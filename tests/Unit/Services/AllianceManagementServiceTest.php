<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\AllianceManagementService;
use App\Models\Services\AlliancePolicyService;
use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ApplicationRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\AllianceBankLogRepository;
use App\Models\Repositories\AllianceLoanRepository;
use App\Models\Entities\User;
use App\Models\Entities\AllianceRole;
use App\Models\Entities\UserResource;
use App\Models\Entities\Alliance;
use Mockery;
use PDO;

class AllianceManagementServiceTest extends TestCase
{
    private AllianceManagementService $service;
    private PDO|Mockery\MockInterface $mockDb;
    private Config|Mockery\MockInterface $mockConfig;
    private AllianceRepository|Mockery\MockInterface $mockAllianceRepo;
    private UserRepository|Mockery\MockInterface $mockUserRepo;
    private ApplicationRepository|Mockery\MockInterface $mockAppRepo;
    private AllianceRoleRepository|Mockery\MockInterface $mockRoleRepo;
    private AlliancePolicyService|Mockery\MockInterface $mockPolicyService;
    private ResourceRepository|Mockery\MockInterface $mockResourceRepo;
    private AllianceBankLogRepository|Mockery\MockInterface $mockBankLogRepo;
    private AllianceLoanRepository|Mockery\MockInterface $mockLoanRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockDb = Mockery::mock(PDO::class);
        $this->mockConfig = Mockery::mock(Config::class);
        $this->mockAllianceRepo = Mockery::mock(AllianceRepository::class);
        $this->mockUserRepo = Mockery::mock(UserRepository::class);
        $this->mockAppRepo = Mockery::mock(ApplicationRepository::class);
        $this->mockRoleRepo = Mockery::mock(AllianceRoleRepository::class);
        $this->mockPolicyService = Mockery::mock(AlliancePolicyService::class);
        $this->mockResourceRepo = Mockery::mock(ResourceRepository::class);
        $this->mockBankLogRepo = Mockery::mock(AllianceBankLogRepository::class);
        $this->mockLoanRepo = Mockery::mock(AllianceLoanRepository::class);

        $this->service = new AllianceManagementService(
            $this->mockDb,
            $this->mockConfig,
            $this->mockAllianceRepo,
            $this->mockUserRepo,
            $this->mockAppRepo,
            $this->mockRoleRepo,
            $this->mockPolicyService,
            $this->mockResourceRepo,
            $this->mockBankLogRepo,
            $this->mockLoanRepo
        );
    }

    public function testCreateAllianceSuccess(): void
    {
        $userId = 1;
        $name = 'New Alliance';
        $tag = 'NEW';
        $cost = 1000;

        // Mock Config
        $this->mockConfig->shouldReceive('get')->with('game_balance.alliance.creation_cost', 50000000)->andReturn($cost);

        // Mock User & Resources
        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($this->createMockUser($userId, null));
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($userId)->andReturn($this->createMockResource($userId, 5000)); // Has enough

        // Mock Uniqueness Checks
        $this->mockAllianceRepo->shouldReceive('findByName')->with($name)->andReturn(null);
        $this->mockAllianceRepo->shouldReceive('findByTag')->with($tag)->andReturn(null);

        // Mock Transaction
        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction');
        $this->mockDb->shouldReceive('commit');

        // Mock Creation Steps
        $newAllianceId = 100;
        $this->mockAllianceRepo->shouldReceive('create')->with($name, $tag, $userId)->andReturn($newAllianceId);

        // Mock Role Creation
        $leaderRoleId = 500;
        $this->mockRoleRepo->shouldReceive('create')->times(3)->andReturn($leaderRoleId); // Leader, Recruit, Member

        // Mock Updates
        $this->mockResourceRepo->shouldReceive('updateCredits')->with($userId, 4000); // 5000 - 1000
        $this->mockUserRepo->shouldReceive('setAlliance')->with($userId, $newAllianceId, $leaderRoleId);

        $response = $this->service->createAlliance($userId, $name, $tag);

        $this->assertTrue($response->isSuccess());
        $this->assertStringContainsString('successfully founded', $response->message);
    }

    public function testCreateAllianceFailsInsufficientFunds(): void
    {
        $userId = 1;
        $this->mockConfig->shouldReceive('get')->andReturn(10000); // High cost
        $this->mockUserRepo->shouldReceive('findById')->andReturn($this->createMockUser($userId, null));
        $this->mockResourceRepo->shouldReceive('findByUserId')->andReturn($this->createMockResource($userId, 100)); // Low funds
        $this->mockAllianceRepo->shouldReceive('findByName')->andReturn(null);
        $this->mockAllianceRepo->shouldReceive('findByTag')->andReturn(null);

        $response = $this->service->createAlliance($userId, 'Name', 'TAG');
        $this->assertFalse($response->isSuccess());
        $this->assertStringContainsString('not have enough credits', $response->message);
    }

    public function testInviteUserSuccess(): void
    {
        $inviterId = 1;
        $targetId = 2;
        $allianceId = 10;

        $inviter = $this->createMockUser($inviterId, $allianceId);
        $target = $this->createMockUser($targetId, null);
        
        $this->mockUserRepo->shouldReceive('findById')->with($inviterId)->andReturn($inviter);
        $this->mockUserRepo->shouldReceive('findById')->with($targetId)->andReturn($target);

        // Permission Check
        $adminRole = $this->createMockRole(true);
        $this->mockRoleRepo->shouldReceive('findById')->with($inviter->alliance_role_id)->andReturn($adminRole);

        // Recruit Role Lookup
        $recruitRole = $this->createMockRole(false, 'Recruit', 555);
        $this->mockRoleRepo->shouldReceive('findDefaultRole')->with($allianceId, 'Recruit')->andReturn($recruitRole);

        // Transaction
        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction');
        $this->mockDb->shouldReceive('commit');

        // Logic
        $this->mockUserRepo->shouldReceive('setAlliance')->with($targetId, $allianceId, 555);
        $this->mockAppRepo->shouldReceive('deleteByUser')->with($targetId);

        $response = $this->service->inviteUser($inviterId, $targetId);

        $this->assertTrue($response->isSuccess());
        $this->assertStringContainsString('accepted your invite', $response->message);
    }

    public function testKickMemberSuccess(): void
    {
        $adminId = 1;
        $targetId = 2;
        $allianceId = 10;

        $admin = $this->createMockUser($adminId, $allianceId);
        $target = $this->createMockUser($targetId, $allianceId);

        $this->mockUserRepo->shouldReceive('findById')->with($adminId)->andReturn($admin);
        $this->mockUserRepo->shouldReceive('findById')->with($targetId)->andReturn($target);

        $adminRole = $this->createMockRole(true, 'Leader');
        $targetRole = $this->createMockRole(false, 'Member');

        $this->mockRoleRepo->shouldReceive('findById')->with($admin->alliance_role_id)->andReturn($adminRole);
        $this->mockRoleRepo->shouldReceive('findById')->with($target->alliance_role_id)->andReturn($targetRole);

        // Policy Check (Null = Allowed)
        $this->mockPolicyService->shouldReceive('canKick')
            ->with(
                Mockery::type(User::class),
                Mockery::type(AllianceRole::class),
                Mockery::type(User::class),
                Mockery::type(AllianceRole::class)
            )
            ->andReturn(null);

        // Action
        $this->mockUserRepo->shouldReceive('leaveAlliance')->with($targetId);

        $response = $this->service->kickMember($adminId, $targetId);

        $this->assertTrue($response->isSuccess());
        $this->assertStringContainsString('has been kicked', $response->message);
    }

    public function testDonateToAllianceSuccess(): void
    {
        $userId = 1;
        $amount = 100;
        $allianceId = 50;

        $user = $this->createMockUser($userId, $allianceId);
        $resources = $this->createMockResource($userId, 1000);

        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($user);
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($userId)->andReturn($resources);

        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction');
        $this->mockDb->shouldReceive('commit');

        $this->mockResourceRepo->shouldReceive('updateCredits')->with($userId, 900);
        $this->mockAllianceRepo->shouldReceive('updateBankCreditsRelative')->with($allianceId, 100);
        $this->mockBankLogRepo->shouldReceive('createLog')->once();

        $response = $this->service->donateToAlliance($userId, $amount);

        $this->assertTrue($response->isSuccess());
        $this->assertStringContainsString('successfully donated', $response->message);
    }

    // --- Roles ---

    public function testCreateRoleSuccess(): void
    {
        $adminId = 1;
        $allianceId = 10;
        $admin = $this->createMockUser($adminId, $allianceId);
        $adminRole = $this->createMockRole(false, 'Leader', 100);
        // Manually override property for test logic check (can_manage_roles) if needed,
        // or ensure createMockRole sets it.
        // My helper sets 'can_manage_roles' to false by default. I need to override/mock it.
        
        // Since AllianceRole is readonly, I'll just create a new one here manually
        $adminRole = new AllianceRole(
            100, $allianceId, 'Leader', 1, true, true, true, true, true, true, true, true, true, true, true
        );

        $this->mockUserRepo->shouldReceive('findById')->with($adminId)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with($admin->alliance_role_id)->andReturn($adminRole);

        $this->mockRoleRepo->shouldReceive('create')
            ->once()
            ->with($allianceId, 'Officer', 100, ['can_kick_members' => 1])
            ->andReturn(200);

        $response = $this->service->createRole($adminId, $allianceId, 'Officer', ['can_kick_members' => 1]);
        $this->assertTrue($response->isSuccess());
    }

    public function testDeleteRoleSuccess(): void
    {
        $adminId = 1;
        $roleId = 50;
        $allianceId = 10;

        $admin = $this->createMockUser($adminId, $allianceId);
        $adminRole = new AllianceRole(100, $allianceId, 'Leader', 1, true, true, true, true, true, true, true, true, true, true, true);
        
        $targetRole = $this->createMockRole(false, 'CustomRole', $roleId);
        $recruitRole = $this->createMockRole(false, 'Recruit', 555);

        $this->mockRoleRepo->shouldReceive('findById')->with($roleId)->andReturn($targetRole);
        $this->mockUserRepo->shouldReceive('findById')->with($adminId)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with($admin->alliance_role_id)->andReturn($adminRole);
        $this->mockRoleRepo->shouldReceive('findDefaultRole')->with($allianceId, 'Recruit')->andReturn($recruitRole);

        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction');
        $this->mockDb->shouldReceive('commit');

        $this->mockRoleRepo->shouldReceive('reassignRoleMembers')->once()->with($roleId, 555);
        $this->mockRoleRepo->shouldReceive('delete')->once()->with($roleId);

        $response = $this->service->deleteRole($adminId, $roleId);
        $this->assertTrue($response->isSuccess());
    }

    // --- Loans ---

    public function testRequestLoanSuccess(): void
    {
        $userId = 1;
        $allianceId = 10;
        $user = $this->createMockUser($userId, $allianceId);

        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($user);
        
        $this->mockLoanRepo->shouldReceive('createLoanRequest')
            ->once()
            ->with($allianceId, $userId, 1000)
            ->andReturn(true);

        $response = $this->service->requestLoan($userId, 1000);
        $this->assertTrue($response->isSuccess());
    }

    public function testApproveLoanSuccess(): void
    {
        $adminId = 1;
        $loanId = 50;
        $borrowerId = 2;
        $allianceId = 10;

        // Admin
        $admin = $this->createMockUser($adminId, $allianceId);
        $adminRole = new AllianceRole(100, $allianceId, 'Leader', 1, true, true, true, true, true, true, true, true, true, true, true);
        $this->mockUserRepo->shouldReceive('findById')->with($adminId)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with($admin->alliance_role_id)->andReturn($adminRole);

        // Loan Mock (stdClass or Array often used in repos, but here likely an Entity?
        // Assuming Loan is likely an Entity or Object. Let's look at signature.
        // It's AllianceLoan Entity.
        $loan = new \App\Models\Entities\AllianceLoan(
            $loanId, $allianceId, $borrowerId, 1000, 1100, 'pending', '2024-01-01', '2024-01-01'
        );

        $this->mockLoanRepo->shouldReceive('findById')->with($loanId)->andReturn($loan);

        // Alliance Check
        $alliance = new Alliance($allianceId, 'A', 'A', '', '', true, 1, 0, 100000, null, '');
        $this->mockAllianceRepo->shouldReceive('findById')->with($allianceId)->andReturn($alliance);

        // Borrower Check
        $borrower = $this->createMockUser($borrowerId, $allianceId);
        $borrowerRes = $this->createMockResource($borrowerId, 0);
        $this->mockUserRepo->shouldReceive('findById')->with($borrowerId)->andReturn($borrower);
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($borrowerId)->andReturn($borrowerRes);

        // Transaction
        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction');
        $this->mockDb->shouldReceive('commit');

        // Logic
        $this->mockAllianceRepo->shouldReceive('updateBankCreditsRelative')->with($allianceId, -1000);
        $this->mockResourceRepo->shouldReceive('updateCredits')->with($borrowerId, 1000);
        $this->mockBankLogRepo->shouldReceive('createLog');
        $this->mockLoanRepo->shouldReceive('updateLoan')->with($loanId, 'active', 1100);

        $response = $this->service->approveLoan($adminId, $loanId);
        $this->assertTrue($response->isSuccess());
    }

    public function testRepayLoanSuccess(): void
    {
        $userId = 1;
        $loanId = 50;
        $allianceId = 10;
        $amount = 500;

        $loan = new \App\Models\Entities\AllianceLoan(
            $loanId, $allianceId, $userId, 1000, 1000, 'active', '2024-01-01', '2024-01-01'
        );

        $this->mockLoanRepo->shouldReceive('findById')->with($loanId)->andReturn($loan);
        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($this->createMockUser($userId, $allianceId));
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($userId)->andReturn($this->createMockResource($userId, 5000));

        // Transaction
        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction');
        $this->mockDb->shouldReceive('commit');

        // Logic
        $this->mockResourceRepo->shouldReceive('updateCredits')->with($userId, 4500); // 5000 - 500
        $this->mockAllianceRepo->shouldReceive('updateBankCreditsRelative')->with($allianceId, 500);
        $this->mockBankLogRepo->shouldReceive('createLog');
        $this->mockLoanRepo->shouldReceive('updateLoan')->with($loanId, 'active', 500); // 1000 - 500

        $response = $this->service->repayLoan($userId, $loanId, $amount);
        $this->assertTrue($response->isSuccess());
    }

    // --- Membership ---

    public function testLeaveAllianceSuccess(): void
    {
        $userId = 1;
        $user = $this->createMockUser($userId, 10);
        // Not a leader role
        $role = $this->createMockRole(false, 'Member');

        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($user);
        $this->mockRoleRepo->shouldReceive('findById')->with($user->alliance_role_id)->andReturn($role);
        $this->mockUserRepo->shouldReceive('leaveAlliance')->with($userId);

        $response = $this->service->leaveAlliance($userId);
        $this->assertTrue($response->isSuccess());
    }

    public function testCancelApplicationSuccess(): void
    {
        $userId = 1;
        $appId = 99;
        
        $app = Mockery::mock('alias:App\Models\Entities\AllianceApplication');
        $app->id = $appId;
        $app->user_id = $userId;

        $this->mockAppRepo->shouldReceive('findById')->with($appId)->andReturn($app);
        $this->mockAppRepo->shouldReceive('delete')->with($appId);

        $response = $this->service->cancelApplication($userId, $appId);
        $this->assertTrue($response->isSuccess());
    }

    // --- Helpers ---

    private function createMockUser(int $id, ?int $allianceId): User
    {
        return new User(
            id: $id,
            email: 'test@test.com',
            characterName: 'TestUser',
            bio: null,
            profile_picture_url: null,
            phone_number: null,
            alliance_id: $allianceId,
            alliance_role_id: $allianceId ? 100 : null,
            passwordHash: 'hash',
            createdAt: '2024-01-01',
            is_npc: false
        );
    }

    private function createMockResource(int $userId, int $credits): UserResource
    {
        return new UserResource($userId, $credits, 0, 0, 0.0, 0, 0, 0, 0, 0, 0);
    }

    private function createMockRole(bool $canInvite, string $name = 'Role', int $id = 100): AllianceRole
    {
        return new AllianceRole(
            id: $id,
            alliance_id: 10,
            name: $name,
            sort_order: 1,
            can_edit_profile: false,
            can_manage_applications: false,
            can_invite_members: $canInvite,
            can_kick_members: false,
            can_manage_roles: false,
            can_see_private_board: false,
            can_manage_forum: false,
            can_manage_bank: false,
            can_manage_structures: false,
            can_manage_diplomacy: false,
            can_declare_war: false
        );
    }
}
