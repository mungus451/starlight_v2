<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\AllianceManagementService;
use App\Models\Services\AlliancePolicyService;
use App\Models\Services\NotificationService;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ApplicationRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\AllianceBankLogRepository;
use App\Models\Repositories\AllianceLoanRepository;
use App\Core\Config;
use App\Models\Entities\User;
use App\Models\Entities\Alliance;
use App\Models\Entities\AllianceRole;
use App\Models\Entities\UserResource;
use App\Models\Entities\AllianceApplication;
use App\Models\Entities\AllianceLoan;
use App\Core\Permissions;
use App\Core\Logger;
use Mockery;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AllianceManagementService::class)]
class AllianceManagementServiceTest extends TestCase
{
    private AllianceManagementService $service;
    
    // Mocks
    private $mockPdo;
    private $mockConfig;
    private $mockAllianceRepo;
    private $mockUserRepo;
    private $mockAppRepo;
    private $mockRoleRepo;
    private $mockPolicyService;
    private $mockResourceRepo;
    private $mockBankLogRepo;
    private $mockLoanRepo;
    private Logger|Mockery\MockInterface $mockLogger;
    private $mockNotificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockPdo = $this->createMockPDO();
        $this->mockConfig = Mockery::mock(Config::class);
        $this->mockAllianceRepo = Mockery::mock(AllianceRepository::class);
        $this->mockUserRepo = Mockery::mock(UserRepository::class);
        $this->mockAppRepo = Mockery::mock(ApplicationRepository::class);
        $this->mockRoleRepo = Mockery::mock(AllianceRoleRepository::class);
        $this->mockPolicyService = Mockery::mock(AlliancePolicyService::class);
        $this->mockResourceRepo = Mockery::mock(ResourceRepository::class);
        $this->mockBankLogRepo = Mockery::mock(AllianceBankLogRepository::class);
        $this->mockLoanRepo = Mockery::mock(AllianceLoanRepository::class);
        $this->mockLogger = Mockery::mock(Logger::class);
        $this->mockNotificationService = Mockery::mock(NotificationService::class);

        // Handle DB transactions in service
        $this->mockPdo->shouldReceive('beginTransaction')->byDefault();
        $this->mockPdo->shouldReceive('commit')->byDefault();
        $this->mockPdo->shouldReceive('rollBack')->byDefault();
        $this->mockPdo->shouldReceive('inTransaction')->andReturn(true)->byDefault();

        // Allow notification service calls by default
        $this->mockNotificationService->shouldReceive('notifyAllianceMembers')->byDefault();

        $this->service = new AllianceManagementService(
            $this->mockPdo,
            $this->mockConfig,
            $this->mockAllianceRepo,
            $this->mockUserRepo,
            $this->mockAppRepo,
            $this->mockRoleRepo,
            $this->mockPolicyService,
            $this->mockResourceRepo,
            $this->mockBankLogRepo,
            $this->mockLoanRepo,
            $this->mockLogger,
            $this->mockNotificationService
        );
    }

    // 1. Update Profile
    public function testUpdateProfile_Success_NoFile(): void
    {
        $adminId = 1;
        $allianceId = 10;
        
        $admin = $this->mockUser(id: $adminId, alliance_id: $allianceId, alliance_role_id: 5);
        $role = $this->mockRole(id: 5, perms: ['can_edit_profile' => true]);
        $alliance = $this->mockAlliance(id: $allianceId);

        $this->mockUserRepo->shouldReceive('findById')->with($adminId)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with(5)->andReturn($role);
        $this->mockAllianceRepo->shouldReceive('findById')->with($allianceId)->andReturn($alliance);
        
        $this->mockAllianceRepo
            ->shouldReceive('updateProfile')
            ->once()
            ->with($allianceId, 'New Desc', Mockery::any(), true);

        $response = $this->service->updateProfile($adminId, $allianceId, 'New Desc', [], false, true);
        $this->assertServiceSuccess($response);
    }

    // 2. Role Management Data
    public function testGetRoleManagementData_Success(): void
    {
        $userId = 1;
        $allianceId = 10;
        
        $user = $this->mockUser(id: $userId, alliance_id: $allianceId, alliance_role_id: 5);
        $role = $this->mockRole(id: 5, perms: ['can_manage_roles' => true]);
        
        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($user);
        $this->mockRoleRepo->shouldReceive('findById')->with(5)->andReturn($role);
        $this->mockRoleRepo->shouldReceive('findByAllianceId')->with($allianceId)->andReturn([]);

        $response = $this->service->getRoleManagementData($userId);
        $this->assertServiceSuccess($response);
    }

    // 3. Create Alliance (Success)
    public function testCreateAlliance_Success(): void
    {
        $userId = 1;
        $cost = 50000;
        
        $user = $this->mockUser(id: $userId, alliance_id: null);
        $resources = $this->mockResource(credits: 100000);

        $this->mockAllianceRepo->shouldReceive('findByName')->andReturnNull();
        $this->mockAllianceRepo->shouldReceive('findByTag')->andReturnNull();
        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($user);
        $this->mockConfig->shouldReceive('get')->with('game_balance.alliance.creation_cost', Mockery::any())->andReturn($cost);
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($userId)->andReturn($resources);

        // Transaction expectations
        $newAllianceId = 10;
        $leaderRoleId = 5;
        $this->mockAllianceRepo->shouldReceive('create')->andReturn($newAllianceId);
        $this->mockRoleRepo->shouldReceive('create')->times(3)->andReturn($leaderRoleId); // Leader, Recruit, Member
        $this->mockResourceRepo->shouldReceive('updateCredits')->with($userId, 50000);
        $this->mockUserRepo->shouldReceive('setAlliance')->with($userId, $newAllianceId, $leaderRoleId);

        $response = $this->service->createAlliance($userId, 'Empire', 'EMP');
        $this->assertServiceSuccess($response);
    }

    // 4. Create Alliance (Name Taken)
    public function testCreateAlliance_Fail_NameTaken(): void
    {
        $this->mockAllianceRepo->shouldReceive('findByName')->andReturn(new Alliance(
            id: 99, name: 'Existing', tag: 'TAG', description: null, profile_picture_url: null,
            is_joinable: false, leader_id: 1, net_worth: 0, bank_credits: 0, last_compound_at: null, created_at: 'now'
        ));
        $response = $this->service->createAlliance(1, 'Existing', 'TAG');
        $this->assertServiceFailure($response, 'name already exists');
    }

    // 5. Apply to Alliance
    public function testApplyToAlliance_Success(): void
    {
        $userId = 1;
        $allianceId = 10;

        $user = $this->mockUser(id: $userId, alliance_id: null);
        $alliance = $this->mockAlliance(id: $allianceId); // Not auto-joinable by default

        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($user);
        $this->mockAppRepo->shouldReceive('findByUserAndAlliance')->andReturnNull();
        $this->mockAllianceRepo->shouldReceive('findById')->with($allianceId)->andReturn($alliance);
        $this->mockAppRepo->shouldReceive('create')->with($userId, $allianceId)->andReturn(true);

        $response = $this->service->applyToAlliance($userId, $allianceId);
        $this->assertServiceSuccess($response);
    }

    // 6. Cancel Application
    public function testCancelApplication_Success(): void
    {
        $userId = 1;
        $appId = 5;
        
        $app = new AllianceApplication(id: $appId, user_id: $userId, alliance_id: 10, created_at: 'now');

        $this->mockAppRepo->shouldReceive('findById')->with($appId)->andReturn($app);
        $this->mockAppRepo->shouldReceive('delete')->with($appId);

        $response = $this->service->cancelApplication($userId, $appId);
        $this->assertServiceSuccess($response);
    }

    // 7. Leave Alliance
    public function testLeaveAlliance_Success(): void
    {
        $userId = 1;
        $user = $this->mockUser(id: $userId, alliance_id: 10, alliance_role_id: 5);
        $role = $this->mockRole(id: 5, name: 'Member');

        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($user);
        $this->mockRoleRepo->shouldReceive('findById')->with(5)->andReturn($role);
        $this->mockUserRepo->shouldReceive('leaveAlliance')->with($userId);

        $response = $this->service->leaveAlliance($userId);
        $this->assertServiceSuccess($response);
    }

    // 8. Accept Application
    public function testAcceptApplication_Success(): void
    {
        $adminId = 1;
        $appId = 5;
        $targetUserId = 2;
        $allianceId = 10;

        $app = new AllianceApplication(id: $appId, user_id: $targetUserId, alliance_id: $allianceId, created_at: 'now');
        $admin = $this->mockUser(id: $adminId, alliance_id: $allianceId, alliance_role_id: 50);
        $adminRole = $this->mockRole(id: 50, perms: ['can_manage_applications' => true]);
        $targetUser = $this->mockUser(id: $targetUserId, alliance_id: null);
        $recruitRole = $this->mockRole(id: 60, name: 'Recruit');

        $this->mockAppRepo->shouldReceive('findById')->with($appId)->andReturn($app);
        $this->mockUserRepo->shouldReceive('findById')->with($adminId)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with(50)->andReturn($adminRole);
        $this->mockUserRepo->shouldReceive('findById')->with($targetUserId)->andReturn($targetUser);
        $this->mockRoleRepo->shouldReceive('findDefaultRole')->with($allianceId, 'Recruit')->andReturn($recruitRole);
        
        // Actions
        $this->mockUserRepo->shouldReceive('setAlliance')->with($targetUserId, $allianceId, 60);
        $this->mockAppRepo->shouldReceive('deleteByUser')->with($targetUserId);

        $response = $this->service->acceptApplication($adminId, $appId);
        $this->assertServiceSuccess($response);
    }

    // 9. Reject Application
    public function testRejectApplication_Success(): void
    {
        $adminId = 1;
        $appId = 5;
        $allianceId = 10;

        $app = new AllianceApplication(id: $appId, user_id: 2, alliance_id: $allianceId, created_at: 'now');
        $admin = $this->mockUser(id: $adminId, alliance_id: $allianceId, alliance_role_id: 50);
        $adminRole = $this->mockRole(id: 50, perms: ['can_manage_applications' => true]);

        $this->mockAppRepo->shouldReceive('findById')->with($appId)->andReturn($app);
        $this->mockUserRepo->shouldReceive('findById')->with($adminId)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with(50)->andReturn($adminRole);
        $this->mockAppRepo->shouldReceive('delete')->with($appId);

        $response = $this->service->rejectApplication($adminId, $appId);
        $this->assertServiceSuccess($response);
    }

    // 10. Invite User
    public function testInviteUser_Success(): void
    {
        $inviterId = 1;
        $targetId = 2;
        $allianceId = 10;

        $inviter = $this->mockUser(id: $inviterId, alliance_id: $allianceId, alliance_role_id: 50);
        $inviterRole = $this->mockRole(id: 50, perms: ['can_invite_members' => true]);
        $target = $this->mockUser(id: $targetId, alliance_id: null);
        $recruitRole = $this->mockRole(id: 60, name: 'Recruit');

        $this->mockUserRepo->shouldReceive('findById')->with($inviterId)->andReturn($inviter);
        $this->mockRoleRepo->shouldReceive('findById')->with(50)->andReturn($inviterRole);
        $this->mockUserRepo->shouldReceive('findById')->with($targetId)->andReturn($target);
        $this->mockRoleRepo->shouldReceive('findDefaultRole')->with($allianceId, 'Recruit')->andReturn($recruitRole);
        
        $this->mockUserRepo->shouldReceive('setAlliance')->with($targetId, $allianceId, 60);
        $this->mockAppRepo->shouldReceive('deleteByUser')->with($targetId);

        $response = $this->service->inviteUser($inviterId, $targetId);
        $this->assertServiceSuccess($response);
    }

    // 11. Kick Member
    public function testKickMember_Success(): void
    {
        $adminId = 1;
        $targetId = 2;
        
        $admin = $this->mockUser(id: $adminId, alliance_id: 10, alliance_role_id: 50);
        $target = $this->mockUser(id: $targetId, alliance_id: 10, alliance_role_id: 60);
        
        $this->mockUserRepo->shouldReceive('findById')->with($adminId)->andReturn($admin);
        $this->mockUserRepo->shouldReceive('findById')->with($targetId)->andReturn($target);
        
        $this->mockRoleRepo->shouldReceive('findById')->with(50)->andReturn($this->mockRole(50));
        $this->mockRoleRepo->shouldReceive('findById')->with(60)->andReturn($this->mockRole(60));
        
        $this->mockPolicyService->shouldReceive('canKick')->andReturnNull(); // Null means authorized
        
        $this->mockUserRepo->shouldReceive('leaveAlliance')->with($targetId);

        $response = $this->service->kickMember($adminId, $targetId);
        $this->assertServiceSuccess($response);
    }

    // 12. Change Member Role
    public function testChangeMemberRole_Success(): void
    {
        $adminId = 1;
        $targetId = 2;
        $newRoleId = 70;

        $admin = $this->mockUser(id: $adminId, alliance_id: 10, alliance_role_id: 50);
        $target = $this->mockUser(id: $targetId, alliance_id: 10, alliance_role_id: 60);
        $newRole = $this->mockRole(id: $newRoleId, name: 'Officer');

        $this->mockUserRepo->shouldReceive('findById')->with($adminId)->andReturn($admin);
        $this->mockUserRepo->shouldReceive('findById')->with($targetId)->andReturn($target);
        $this->mockRoleRepo->shouldReceive('findById')->with(50)->andReturn($this->mockRole(50));
        $this->mockRoleRepo->shouldReceive('findById')->with(60)->andReturn($this->mockRole(60));
        $this->mockRoleRepo->shouldReceive('findById')->with($newRoleId)->andReturn($newRole);

        $this->mockPolicyService->shouldReceive('canAssignRole')->andReturnNull();

        $this->mockUserRepo->shouldReceive('setAllianceRole')->with($targetId, $newRoleId);

        $response = $this->service->changeMemberRole($adminId, $targetId, $newRoleId);
        $this->assertServiceSuccess($response);
    }

    // 13. Create Role
    public function testCreateRole_Success(): void
    {
        $adminId = 1;
        $allianceId = 10;
        
        $admin = $this->mockUser(id: $adminId, alliance_id: $allianceId, alliance_role_id: 50);
        $adminRole = $this->mockRole(id: 50, perms: ['can_manage_roles' => true]);

        $this->mockUserRepo->shouldReceive('findById')->with($adminId)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with(50)->andReturn($adminRole);
        
        $this->mockRoleRepo->shouldReceive('create')->with($allianceId, 'NewRole', 100, 0);

        $response = $this->service->createRole($adminId, $allianceId, 'NewRole', []);
        $this->assertServiceSuccess($response);
    }

    // 14. Update Role
    public function testUpdateRole_Success(): void
    {
        $adminId = 1;
        $roleId = 80;
        
        $role = $this->mockRole(id: $roleId, alliance_id: 10, name: 'CustomRole');
        $admin = $this->mockUser(id: $adminId, alliance_id: 10, alliance_role_id: 50);
        $adminRole = $this->mockRole(id: 50, perms: ['can_manage_roles' => true]);

        $this->mockRoleRepo->shouldReceive('findById')->with($roleId)->andReturn($role);
        $this->mockUserRepo->shouldReceive('findById')->with($adminId)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with(50)->andReturn($adminRole);
        
        $this->mockRoleRepo->shouldReceive('update')->with($roleId, 'UpdatedName', 0);

        $response = $this->service->updateRole($adminId, $roleId, 'UpdatedName', []);
        $this->assertServiceSuccess($response);
    }

    // 15. Delete Role
    public function testDeleteRole_Success(): void
    {
        $adminId = 1;
        $roleId = 80;
        
        $role = $this->mockRole(id: $roleId, alliance_id: 10, name: 'CustomRole');
        $recruitRole = $this->mockRole(id: 60, alliance_id: 10, name: 'Recruit');
        $admin = $this->mockUser(id: $adminId, alliance_id: 10, alliance_role_id: 50);
        $adminRole = $this->mockRole(id: 50, perms: ['can_manage_roles' => true]);

        $this->mockRoleRepo->shouldReceive('findById')->with($roleId)->andReturn($role);
        $this->mockUserRepo->shouldReceive('findById')->with($adminId)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with(50)->andReturn($adminRole);
        $this->mockRoleRepo->shouldReceive('findDefaultRole')->with(10, 'Recruit')->andReturn($recruitRole);
        
        $this->mockRoleRepo->shouldReceive('reassignRoleMembers')->with($roleId, 60);
        $this->mockRoleRepo->shouldReceive('delete')->with($roleId);

        $response = $this->service->deleteRole($adminId, $roleId);
        $this->assertServiceSuccess($response);
    }

    // 16. Donate to Alliance
    public function testDonateToAlliance_Success(): void
    {
        $userId = 1;
        $amount = 1000;
        
        $user = $this->mockUser(id: $userId, alliance_id: 10);
        $resources = $this->mockResource(credits: 5000);

        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($user);
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($userId)->andReturn($resources);
        
        $this->mockResourceRepo->shouldReceive('updateCredits')->with($userId, 4000);
        $this->mockAllianceRepo->shouldReceive('updateBankCreditsRelative')->with(10, 1000);
        $this->mockBankLogRepo->shouldReceive('createLog');

        $response = $this->service->donateToAlliance($userId, $amount);
        $this->assertServiceSuccess($response);
    }

    // 17. Request Loan
    public function testRequestLoan_Success(): void
    {
        $userId = 1;
        $user = $this->mockUser(id: $userId, alliance_id: 10);
        
        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($user);
        $this->mockLoanRepo->shouldReceive('createLoanRequest')->with(10, $userId, 500);

        $response = $this->service->requestLoan($userId, 500);
        $this->assertServiceSuccess($response);
    }

    // 18. Approve Loan
    public function testApproveLoan_Success(): void
    {
        $adminId = 1;
        $loanId = 100;
        $borrowerId = 2;
        $allianceId = 10;
        
        $loan = new AllianceLoan(
            id: $loanId, 
            alliance_id: $allianceId, 
            user_id: $borrowerId, 
            amount_requested: 500, 
            amount_to_repay: 550, 
            status: 'pending', 
            created_at: 'now',
            updated_at: 'now'
        );
        
        $admin = $this->mockUser(id: $adminId, alliance_id: $allianceId, alliance_role_id: 50);
        $adminRole = $this->mockRole(id: 50, perms: ['can_manage_bank' => true]);
        $alliance = $this->mockAlliance(id: $allianceId, bank_credits: 10000);
        $borrower = $this->mockUser(id: $borrowerId);
        $borrowerResources = $this->mockResource(credits: 100);

        $this->mockLoanRepo->shouldReceive('findById')->with($loanId)->andReturn($loan);
        $this->mockUserRepo->shouldReceive('findById')->with($adminId)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with(50)->andReturn($adminRole);
        $this->mockAllianceRepo->shouldReceive('findById')->with($allianceId)->andReturn($alliance);
        $this->mockUserRepo->shouldReceive('findById')->with($borrowerId)->andReturn($borrower);
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($borrowerId)->andReturn($borrowerResources);
        
        // Actions
        $this->mockAllianceRepo->shouldReceive('updateBankCreditsRelative')->with($allianceId, -500);
        $this->mockResourceRepo->shouldReceive('updateCredits')->with($borrowerId, 600);
        $this->mockBankLogRepo->shouldReceive('createLog');
        $this->mockLoanRepo->shouldReceive('updateLoan')->with($loanId, 'active', 550);

        $response = $this->service->approveLoan($adminId, $loanId);
        $this->assertServiceSuccess($response);
    }

    // 19. Deny Loan
    public function testDenyLoan_Success(): void
    {
        $adminId = 1;
        $loanId = 100;
        
        $loan = new AllianceLoan(
            id: $loanId, 
            alliance_id: 10, 
            user_id: 2, 
            amount_requested: 500, 
            amount_to_repay: 550, 
            status: 'pending', 
            created_at: 'now',
            updated_at: 'now'
        );

        $admin = $this->mockUser(id: $adminId, alliance_id: 10, alliance_role_id: 50);
        $adminRole = $this->mockRole(id: 50, perms: ['can_manage_bank' => true]);

        $this->mockLoanRepo->shouldReceive('findById')->with($loanId)->andReturn($loan);
        $this->mockUserRepo->shouldReceive('findById')->with($adminId)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with(50)->andReturn($adminRole);
        
        $this->mockLoanRepo->shouldReceive('updateLoan')->with($loanId, 'denied', 0);

        $response = $this->service->denyLoan($adminId, $loanId);
        $this->assertServiceSuccess($response);
    }

    // 20. Repay Loan
    public function testRepayLoan_Success(): void
    {
        $userId = 2;
        $loanId = 100;
        
        $loan = new AllianceLoan(
            id: $loanId, 
            alliance_id: 10, 
            user_id: $userId, 
            amount_requested: 500, 
            amount_to_repay: 550, 
            status: 'active', 
            created_at: 'now',
            updated_at: 'now'
        );

        $user = $this->mockUser(id: $userId, alliance_id: 10);
        $resources = $this->mockResource(credits: 1000);
        
        $this->mockLoanRepo->shouldReceive('findById')->with($loanId)->andReturn($loan);
        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($user);
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($userId)->andReturn($resources);
        
        // Full repayment
        $this->mockResourceRepo->shouldReceive('updateCredits')->with($userId, 450); // 1000 - 550
        $this->mockAllianceRepo->shouldReceive('updateBankCreditsRelative')->with(10, 550);
        $this->mockBankLogRepo->shouldReceive('createLog');
        $this->mockLoanRepo->shouldReceive('updateLoan')->with($loanId, 'paid', 0);

        $response = $this->service->repayLoan($userId, $loanId, 550);
        $this->assertServiceSuccess($response);
    }

    // 21. Policy Deny Check (Kick)
    public function testKickMember_Fail_PolicyDeny(): void
    {
        $adminId = 1;
        $targetId = 2;
        
        $admin = $this->mockUser(id: $adminId, alliance_id: 10, alliance_role_id: 50);
        $target = $this->mockUser(id: $targetId, alliance_id: 10, alliance_role_id: 60);
        
        $this->mockUserRepo->shouldReceive('findById')->andReturn($admin, $target);
        $this->mockRoleRepo->shouldReceive('findById')->andReturn($this->mockRole(50), $this->mockRole(60));
        
        $this->mockPolicyService->shouldReceive('canKick')->andReturn('Cannot kick this user');
        
        $response = $this->service->kickMember($adminId, $targetId);
        $this->assertServiceFailure($response, 'Cannot kick this user');
    }

    // 22. Donate Fail
    public function testDonateToAlliance_Fail_InsufficientCredits(): void
    {
        $userId = 1;
        $resources = $this->mockResource(credits: 50);
        
        $this->mockUserRepo->shouldReceive('findById')->andReturn($this->mockUser(id: 1, alliance_id: 10));
        $this->mockResourceRepo->shouldReceive('findByUserId')->andReturn($resources);
        
        $response = $this->service->donateToAlliance($userId, 100);
        $this->assertServiceFailure($response, 'enough credits');
    }

    // 23. Accept App Permission Fail
    public function testAcceptApplication_Fail_NoPermission(): void
    {
        $adminId = 1;
        $appId = 5;
        $allianceId = 10;
        
        $app = new AllianceApplication(id: 5, user_id: 2, alliance_id: 10, created_at: 'now');
        $admin = $this->mockUser(id: 1, alliance_id: 10, alliance_role_id: 50);
        $role = $this->mockRole(id: 50, perms: ['can_manage_applications' => false]);
        
        $this->mockAppRepo->shouldReceive('findById')->andReturn($app);
        $this->mockUserRepo->shouldReceive('findById')->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->andReturn($role);
        
        $response = $this->service->acceptApplication($adminId, $appId);
        $this->assertServiceFailure($response, 'do not have permission');
    }

    // --- Transaction Failure Tests ---

    public function testCreateAlliance_Fail_TransactionError(): void
    {
        $userId = 1;
        $cost = 50000;
        $user = $this->mockUser(id: $userId, alliance_id: null);
        $resources = $this->mockResource(credits: 100000);

        $this->mockAllianceRepo->shouldReceive('findByName')->andReturnNull();
        $this->mockAllianceRepo->shouldReceive('findByTag')->andReturnNull();
        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($user);
        $this->mockConfig->shouldReceive('get')->andReturn($cost);
        $this->mockResourceRepo->shouldReceive('findByUserId')->andReturn($resources);

        // Force Exception
        $this->mockAllianceRepo->shouldReceive('create')->andThrow(new \Exception('DB Error'));
        $this->mockPdo->shouldReceive('rollBack')->once();
        
        // Expect Log
        $this->mockLogger->shouldReceive('error')->once()->with(Mockery::pattern('/Alliance Creation Error: DB Error/'));

        $response = $this->service->createAlliance($userId, 'Empire', 'EMP');
        $this->assertServiceFailure($response, 'database error');
    }

    public function testAcceptApplication_Fail_TransactionError(): void
    {
        $adminId = 1;
        $appId = 5;
        $targetUserId = 2;
        $allianceId = 10;

        $app = new AllianceApplication(id: $appId, user_id: $targetUserId, alliance_id: $allianceId, created_at: 'now');
        $admin = $this->mockUser(id: $adminId, alliance_id: $allianceId, alliance_role_id: 50);
        $adminRole = $this->mockRole(id: 50, perms: ['can_manage_applications' => true]);
        $targetUser = $this->mockUser(id: $targetUserId, alliance_id: null);
        $recruitRole = $this->mockRole(id: 60, name: 'Recruit');

        $this->mockAppRepo->shouldReceive('findById')->andReturn($app);
        $this->mockUserRepo->shouldReceive('findById')->with($adminId)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with(50)->andReturn($adminRole);
        $this->mockUserRepo->shouldReceive('findById')->with($targetUserId)->andReturn($targetUser);
        $this->mockRoleRepo->shouldReceive('findDefaultRole')->andReturn($recruitRole);

        // Force Exception
        $this->mockUserRepo->shouldReceive('setAlliance')->andThrow(new \Exception('DB Error'));
        $this->mockPdo->shouldReceive('rollBack')->once();
        
        // Expect Log
        $this->mockLogger->shouldReceive('error')->once()->with(Mockery::pattern('/Accept Application Error: DB Error/'));

        $response = $this->service->acceptApplication($adminId, $appId);
        $this->assertServiceFailure($response, 'database error');
    }

    public function testInviteUser_Fail_TransactionError(): void
    {
        $inviterId = 1;
        $targetId = 2;
        $allianceId = 10;

        $inviter = $this->mockUser(id: $inviterId, alliance_id: $allianceId, alliance_role_id: 50);
        $inviterRole = $this->mockRole(id: 50, perms: ['can_invite_members' => true]);
        $target = $this->mockUser(id: $targetId, alliance_id: null);
        $recruitRole = $this->mockRole(id: 60, name: 'Recruit');

        $this->mockUserRepo->shouldReceive('findById')->with($inviterId)->andReturn($inviter);
        $this->mockRoleRepo->shouldReceive('findById')->with(50)->andReturn($inviterRole);
        $this->mockUserRepo->shouldReceive('findById')->with($targetId)->andReturn($target);
        $this->mockRoleRepo->shouldReceive('findDefaultRole')->andReturn($recruitRole);

        // Force Exception
        $this->mockUserRepo->shouldReceive('setAlliance')->andThrow(new \Exception('DB Error'));
        $this->mockPdo->shouldReceive('rollBack')->once();
        
        // Expect Log
        $this->mockLogger->shouldReceive('error')->once()->with(Mockery::pattern('/Invite User Error: DB Error/'));

        $response = $this->service->inviteUser($inviterId, $targetId);
        $this->assertServiceFailure($response, 'database error');
    }

    public function testDeleteRole_Fail_TransactionError(): void
    {
        $adminId = 1;
        $roleId = 80;
        
        $role = $this->mockRole(id: $roleId, alliance_id: 10, name: 'CustomRole');
        $recruitRole = $this->mockRole(id: 60, alliance_id: 10, name: 'Recruit');
        $admin = $this->mockUser(id: $adminId, alliance_id: 10, alliance_role_id: 50);
        $adminRole = $this->mockRole(id: 50, perms: ['can_manage_roles' => true]);

        $this->mockRoleRepo->shouldReceive('findById')->with($roleId)->andReturn($role);
        $this->mockUserRepo->shouldReceive('findById')->with($adminId)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->with(50)->andReturn($adminRole);
        $this->mockRoleRepo->shouldReceive('findDefaultRole')->andReturn($recruitRole);
        
        // Force Exception
        $this->mockRoleRepo->shouldReceive('reassignRoleMembers')->andThrow(new \Exception('DB Error'));
        $this->mockPdo->shouldReceive('rollBack')->once();
        
        // Expect Log
        $this->mockLogger->shouldReceive('error')->once()->with(Mockery::pattern('/Delete Role Error: DB Error/'));

        $response = $this->service->deleteRole($adminId, $roleId);
        $this->assertServiceFailure($response, 'database error');
    }

    public function testDonateToAlliance_Fail_TransactionError(): void
    {
        $userId = 1;
        $amount = 1000;
        $user = $this->mockUser(id: $userId, alliance_id: 10);
        $resources = $this->mockResource(credits: 5000);

        $this->mockUserRepo->shouldReceive('findById')->andReturn($user);
        $this->mockResourceRepo->shouldReceive('findByUserId')->andReturn($resources);
        
        // Force Exception
        $this->mockResourceRepo->shouldReceive('updateCredits')->andThrow(new \Exception('DB Error'));
        $this->mockPdo->shouldReceive('rollBack')->once();
        
        // Expect Log
        $this->mockLogger->shouldReceive('error')->once()->with(Mockery::pattern('/Alliance Donation Error: DB Error/'));

        $response = $this->service->donateToAlliance($userId, $amount);
        $this->assertServiceFailure($response, 'database error');
    }

    public function testApproveLoan_Fail_TransactionError(): void
    {
        $adminId = 1;
        $loanId = 100;
        $borrowerId = 2;
        $allianceId = 10;
        
        $loan = new AllianceLoan(
            id: $loanId, 
            alliance_id: $allianceId, 
            user_id: $borrowerId, 
            amount_requested: 500, 
            amount_to_repay: 550, 
            status: 'pending', 
            created_at: 'now',
            updated_at: 'now'
        );
        $admin = $this->mockUser(id: $adminId, alliance_id: $allianceId, alliance_role_id: 50);
        $adminRole = $this->mockRole(id: 50, perms: ['can_manage_bank' => true]);
        $alliance = $this->mockAlliance(id: $allianceId, bank_credits: 10000);
        $borrower = $this->mockUser(id: $borrowerId);
        $borrowerResources = $this->mockResource(credits: 100);

        $this->mockLoanRepo->shouldReceive('findById')->andReturn($loan);
        $this->mockUserRepo->shouldReceive('findById')->with($adminId)->andReturn($admin);
        $this->mockRoleRepo->shouldReceive('findById')->andReturn($adminRole);
        $this->mockAllianceRepo->shouldReceive('findById')->andReturn($alliance);
        $this->mockUserRepo->shouldReceive('findById')->with($borrowerId)->andReturn($borrower);
        $this->mockResourceRepo->shouldReceive('findByUserId')->andReturn($borrowerResources);
        
        // Force Exception
        $this->mockAllianceRepo->shouldReceive('updateBankCreditsRelative')->andThrow(new \Exception('DB Error'));
        $this->mockPdo->shouldReceive('rollBack')->once();
        
        // Expect Log
        $this->mockLogger->shouldReceive('error')->once()->with(Mockery::pattern('/Alliance Loan Approve Error: DB Error/'));

        $response = $this->service->approveLoan($adminId, $loanId);
        $this->assertServiceFailure($response, 'database error');
    }

    public function testRepayLoan_Fail_TransactionError(): void
    {
        $userId = 2;
        $loanId = 100;
        
        $loan = new AllianceLoan(
            id: $loanId, 
            alliance_id: 10, 
            user_id: $userId, 
            amount_requested: 500, 
            amount_to_repay: 550, 
            status: 'active', 
            created_at: 'now',
            updated_at: 'now'
        );
        $user = $this->mockUser(id: $userId, alliance_id: 10);
        $resources = $this->mockResource(credits: 1000);
        
        $this->mockLoanRepo->shouldReceive('findById')->andReturn($loan);
        $this->mockUserRepo->shouldReceive('findById')->andReturn($user);
        $this->mockResourceRepo->shouldReceive('findByUserId')->andReturn($resources);
        
        // Force Exception
        $this->mockResourceRepo->shouldReceive('updateCredits')->andThrow(new \Exception('DB Error'));
        $this->mockPdo->shouldReceive('rollBack')->once();
        
        // Expect Log
        $this->mockLogger->shouldReceive('error')->once()->with(Mockery::pattern('/Alliance Loan Repay Error: DB Error/'));

        $response = $this->service->repayLoan($userId, $loanId, 550);
        $this->assertServiceFailure($response, 'database error');
    }

    // --- Helpers ---
    private function mockUser(int $id, ?int $alliance_id = null, ?int $alliance_role_id = null): User
    {
        return new User(
            id: $id,
            email: 'test@example.com',
            characterName: 'User'.$id,
            bio: null,
            profile_picture_url: null,
            phone_number: null,
            alliance_id: $alliance_id,
            alliance_role_id: $alliance_role_id,
            passwordHash: 'hash',
            createdAt: 'now',
            is_npc: false
        );
    }

// ... (inside the class)

    private function mockRole(int $id, int $alliance_id = 10, string $name = 'Role', array $perms = []): AllianceRole
    {
        $permissionsMask = 0;
        foreach ($perms as $permName => $value) {
            if ($value) {
                $constName = strtoupper($permName);
                if (defined(Permissions::class . '::' . $constName)) {
                    $permissionsMask |= constant(Permissions::class . '::' . $constName);
                }
            }
        }
        
        $role = new AllianceRole(
            id: $id,
            alliance_id: $alliance_id,
            name: $name,
            sort_order: 50,
            permissions: $permissionsMask
        );
        return $role;
    }

    private function mockResource(int $credits = 0): UserResource
    {
        return new UserResource(
            user_id: 1,
            credits: $credits,
            banked_credits: 0,
            gemstones: 0,
            naquadah_crystals: 0.0,
            untrained_citizens: 0,
            workers: 0,
            soldiers: 0,
            guards: 0,
            spies: 0,
            sentries: 0
        );
    }
    
    private function mockAlliance(int $id, int $bank_credits = 0): Alliance
    {
        return new Alliance(
            id: $id,
            name: 'Alliance'.$id,
            tag: 'TAG',
            description: 'Desc',
            profile_picture_url: null,
            is_joinable: false,
            leader_id: 1,
            net_worth: 0,
            bank_credits: $bank_credits,
            last_compound_at: null,
            created_at: 'now'
        );
    }
}