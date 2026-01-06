<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\TurnProcessorService;
use App\Models\Services\PowerCalculatorService;
use App\Core\Config;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceBankLogRepository;
use App\Models\Repositories\GeneralRepository;
use App\Models\Repositories\ScientistRepository;
use App\Models\Repositories\EdictRepository;
use App\Models\Services\EmbassyService;
use App\Models\Services\NetWorthCalculatorService; // NEW
use App\Models\Entities\User;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStats;
use App\Models\Entities\UserStructure;
use App\Models\Entities\Alliance;
use Mockery;
use PDO;

class TurnProcessorServiceTest extends TestCase
{
    private TurnProcessorService $service;
    private PDO|Mockery\MockInterface $mockDb;
    private Config|Mockery\MockInterface $mockConfig;
    private UserRepository|Mockery\MockInterface $mockUserRepo;
    private ResourceRepository|Mockery\MockInterface $mockResourceRepo;
    private StructureRepository|Mockery\MockInterface $mockStructureRepo;
    private StatsRepository|Mockery\MockInterface $mockStatsRepo;
    private PowerCalculatorService|Mockery\MockInterface $mockPowerCalc;
    private AllianceRepository|Mockery\MockInterface $mockAllianceRepo;
    private AllianceBankLogRepository|Mockery\MockInterface $mockBankLogRepo;
    private GeneralRepository|Mockery\MockInterface $mockGeneralRepo;
    private ScientistRepository|Mockery\MockInterface $mockScientistRepo;
    private EdictRepository|Mockery\MockInterface $mockEdictRepo;
    private EmbassyService|Mockery\MockInterface $mockEmbassyService;
    private NetWorthCalculatorService|Mockery\MockInterface $mockNwCalculator; // NEW

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockDb = Mockery::mock(PDO::class);
        $this->mockConfig = Mockery::mock(Config::class);
        $this->mockUserRepo = Mockery::mock(UserRepository::class);
        $this->mockResourceRepo = Mockery::mock(ResourceRepository::class);
        $this->mockStructureRepo = Mockery::mock(StructureRepository::class);
        $this->mockStatsRepo = Mockery::mock(StatsRepository::class);
        $this->mockPowerCalc = Mockery::mock(PowerCalculatorService::class);
        $this->mockAllianceRepo = Mockery::mock(AllianceRepository::class);
        $this->mockBankLogRepo = Mockery::mock(AllianceBankLogRepository::class);
        $this->mockGeneralRepo = Mockery::mock(GeneralRepository::class);
        $this->mockScientistRepo = Mockery::mock(ScientistRepository::class);
        $this->mockEdictRepo = Mockery::mock(EdictRepository::class);
        $this->mockEmbassyService = Mockery::mock(EmbassyService::class);
        $this->mockNwCalculator = Mockery::mock(NetWorthCalculatorService::class); // NEW

        // Pre-load config in constructor
        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.alliance_treasury', [])
            ->andReturn(['bank_interest_rate' => 0.05]); // 5% Interest
            
        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.upkeep', [])
            ->andReturn([]);

        $this->service = new TurnProcessorService(
            $this->mockDb,
            $this->mockConfig,
            $this->mockUserRepo,
            $this->mockResourceRepo,
            $this->mockStructureRepo,
            $this->mockStatsRepo,
            $this->mockPowerCalc,
            $this->mockAllianceRepo,
            $this->mockBankLogRepo,
            $this->mockGeneralRepo,
            $this->mockScientistRepo,
            $this->mockEdictRepo,
            $this->mockEmbassyService,
            $this->mockNwCalculator // New dependency, cast to type
        );
    }

    public function testProcessAllUsersRunsSuccessfully(): void
    {
        $userId = 1;
        $allianceId = 10;
        
        // 1. Mock Fetching User IDs
        $this->mockUserRepo->shouldReceive('getAllUserIds')
            ->once()
            ->andReturn([$userId]);

        // 2. Mock Transaction Start (Per User)
        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction')->once();
        $this->mockDb->shouldReceive('commit')->once();

        // 3. Mock Entity Fetching
        $mockUser = $this->createMockUser($userId, $allianceId);
        $mockRes = $this->createMockResource($userId);
        $mockStruct = $this->createMockStructure($userId);
        $mockStats = $this->createMockStats($userId);

        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($mockUser);
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($userId)->andReturn($mockRes);
        $this->mockStructureRepo->shouldReceive('findByUserId')->with($userId)->andReturn($mockStruct);
        $this->mockStatsRepo->shouldReceive('findByUserId')->with($userId)->andReturn($mockStats);

                    // 4. Mock Calculations
                    $this->mockPowerCalc->shouldReceive('calculateIncomePerTurn')
                        ->once()
                        ->with($userId, $mockRes, $mockStats, $mockStruct, $allianceId)
                        ->andReturn([
                            'total_credit_income' => 1000,
                            'interest' => 50,
                            'total_citizens' => 10,
                            'research_data_income' => 20,
                            'dark_matter_income' => 5.5,
                            'naquadah_income' => 0.0,
                            'protoform_income' => 0.0 // Added for new parameter
                        ]);
        
                    // 5. Mock Updates
                    $this->mockResourceRepo->shouldReceive('applyTurnIncome')
                        ->once()
                        ->with($userId, 1000, 50, 10, 20, 5.5, 0.0, 0.0); // Added for new parameter
        
                    $this->mockStatsRepo->shouldReceive('applyTurnAttackTurn')
                        ->once()
                        ->with($userId, 1);

                    // NEW: Expect Net Worth calculation and update
                    $this->mockNwCalculator->shouldReceive('calculateTotalNetWorth')
                        ->once()
                        ->with($userId)
                        ->andReturn(5000000); // Arbitrary new net worth

                    $this->mockStatsRepo->shouldReceive('updateNetWorth')
                        ->once()
                        ->with($userId, 5000000);
        
                    $this->mockGeneralRepo->shouldReceive('countByUserId')
                        ->once()
                        ->with($userId)
                        ->andReturn(0);
        
                                $this->mockScientistRepo->shouldReceive('getActiveScientistCount')
        
                                    ->once()
        
                                    ->with($userId)
        
                                    ->andReturn(0);
        
                    
        
                                $this->mockEdictRepo->shouldReceive('findActiveByUserId')
        
                                    ->once()
        
                                    ->with($userId)
        
                                    ->andReturn([]);
        
                    
        
                                // 6. Mock Alliances (Empty for this test to focus on users)
        
                    
                    $this->mockAllianceRepo->shouldReceive('getAllAlliances')
                        ->once()
                        ->andReturn([]);
                        // Execute
                $result = $this->service->processAllUsers();
        
                $this->assertEquals(1, $result['users']);
            }
        
            public function testProcessAllAlliancesCalculatesInterest(): void
            {
                // 1. Mock No Users
                $this->mockUserRepo->shouldReceive('getAllUserIds')->andReturn([]);
        
                // 2. Mock Alliances
                $allianceId = 5;
                $mockAlliance = new Alliance(
                    id: $allianceId,
                    name: 'Test Alliance',
                    tag: 'TEST',
                    description: null,
                    profile_picture_url: null,
                    is_joinable: true,
                    leader_id: 1,
                    net_worth: 0,
                    bank_credits: 100000, // Should earn 5000 interest (5%)
                    last_compound_at: null,
                    created_at: '2024-01-01'
                );
        
        $this->mockAllianceRepo->shouldReceive('getAllAlliances')
            ->once()
            ->andReturn([$mockAlliance]);

        // Fix: Add expectation for Net Worth Aggregation
        $this->mockUserRepo->shouldReceive('sumNetWorthByAllianceId')
            ->with(5)
            ->once()
            ->andReturn(100000); // Mock total net worth
            
        $this->mockAllianceRepo->shouldReceive('updateNetWorth')
            ->with(5, 100000)
            ->once()
            ->andReturn(true);

        // Transaction expectations
        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction')->once();
        $this->mockDb->shouldReceive('commit')->once();

        $this->mockAllianceRepo->shouldReceive('updateBankCreditsRelative')
            ->with(5, 5000) // 5% of 100,000
            ->once()
            ->andReturn(true);
        
                $this->mockBankLogRepo->shouldReceive('createLog')
                    ->once()
                    ->with($allianceId, null, 'interest', 5000, Mockery::type('string'));
        
                $this->mockAllianceRepo->shouldReceive('updateLastCompoundAt')
                    ->once()
                    ->with($allianceId);
        
                // Execute
                $result = $this->service->processAllUsers();
        
                $this->assertEquals(1, $result['alliances']);
            }
        
            // --- Helpers ---
        
            private function createMockUser(int $id, ?int $allianceId): User
            {
                return new User(
                    id: $id,
                    email: 'test@test.com',
                    characterName: 'Test',
                    bio: null,
                    profile_picture_url: null,
                    phone_number: null,
                    alliance_id: $allianceId,
                    alliance_role_id: null,
                    passwordHash: 'hash',
                    createdAt: '2024-01-01',
                    is_npc: false
                );
            }
        
            private function createMockResource(int $userId): UserResource
            {
                return new UserResource($userId, 0, 0, 0, 0.0, 0, 0, 0, 0, 0, 0);
            }
        
            private function createMockStructure(int $userId): UserStructure
            {
                return new UserStructure($userId, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
            }
        
            private function createMockStats(int $userId): UserStats
            {
                return new UserStats($userId, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, null);
            }
        }
