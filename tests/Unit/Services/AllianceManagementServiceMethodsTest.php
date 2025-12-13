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
use App\Models\Entities\Alliance;
use App\Models\Entities\AllianceLoan;
use Mockery;
use PDO;

/**
 * Part 2 of AllianceManagementService tests.
 * Focuses on Profile, Roles, and Application logic not covered in Part 1.
 */
class AllianceManagementServiceMethodsTest extends TestCase
{
    private AllianceManagementService $service;
    private PDO|Mockery\MockInterface $mockDb;
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
        $mockConfig = Mockery::mock(Config::class);
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
            $mockConfig,
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

    // --- Profile & Files ---

    public function testUpdateProfileUpdatesDescriptionOnly(): void
    {
        $adminId = 1;
        $allianceId = 10;
        
        $admin = $this->createMockUser($adminId, $allianceId);
        $role = $this->createMockRole(true, 'can_edit_profile'); // Permission: yes
        $alliance = new Alliance($allianceId, 'A', 'A', 'Old Desc', 'old.png', true, 1, 0, 0, null, '');

        $this->mockUserRepo->shouldReceive('findById')->with($adminId)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with($admin->alliance_role_id)->andReturn($role);
        $this->mockAllianceRepo->shouldReceive('findById')->with($allianceId)->andReturn($alliance);

        $this->mockAllianceRepo->shouldReceive('updateProfile')
            ->once()
            ->with($allianceId, 'New Desc', 'old.png', true) // Old png preserved
            ->andReturn(true);

        $response = $this->service->updateProfile($adminId, $allianceId, 'New Desc', [], false, true);
        $this->assertTrue($response->isSuccess());
    }

    // --- Role Management ---

    public function testGetRoleManagementDataReturnsRoles(): void
    {
        $userId = 1;
        $allianceId = 10;
        $user = $this->createMockUser($userId, $allianceId);
        $role = $this->createMockRole(true, 'can_manage_roles');

        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($user);
        $this->mockRoleRepo->shouldReceive('findById')->with($user->alliance_role_id)->andReturn($role);
        $this->mockRoleRepo->shouldReceive('findByAllianceId')->with($allianceId)->andReturn([]);

        $response = $this->service->getRoleManagementData($userId);
        $this->assertTrue($response->isSuccess());
        $this->assertArrayHasKey('roles', $response->data);
    }

    public function testUpdateRoleSuccess(): void
    {
        $adminId = 1;
        $roleId = 50;
        $allianceId = 10;

        $admin = $this->createMockUser($adminId, $allianceId);
        $adminRole = $this->createMockRole(true, 'can_manage_roles');
        $targetRole = new AllianceRole($roleId, $allianceId, 'Custom', 1, false, false, false, false, false, false, false, false, false, false, false);

        $this->mockRoleRepo->shouldReceive('findById')->with($roleId)->andReturn($targetRole);
        $this->mockUserRepo->shouldReceive('findById')->with($adminId)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with($admin->alliance_role_id)->andReturn($adminRole);

        $this->mockRoleRepo->shouldReceive('update')
            ->once()
            ->with($roleId, 'NewName', ['can_invite_members' => 1]);

        $response = $this->service->updateRole($adminId, $roleId, 'NewName', ['can_invite_members' => 1]);
        $this->assertTrue($response->isSuccess());
    }

    public function testChangeMemberRoleSuccess(): void
    {
        $adminId = 1;
        $targetId = 2;
        $newRoleId = 99;

        $admin = $this->createMockUser($adminId, 10);
        $target = $this->createMockUser($targetId, 10);
        
        $adminRole = $this->createMockRole(true); // Is admin
        $targetRole = $this->createMockRole(false);
        $newRole = $this->createMockRole(false, 'Officer', $newRoleId);

        $this->mockUserRepo->shouldReceive('findById')->with($adminId)->andReturn($admin);
        $this->mockUserRepo->shouldReceive('findById')->with($targetId)->andReturn($target);
        
        $this->mockRoleRepo->shouldReceive('findById')->with($admin->alliance_role_id)->andReturn($adminRole);
        $this->mockRoleRepo->shouldReceive('findById')->with($target->alliance_role_id)->andReturn($targetRole);
        $this->mockRoleRepo->shouldReceive('findById')->with($newRoleId)->andReturn($newRole);

        $this->mockPolicyService->shouldReceive('canAssignRole')->andReturn(null); // Allowed

        $this->mockUserRepo->shouldReceive('setAllianceRole')->once()->with($targetId, $newRoleId);

        $response = $this->service->changeMemberRole($adminId, $targetId, $newRoleId);
        $this->assertTrue($response->isSuccess());
    }

    // --- Application Logic ---

    public function testApplyToAllianceSuccess(): void
    {
        $userId = 1;
        $allianceId = 10;
        
        $user = $this->createMockUser($userId, null); // Not in alliance
        $alliance = new Alliance($allianceId, 'A', 'A', '', '', false, 2, 0, 0, null, ''); // Application required

        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($user);
        $this->mockAppRepo->shouldReceive('findByUserAndAlliance')->with($userId, $allianceId)->andReturn(null);
        $this->mockAllianceRepo->shouldReceive('findById')->with($allianceId)->andReturn($alliance);

        $this->mockAppRepo->shouldReceive('create')->once()->with($userId, $allianceId)->andReturn(true);

        $response = $this->service->applyToAlliance($userId, $allianceId);
        $this->assertTrue($response->isSuccess());
        $this->assertEquals('Application sent!', $response->message);
    }

    public function testRejectApplicationSuccess(): void
    {
        $adminId = 1;
        $appId = 55;
        $allianceId = 10;

        $admin = $this->createMockUser($adminId, $allianceId);
        $adminRole = $this->createMockRole(true, 'can_manage_applications');
        
        $app = Mockery::mock('alias:App\Models\Entities\AllianceApplication');
        $app->id = $appId;
        $app->alliance_id = $allianceId;

        $this->mockAppRepo->shouldReceive('findById')->with($appId)->andReturn($app);
        $this->mockUserRepo->shouldReceive('findById')->with($adminId)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with($admin->alliance_role_id)->andReturn($adminRole);

        $this->mockAppRepo->shouldReceive('delete')->once()->with($appId);

        $response = $this->service->rejectApplication($adminId, $appId);
        $this->assertTrue($response->isSuccess());
    }

    // --- Loan Logic ---

    public function testDenyLoanSuccess(): void
    {
        $adminId = 1;
        $loanId = 55;
        $allianceId = 10;

        $admin = $this->createMockUser($adminId, $allianceId);
        $adminRole = $this->createMockRole(true, 'can_manage_bank');

        $loan = new AllianceLoan(
            $loanId, $allianceId, 2, 1000, 1100, 'pending', '2024-01-01', '2024-01-01'
        );

        $this->mockLoanRepo->shouldReceive('findById')->with($loanId)->andReturn($loan);
        $this->mockUserRepo->shouldReceive('findById')->with($adminId)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with($admin->alliance_role_id)->andReturn($adminRole);

        $this->mockLoanRepo->shouldReceive('updateLoan')->once()->with($loanId, 'denied', 0);

        $response = $this->service->denyLoan($adminId, $loanId);
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

    private function createMockRole(bool $hasPermission, string $permName = 'perm', int $id = 100): AllianceRole
    {
        // Dynamic permission mapping for test convenience
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
