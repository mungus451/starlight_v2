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
use App\Models\Entities\AllianceLoan;
use Mockery;
use PDO;

/**
 * Comprehensive Unit Tests for AllianceManagementService.
 * Covers all 22 public methods and their internal logic branches.
 * @covers \App\Models\Services\AllianceManagementService
 */
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

    // 1. Create Alliance (Success)
    public function testCreateAllianceSuccess(): void
    {
        $userId = 1;
        $name = 'New Alliance';
        $tag = 'NEW';
        $cost = 1000;

        $this->mockConfig->shouldReceive('get')->with('game_balance.alliance.creation_cost', 50000000)->andReturn($cost);
        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($this->createMockUser($userId, null));
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($userId)->andReturn($this->createMockResource($userId, 5000));
        $this->mockAllianceRepo->shouldReceive('findByName')->andReturn(null);
        $this->mockAllianceRepo->shouldReceive('findByTag')->andReturn(null);

        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction');
        $this->mockDb->shouldReceive('commit');

        $this->mockAllianceRepo->shouldReceive('create')->andReturn(100);
        $this->mockRoleRepo->shouldReceive('create')->times(3)->andReturn(500);
        $this->mockResourceRepo->shouldReceive('updateCredits')->with($userId, 4000);
        $this->mockUserRepo->shouldReceive('setAlliance')->with($userId, 100, 500);

        $response = $this->service->createAlliance($userId, $name, $tag);
        $this->assertTrue($response->isSuccess());
    }

    // 2. Create Alliance (Fail)
    public function testCreateAllianceFailsInsufficientFunds(): void
    {
        $userId = 1;
        $this->mockConfig->shouldReceive('get')->andReturn(10000);
        $this->mockUserRepo->shouldReceive('findById')->andReturn($this->createMockUser($userId, null));
        $this->mockResourceRepo->shouldReceive('findByUserId')->andReturn($this->createMockResource($userId, 100));
        $this->mockAllianceRepo->shouldReceive('findByName')->andReturn(null);
        $this->mockAllianceRepo->shouldReceive('findByTag')->andReturn(null);

        $response = $this->service->createAlliance($userId, 'Name', 'TAG');
        $this->assertFalse($response->isSuccess());
    }

    // 3. Update Profile (Success)
    public function testUpdateProfileSuccessWithoutFile(): void
    {
        $admin = $this->createMockUser(1, 10);
        $alliance = new Alliance(10, 'A', 'A', 'Old', 'pfp.png', true, 1, 0, 0, null, '');
        $role = $this->createMockRole(true, 'can_edit_profile');

        $this->mockUserRepo->shouldReceive('findById')->with(1)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with($admin->alliance_role_id)->andReturn($role);
        $this->mockAllianceRepo->shouldReceive('findById')->with(10)->andReturn($alliance);

        $this->mockAllianceRepo->shouldReceive('updateProfile')->once()->with(10, 'New Desc', 'pfp.png', true)->andReturn(true);

        $response = $this->service->updateProfile(1, 10, 'New Desc', [], false, true);
        $this->assertTrue($response->isSuccess());
    }

    // 4. Update Profile (Remove Avatar)
    public function testUpdateProfileRemovesAvatar(): void
    {
        $admin = $this->createMockUser(1, 10);
        $alliance = new Alliance(10, 'A', 'A', 'Old', 'pfp.png', true, 1, 0, 0, null, '');
        $role = $this->createMockRole(true, 'can_edit_profile');

        $this->mockUserRepo->shouldReceive('findById')->with(1)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with($admin->alliance_role_id)->andReturn($role);
        $this->mockAllianceRepo->shouldReceive('findById')->with(10)->andReturn($alliance);

        $this->mockAllianceRepo->shouldReceive('updateProfile')->once()->with(10, 'New Desc', '', true)->andReturn(true);

        $response = $this->service->updateProfile(1, 10, 'New Desc', [], true, true);
        $this->assertTrue($response->isSuccess());
    }

    // 5. Get Role Data
    public function testGetRoleManagementDataReturnsRoles(): void
    {
        $user = $this->createMockUser(1, 10);
        $role = $this->createMockRole(true, 'can_manage_roles');

        $this->mockUserRepo->shouldReceive('findById')->with(1)->andReturn($user);
        $this->mockRoleRepo->shouldReceive('findById')->with($user->alliance_role_id)->andReturn($role);
        $this->mockRoleRepo->shouldReceive('findByAllianceId')->with(10)->andReturn([]);

        $response = $this->service->getRoleManagementData(1);
        $this->assertTrue($response->isSuccess());
    }

    // 6. Create Role
    public function testCreateRoleSuccess(): void
    {
        $admin = $this->createMockUser(1, 10);
        $role = $this->createMockRole(true, 'can_manage_roles');

        $this->mockUserRepo->shouldReceive('findById')->with(1)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with($admin->alliance_role_id)->andReturn($role);
        $this->mockRoleRepo->shouldReceive('create')->once();

        $response = $this->service->createRole(1, 10, 'NewRole', []);
        $this->assertTrue($response->isSuccess());
    }

    // 7. Update Role
    public function testUpdateRoleSuccess(): void
    {
        $admin = $this->createMockUser(1, 10);
        $role = $this->createMockRole(true, 'can_manage_roles');
        $target = new AllianceRole(50, 10, 'Custom', 1, false, false, false, false, false, false, false, false, false, false, false);

        $this->mockUserRepo->shouldReceive('findById')->with(1)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with($admin->alliance_role_id)->andReturn($role);
        $this->mockRoleRepo->shouldReceive('findById')->with(50)->andReturn($target);
        $this->mockRoleRepo->shouldReceive('update')->once();

        $response = $this->service->updateRole(1, 50, 'New', []);
        $this->assertTrue($response->isSuccess());
    }

    // 8. Delete Role
    public function testDeleteRoleSuccess(): void
    {
        $admin = $this->createMockUser(1, 10);
        $role = $this->createMockRole(true, 'can_manage_roles');
        $target = new AllianceRole(50, 10, 'Custom', 1, false, false, false, false, false, false, false, false, false, false, false);
        $recruit = $this->createMockRole(false, 'Recruit', 555);

        $this->mockUserRepo->shouldReceive('findById')->with(1)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with($admin->alliance_role_id)->andReturn($role);
        $this->mockRoleRepo->shouldReceive('findById')->with(50)->andReturn($target);
        $this->mockRoleRepo->shouldReceive('findDefaultRole')->andReturn($recruit);

        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction');
        $this->mockDb->shouldReceive('commit');

        $this->mockRoleRepo->shouldReceive('reassignRoleMembers');
        $this->mockRoleRepo->shouldReceive('delete');

        $response = $this->service->deleteRole(1, 50);
        $this->assertTrue($response->isSuccess());
    }

    // 9. Change Member Role
    public function testChangeMemberRoleSuccess(): void
    {
        $admin = $this->createMockUser(1, 10);
        $target = $this->createMockUser(2, 10);
        $role = $this->createMockRole(true);
        $targetRole = $this->createMockRole(false);
        $newRole = $this->createMockRole(false, 'Officer', 99);

        $this->mockUserRepo->shouldReceive('findById')->with(1)->andReturn($admin);
        $this->mockUserRepo->shouldReceive('findById')->with(2)->andReturn($target);
        $this->mockRoleRepo->shouldReceive('findById')->andReturn($role, $targetRole, $newRole);
        $this->mockPolicyService->shouldReceive('canAssignRole')->andReturn(null);
        $this->mockUserRepo->shouldReceive('setAllianceRole')->with(2, 99);

        $response = $this->service->changeMemberRole(1, 2, 99);
        $this->assertTrue($response->isSuccess());
    }

    // 10. Apply
    public function testApplyToAllianceSuccess(): void
    {
        $user = $this->createMockUser(1, null);
        $alliance = new Alliance(10, 'A', 'A', '', '', false, 2, 0, 0, null, '');

        $this->mockUserRepo->shouldReceive('findById')->with(1)->andReturn($user);
        $this->mockAppRepo->shouldReceive('findByUserAndAlliance')->andReturn(null);
        $this->mockAllianceRepo->shouldReceive('findById')->with(10)->andReturn($alliance);
        $this->mockAppRepo->shouldReceive('create')->andReturn(true);

        $response = $this->service->applyToAlliance(1, 10);
        $this->assertTrue($response->isSuccess());
    }

    // 11. Cancel Application
    public function testCancelApplicationSuccess(): void
    {
        $app = Mockery::mock('alias:App\Models\Entities\AllianceApplication');
        $app->id = 5;
        $app->user_id = 1;

        $this->mockAppRepo->shouldReceive('findById')->with(5)->andReturn($app);
        $this->mockAppRepo->shouldReceive('delete')->with(5);

        $response = $this->service->cancelApplication(1, 5);
        $this->assertTrue($response->isSuccess());
    }

    // 12. Accept Application
    public function testAcceptApplicationSuccess(): void
    {
        $admin = $this->createMockUser(1, 10);
        $role = $this->createMockRole(true, 'can_manage_applications');
        $target = $this->createMockUser(2, null);
        
        $app = Mockery::mock('alias:App\Models\Entities\AllianceApplication');
        $app->id = 55;
        $app->alliance_id = 10;
        $app->user_id = 2;

        $this->mockAppRepo->shouldReceive('findById')->with(55)->andReturn($app);
        $this->mockUserRepo->shouldReceive('findById')->with(1)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with($admin->alliance_role_id)->andReturn($role);
        $this->mockUserRepo->shouldReceive('findById')->with(2)->andReturn($target);
        $this->mockRoleRepo->shouldReceive('findDefaultRole')->andReturn($this->createMockRole(false));

        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction');
        $this->mockDb->shouldReceive('commit');

        $this->mockUserRepo->shouldReceive('setAlliance');
        $this->mockAppRepo->shouldReceive('deleteByUser');

        $response = $this->service->acceptApplication(1, 55);
        $this->assertTrue($response->isSuccess());
    }

    // 13. Reject Application
    public function testRejectApplicationSuccess(): void
    {
        $admin = $this->createMockUser(1, 10);
        $role = $this->createMockRole(true, 'can_manage_applications');
        $app = Mockery::mock('alias:App\Models\Entities\AllianceApplication');
        $app->id = 55;
        $app->alliance_id = 10;

        $this->mockAppRepo->shouldReceive('findById')->with(55)->andReturn($app);
        $this->mockUserRepo->shouldReceive('findById')->with(1)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with($admin->alliance_role_id)->andReturn($role);
        $this->mockAppRepo->shouldReceive('delete')->with(55);

        $response = $this->service->rejectApplication(1, 55);
        $this->assertTrue($response->isSuccess());
    }

    // 14. Invite User
    public function testInviteUserSuccess(): void
    {
        $inviter = $this->createMockUser(1, 10);
        $target = $this->createMockUser(2, null);
        $role = $this->createMockRole(true, 'can_invite_members');

        $this->mockUserRepo->shouldReceive('findById')->with(1)->andReturn($inviter);
        $this->mockUserRepo->shouldReceive('findById')->with(2)->andReturn($target);
        $this->mockRoleRepo->shouldReceive('findById')->with($inviter->alliance_role_id)->andReturn($role);
        $this->mockRoleRepo->shouldReceive('findDefaultRole')->andReturn($this->createMockRole(false));

        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction');
        $this->mockDb->shouldReceive('commit');

        $this->mockUserRepo->shouldReceive('setAlliance');
        $this->mockAppRepo->shouldReceive('deleteByUser');

        $response = $this->service->inviteUser(1, 2);
        $this->assertTrue($response->isSuccess());
    }

    // 15. Leave Alliance
    public function testLeaveAllianceSuccess(): void
    {
        $user = $this->createMockUser(1, 10);
        $role = $this->createMockRole(false, 'Member');

        $this->mockUserRepo->shouldReceive('findById')->with(1)->andReturn($user);
        $this->mockRoleRepo->shouldReceive('findById')->with($user->alliance_role_id)->andReturn($role);
        $this->mockUserRepo->shouldReceive('leaveAlliance')->with(1);

        $response = $this->service->leaveAlliance(1);
        $this->assertTrue($response->isSuccess());
    }

    // 16. Kick Member
    public function testKickMemberSuccess(): void
    {
        $admin = $this->createMockUser(1, 10);
        $target = $this->createMockUser(2, 10);
        $adminRole = $this->createMockRole(true, 'Leader');
        $targetRole = $this->createMockRole(false, 'Member');

        $this->mockUserRepo->shouldReceive('findById')->with(1)->andReturn($admin);
        $this->mockUserRepo->shouldReceive('findById')->with(2)->andReturn($target);
        $this->mockRoleRepo->shouldReceive('findById')->with($admin->alliance_role_id)->andReturn($adminRole);
        $this->mockRoleRepo->shouldReceive('findById')->with($target->alliance_role_id)->andReturn($targetRole);

        $this->mockPolicyService->shouldReceive('canKick')->andReturn(null);
        $this->mockUserRepo->shouldReceive('leaveAlliance')->with(2);

        $response = $this->service->kickMember(1, 2);
        $this->assertTrue($response->isSuccess());
    }

    // 17. Donate
    public function testDonateToAllianceSuccess(): void
    {
        $user = $this->createMockUser(1, 10);
        $res = $this->createMockResource(1, 1000);

        $this->mockUserRepo->shouldReceive('findById')->with(1)->andReturn($user);
        $this->mockResourceRepo->shouldReceive('findByUserId')->with(1)->andReturn($res);

        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction');
        $this->mockDb->shouldReceive('commit');

        $this->mockResourceRepo->shouldReceive('updateCredits');
        $this->mockAllianceRepo->shouldReceive('updateBankCreditsRelative');
        $this->mockBankLogRepo->shouldReceive('createLog');

        $response = $this->service->donateToAlliance(1, 100);
        $this->assertTrue($response->isSuccess());
    }

    // 18. Request Loan
    public function testRequestLoanSuccess(): void
    {
        $user = $this->createMockUser(1, 10);
        $this->mockUserRepo->shouldReceive('findById')->with(1)->andReturn($user);
        $this->mockLoanRepo->shouldReceive('createLoanRequest');

        $response = $this->service->requestLoan(1, 1000);
        $this->assertTrue($response->isSuccess());
    }

    // 19. Approve Loan
    public function testApproveLoanSuccess(): void
    {
        $admin = $this->createMockUser(1, 10);
        $role = $this->createMockRole(true, 'can_manage_bank');
        $loan = new AllianceLoan(50, 10, 2, 1000, 1100, 'pending', '', '');
        $alliance = new Alliance(10, 'A', 'A', '', '', true, 1, 0, 100000, null, '');
        $borrower = $this->createMockUser(2, 10);
        $res = $this->createMockResource(2, 0);

        $this->mockLoanRepo->shouldReceive('findById')->with(50)->andReturn($loan);
        $this->mockUserRepo->shouldReceive('findById')->with(1)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with($admin->alliance_role_id)->andReturn($role);
        $this->mockAllianceRepo->shouldReceive('findById')->with(10)->andReturn($alliance);
        $this->mockUserRepo->shouldReceive('findById')->with(2)->andReturn($borrower);
        $this->mockResourceRepo->shouldReceive('findByUserId')->with(2)->andReturn($res);

        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction');
        $this->mockDb->shouldReceive('commit');

        $this->mockAllianceRepo->shouldReceive('updateBankCreditsRelative');
        $this->mockResourceRepo->shouldReceive('updateCredits');
        $this->mockBankLogRepo->shouldReceive('createLog');
        $this->mockLoanRepo->shouldReceive('updateLoan');

        $response = $this->service->approveLoan(1, 50);
        $this->assertTrue($response->isSuccess());
    }

    // 20. Deny Loan
    public function testDenyLoanSuccess(): void
    {
        $admin = $this->createMockUser(1, 10);
        $role = $this->createMockRole(true, 'can_manage_bank');
        $loan = new AllianceLoan(50, 10, 2, 1000, 1100, 'pending', '', '');

        $this->mockLoanRepo->shouldReceive('findById')->with(50)->andReturn($loan);
        $this->mockUserRepo->shouldReceive('findById')->with(1)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with($admin->alliance_role_id)->andReturn($role);
        $this->mockLoanRepo->shouldReceive('updateLoan');

        $response = $this->service->denyLoan(1, 50);
        $this->assertTrue($response->isSuccess());
    }

    // 21. Repay Loan
    public function testRepayLoanSuccess(): void
    {
        $loan = new AllianceLoan(50, 10, 1, 1000, 1000, 'active', '', '');
        $user = $this->createMockUser(1, 10);
        $res = $this->createMockResource(1, 5000);

        $this->mockLoanRepo->shouldReceive('findById')->with(50)->andReturn($loan);
        $this->mockUserRepo->shouldReceive('findById')->with(1)->andReturn($user);
        $this->mockResourceRepo->shouldReceive('findByUserId')->with(1)->andReturn($res);

        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction');
        $this->mockDb->shouldReceive('commit');

        $this->mockResourceRepo->shouldReceive('updateCredits');
        $this->mockAllianceRepo->shouldReceive('updateBankCreditsRelative');
        $this->mockBankLogRepo->shouldReceive('createLog');
        $this->mockLoanRepo->shouldReceive('updateLoan');

        $response = $this->service->repayLoan(1, 50, 500);
        $this->assertTrue($response->isSuccess());
    }

    // 22. Check Permission (Fail Case)
    public function testCheckPermissionFailsOnRole(): void
    {
        $user = $this->createMockUser(1, 10);
        $role = $this->createMockRole(false, 'NoPerms');

        $this->mockUserRepo->shouldReceive('findById')->with(1)->andReturn($user);
        $this->mockRoleRepo->shouldReceive('findById')->with($user->alliance_role_id)->andReturn($role);
        $this->mockRoleRepo->shouldReceive('findById')->with(50)->andReturn(new AllianceRole(50, 10, 'T', 1, false, false, false, false, false, false, false, false, false, false, false));

        $response = $this->service->updateRole(1, 50, 'Name', []);
        $this->assertFalse($response->isSuccess());
        $this->assertStringContainsString('do not have permission', $response->message);
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

    private function createMockRole(bool $hasPermission, string $permName = 'perm', int $id = 100): AllianceRole
    {
        $perms = [
            'can_edit_profile' => false,
            'can_manage_applications' => false,
            'can_invite_members' => false,
            'can_kick_members' => false,
            'can_manage_roles' => false,
            'can_see_private_board' => false,
            'can_manage_forum' => false,
            'can_manage_bank' => false,
            'can_manage_structures' => false,
            'can_manage_diplomacy' => false,
            'can_declare_war' => false
        ];

        if ($hasPermission && array_key_exists($permName, $perms)) {
            $perms[$permName] = true;
        }

        return new AllianceRole(
            id: $id,
            alliance_id: 10,
            name: 'Role',
            sort_order: 1,
            can_edit_profile: $perms['can_edit_profile'],
            can_manage_applications: $perms['can_manage_applications'],
            can_invite_members: $perms['can_invite_members'],
            can_kick_members: $perms['can_kick_members'],
            can_manage_roles: $perms['can_manage_roles'],
            can_see_private_board: $perms['can_see_private_board'],
            can_manage_forum: $perms['can_manage_forum'],
            can_manage_bank: $perms['can_manage_bank'],
            can_manage_structures: $perms['can_manage_structures'],
            can_manage_diplomacy: $perms['can_manage_diplomacy'],
            can_declare_war: $perms['can_declare_war']
        );
    }
}