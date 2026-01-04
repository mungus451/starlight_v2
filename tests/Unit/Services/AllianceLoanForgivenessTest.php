<?php

namespace Tests\Unit\Services;

use App\Core\Config;
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
use App\Core\Logger;
use App\Models\Entities\AllianceLoan;
use App\Models\Entities\Alliance;
use App\Models\Entities\User;
use Tests\Unit\TestCase;
use Mockery;
use PDO;

class AllianceLoanForgivenessTest extends TestCase
{
    private $db;
    private $config;
    private $allianceRepo;
    private $userRepo;
    private $appRepo;
    private $roleRepo;
    private $policyService;
    private $resourceRepo;
    private $bankLogRepo;
    private $loanRepo;
    private $logger;
    private $notificationService;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->db = Mockery::mock(PDO::class);
        $this->config = Mockery::mock(Config::class);
        $this->allianceRepo = Mockery::mock(AllianceRepository::class);
        $this->userRepo = Mockery::mock(UserRepository::class);
        $this->appRepo = Mockery::mock(ApplicationRepository::class);
        $this->roleRepo = Mockery::mock(AllianceRoleRepository::class);
        $this->policyService = Mockery::mock(AlliancePolicyService::class);
        $this->resourceRepo = Mockery::mock(ResourceRepository::class);
        $this->bankLogRepo = Mockery::mock(AllianceBankLogRepository::class);
        $this->loanRepo = Mockery::mock(AllianceLoanRepository::class);
        $this->logger = Mockery::mock(Logger::class);
        $this->notificationService = Mockery::mock(NotificationService::class);

        $this->service = new AllianceManagementService(
            $this->db,
            $this->config,
            $this->allianceRepo,
            $this->userRepo,
            $this->appRepo,
            $this->roleRepo,
            $this->policyService,
            $this->resourceRepo,
            $this->bankLogRepo,
            $this->loanRepo,
            $this->logger,
            $this->notificationService
        );
    }

    public function testForgiveLoanSuccess(): void
    {
        $adminId = 1;
        $loanId = 100;
        $allianceId = 10;
        $borrowerId = 2;
        
        // Mock Loan
        $loan = new AllianceLoan(
            id: $loanId,
            alliance_id: $allianceId,
            user_id: $borrowerId,
            amount_requested: 500,
            amount_to_repay: 500,
            status: 'active',
            created_at: '2024-01-01',
            updated_at: '2024-01-01',
            character_name: 'Borrower'
        );
        
        // Mock Alliance (Leader check)
        $alliance = new Alliance(
            id: $allianceId,
            name: 'Test',
            tag: 'TEST',
            description: '',
            profile_picture_url: '',
            is_joinable: true,
            leader_id: $adminId, // Admin is leader
            net_worth: 0,
            bank_credits: 1000,
            last_compound_at: null,
            created_at: '2024-01-01'
        );
        
        // Mock Borrower
        $borrower = new User(
            id: $borrowerId,
            email: 'borrower@test.com',
            characterName: 'Borrower',
            bio: null,
            profile_picture_url: null,
            phone_number: null,
            alliance_id: $allianceId,
            alliance_role_id: 2,
            passwordHash: 'hash',
            createdAt: '2024-01-01',
            is_npc: false
        );

        $this->loanRepo->shouldReceive('findById')->with($loanId)->andReturn($loan);
        $this->allianceRepo->shouldReceive('findById')->with($allianceId)->andReturn($alliance);
        $this->userRepo->shouldReceive('findById')->with($borrowerId)->andReturn($borrower);
        
        // Expectations
        $this->db->shouldReceive('beginTransaction')->once();
        $this->loanRepo->shouldReceive('updateLoan')->with($loanId, 'paid', 0)->once();
        $this->bankLogRepo->shouldReceive('createLog')
            ->with($allianceId, $adminId, 'loan_forgiveness', 0, Mockery::type('string'))
            ->once();
        $this->db->shouldReceive('commit')->once();

        $response = $this->service->forgiveLoan($adminId, $loanId);
        
        $this->assertServiceSuccess($response);
        $this->assertServiceMessageContains($response, 'has been forgiven');
    }

    public function testForgiveLoanFailsIfNotLeader(): void
    {
        $adminId = 1;
        $loanId = 100;
        $allianceId = 10;
        $realLeaderId = 999;
        
        $loan = new AllianceLoan(
            id: $loanId,
            alliance_id: $allianceId,
            user_id: 2,
            amount_requested: 500,
            amount_to_repay: 500,
            status: 'active',
            created_at: '2024-01-01',
            updated_at: '2024-01-01',
            character_name: 'Borrower'
        );
        
        $alliance = new Alliance(
            id: $allianceId,
            name: 'Test',
            tag: 'TEST',
            description: '',
            profile_picture_url: '',
            is_joinable: true,
            leader_id: $realLeaderId, // Admin is NOT leader
            net_worth: 0,
            bank_credits: 1000,
            last_compound_at: null,
            created_at: '2024-01-01'
        );

        $this->loanRepo->shouldReceive('findById')->with($loanId)->andReturn($loan);
        $this->allianceRepo->shouldReceive('findById')->with($allianceId)->andReturn($alliance);
        
        $response = $this->service->forgiveLoan($adminId, $loanId);
        
        $this->assertServiceFailure($response);
        $this->assertServiceMessageContains($response, 'Only the Alliance Leader');
    }

    public function testForgiveLoanFailsIfNotActive(): void
    {
        $adminId = 1;
        $loanId = 100;
        $allianceId = 10;
        
        $loan = new AllianceLoan(
            id: $loanId,
            alliance_id: $allianceId,
            user_id: 2,
            amount_requested: 500,
            amount_to_repay: 500,
            status: 'pending', // Not active
            created_at: '2024-01-01',
            updated_at: '2024-01-01',
            character_name: 'Borrower'
        );
        
        $alliance = new Alliance(
            id: $allianceId,
            name: 'Test',
            tag: 'TEST',
            description: '',
            profile_picture_url: '',
            is_joinable: true,
            leader_id: $adminId,
            net_worth: 0,
            bank_credits: 1000,
            last_compound_at: null,
            created_at: '2024-01-01'
        );

        $this->loanRepo->shouldReceive('findById')->with($loanId)->andReturn($loan);
        $this->allianceRepo->shouldReceive('findById')->with($allianceId)->andReturn($alliance);
        
        $response = $this->service->forgiveLoan($adminId, $loanId);
        
        $this->assertServiceFailure($response);
        $this->assertServiceMessageContains($response, 'not active');
    }
}
