<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\StructureService;
use App\Models\Services\PowerCalculatorService;
use App\Models\Services\TurnProcessorService;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceBankLogRepository;
use App\Models\Repositories\AllianceStructureRepository;
use App\Models\Repositories\AllianceStructureDefinitionRepository;
use App\Models\Repositories\GeneralRepository;
use App\Models\Repositories\ScientistRepository;
use App\Models\Repositories\EdictRepository;
use App\Models\Services\EmbassyService;
use App\Models\Services\ArmoryService;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStructure;
use App\Models\Entities\UserStats;
use App\Core\Config;
use Mockery;

class NaquadahFeatureTest extends TestCase
{
    /**
     * Test 1: StructureService should deduct Naquadah Crystals when upgrading
     */
    public function testUpgradeStructure_DeductsNaquadah_WhenAffordable(): void
    {
        // 1. Arrange Dependencies
        $mockDb = $this->createMockPDO();
        $mockConfig = Mockery::mock(Config::class);
        $mockResourceRepo = Mockery::mock(ResourceRepository::class);
        $mockStructureRepo = Mockery::mock(StructureRepository::class);

        $service = new StructureService($mockDb, $mockConfig, $mockResourceRepo, $mockStructureRepo);

        $userId = 1;
        
        // Mock Config
        $mockConfig->shouldReceive('get')
            ->with('game_balance.structures.naquadah_mining_complex')
            ->andReturn([
                'name' => 'Naquadah Mining Complex',
                'base_cost' => 1000000,
                'base_crystal_cost' => 500,
                'multiplier' => 2.0
            ]);

        // Mock Resources (Affordable)
        $mockResources = new UserResource(
            user_id: $userId,
            credits: 2000000,
            banked_credits: 0,
            gemstones: 0,
            naquadah_crystals: 1000.0, // Enough
            untrained_citizens: 0,
            workers: 0,
            soldiers: 0,
            guards: 0,
            spies: 0,
            sentries: 0
        );
        $mockResourceRepo->shouldReceive('findByUserId')->with($userId)->andReturn($mockResources);

        // Mock Structures (Level 0 -> 1)
        $mockStructures = new UserStructure(
            user_id: $userId,
            fortification_level: 0,
            offense_upgrade_level: 0,
            defense_upgrade_level: 0,
            spy_upgrade_level: 0,
            economy_upgrade_level: 0,
            population_level: 0,
            armory_level: 0,
            accounting_firm_level: 0,
            naquadah_mining_complex_level: 0 // Current Level
        );
        $mockStructureRepo->shouldReceive('findByUserId')->andReturn($mockStructures);

        // Mock Transaction
        $mockDb->shouldReceive('inTransaction')->andReturn(false);
        $mockDb->shouldReceive('beginTransaction')->once();
        $mockDb->shouldReceive('commit')->once();

        // 2. Expect Updates
        // Should deduct 1,000,000 Credits and 500 Crystals
        // Note: updateResources takes negative values for deduction
        $mockResourceRepo->shouldReceive('updateResources')
            ->once()
            ->with($userId, -1000000, -500, 0) 
            ->andReturn(true);

        $mockStructureRepo->shouldReceive('updateStructureLevel')
            ->once()
            ->with($userId, 'naquadah_mining_complex_level', 1)
            ->andReturn(true);

        // 3. Act
        $response = $service->upgradeStructure($userId, 'naquadah_mining_complex');

        // 4. Assert
        $this->assertServiceSuccess($response);
    }

    public function testUpgradeStructure_Fails_WhenInsufficientNaquadah(): void
    {
        // 1. Arrange
        $mockDb = $this->createMockPDO();
        $mockConfig = Mockery::mock(Config::class);
        $mockResourceRepo = Mockery::mock(ResourceRepository::class);
        $mockStructureRepo = Mockery::mock(StructureRepository::class);

        $service = new StructureService($mockDb, $mockConfig, $mockResourceRepo, $mockStructureRepo);
        $userId = 1;

        $mockConfig->shouldReceive('get')->andReturn([
            'name' => 'Naquadah Mining Complex',
            'base_cost' => 1000000,
            'base_crystal_cost' => 500,
            'multiplier' => 2.0
        ]);

        // Insufficient Crystals (100 < 500)
        $mockResources = new UserResource($userId, 2000000, 0, 0, 100.0, 0, 0, 0, 0, 0, 0);
        $mockResourceRepo->shouldReceive('findByUserId')->andReturn($mockResources);

        $mockStructures = new UserStructure($userId, 0, 0, 0, 0, 0, 0, 0, 0);
        $mockStructureRepo->shouldReceive('findByUserId')->andReturn($mockStructures);

        // 2. Act
        $response = $service->upgradeStructure($userId, 'naquadah_mining_complex');

        // 3. Assert
        $this->assertServiceFailure($response);
        $this->assertServiceMessageContains($response, 'Insufficient naquadah crystals');
    }

    /**
     * Test 2: PowerCalculatorService should calculate Naquadah Income
     */
    public function testCalculateIncome_IncludesNaquadahIncome(): void
    {
        // 1. Arrange Dependencies
        $mockConfig = Mockery::mock(Config::class);
        $mockArmoryService = Mockery::mock(ArmoryService::class);
        $mockAllianceStructRepo = Mockery::mock(AllianceStructureRepository::class);
        $mockStructDefRepo = Mockery::mock(AllianceStructureDefinitionRepository::class);
        $mockEdictRepo = Mockery::mock(EdictRepository::class);
        $mockGeneralRepo = Mockery::mock(GeneralRepository::class);

        $mockEdictRepo->shouldReceive('findActiveByUserId')->andReturn([]);
        $mockGeneralRepo->shouldReceive('findByUserId')->andReturn([]);
        $mockGeneralRepo->shouldReceive('countByUserId')->andReturn(0);
        $mockConfig->shouldReceive('get')->with('game_balance.generals', [])->andReturn([]); // Fix missing config too

        $service = new PowerCalculatorService(
            $mockConfig,
            $mockArmoryService,
            $mockAllianceStructRepo,
            $mockStructDefRepo,
            $mockEdictRepo,
            $mockGeneralRepo
        );

        $userId = 1;

        // Mock Config
        $mockConfig->shouldReceive('get')
            ->with('game_balance.turn_processor')
            ->andReturn([
                'credit_income_per_econ_level' => 1000,
                'credit_income_per_worker' => 100,
                'bank_interest_rate' => 0.0005,
                'credit_bonus_per_wealth_point' => 0.01,
                'citizen_growth_per_pop_level' => 1,
                'naquadah_per_mining_complex_level' => 0.5 // KEY CONFIG
            ]);

        $mockArmoryService->shouldReceive('getAggregateBonus')->andReturn(0);

        // Entities - Use Real Objects (Readonly classes cannot be mocked easily)
        $resources = new UserResource(
            user_id: $userId,
            credits: 0,
            banked_credits: 0,
            gemstones: 0,
            naquadah_crystals: 0.0,
            untrained_citizens: 0,
            workers: 10,
            soldiers: 0,
            guards: 0,
            spies: 0,
            sentries: 0
        );

        $stats = new UserStats(
            user_id: $userId,
            level: 1,
            experience: 0,
            net_worth: 0,
            war_prestige: 0,
            energy: 10,
            attack_turns: 10,
            level_up_points: 0,
            strength_points: 0,
            constitution_points: 0,
            wealth_points: 0, // 0 Wealth Points
            dexterity_points: 0,
            charisma_points: 0,
            deposit_charges: 0,
            last_deposit_at: null
        );

        // Structure Level 5
        $structures = new UserStructure(
            user_id: $userId,
            fortification_level: 0,
            offense_upgrade_level: 0,
            defense_upgrade_level: 0,
            spy_upgrade_level: 0,
            economy_upgrade_level: 0,
            population_level: 0,
            armory_level: 0,
            accounting_firm_level: 0,
            naquadah_mining_complex_level: 5 
        );

        // 2. Act
        $result = $service->calculateIncomePerTurn($userId, $resources, $stats, $structures, null);

        // 3. Assert
        // Expected: 5 levels * 0.5 = 2.5
        $this->assertEquals(2.5, $result['naquadah_income']);
    }

    /**
     * Test 3: TurnProcessorService passes Naquadah Income to ResourceRepo
     */
    public function testProcessTurn_AppliesNaquadahIncome(): void
    {
        // 1. Arrange Dependencies
        $mockDb = $this->createMockPDO();
        $mockConfig = Mockery::mock(Config::class);
        $mockUserRepo = Mockery::mock(UserRepository::class);
        $mockResourceRepo = Mockery::mock(ResourceRepository::class);
        $mockStructureRepo = Mockery::mock(StructureRepository::class);
        $mockStatsRepo = Mockery::mock(StatsRepository::class);
        $mockPowerCalc = Mockery::mock(PowerCalculatorService::class);
        $mockAllianceRepo = Mockery::mock(AllianceRepository::class);
        $mockBankLogRepo = Mockery::mock(AllianceBankLogRepository::class);
        $mockGeneralRepo = Mockery::mock(GeneralRepository::class);
        $mockScientistRepo = Mockery::mock(ScientistRepository::class);
        $mockEdictRepo = Mockery::mock(EdictRepository::class);
        $mockEmbassyService = Mockery::mock(EmbassyService::class);

        // Config Treasury for Constructor
        $mockConfig->shouldReceive('get')->with('game_balance.alliance_treasury', [])->andReturn([]);
        $mockConfig->shouldReceive('get')->with('game_balance.upkeep', [])->andReturn([]);

        $mockEdictRepo->shouldReceive('findActiveByUserId')->andReturn([]);

        $service = new TurnProcessorService(
            $mockDb,
            $mockConfig,
            $mockUserRepo,
            $mockResourceRepo,
            $mockStructureRepo,
            $mockStatsRepo,
            $mockPowerCalc,
            $mockAllianceRepo,
            $mockBankLogRepo,
            $mockGeneralRepo,
            $mockScientistRepo,
            $mockEdictRepo,
            $mockEmbassyService
        );

        $userId = 123;

        // Mock User Fetching (Simulate finding 1 user)
        $mockUserRepo->shouldReceive('getAllUserIds')->andReturn([$userId]);
        
        $mockUser = new \App\Models\Entities\User(
            $userId, 
            'test@example.com', 
            'TestUser', 
            null, 
            null, 
            null, 
            null, 
            null, 
            'hash', 
            '2025-01-01',
            false
        );
        $mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($mockUser);

        // Mock Data Fetches
        // Use real entities for return values
        $resources = new UserResource($userId, 0, 0, 0, 0.0, 0, 0, 0, 0, 0, 0);
        $structures = new UserStructure($userId, 0, 0, 0, 0, 0, 0, 0, 0); 
        $stats = new UserStats($userId, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, null);

        $mockResourceRepo->shouldReceive('findByUserId')->andReturn($resources);
        $mockStructureRepo->shouldReceive('findByUserId')->andReturn($structures);
        $mockStatsRepo->shouldReceive('findByUserId')->andReturn($stats);
        
        // Mock Alliance Processing (Empty for this test)
        $mockAllianceRepo->shouldReceive('getAllAlliances')->andReturn([]);

        // Mock Power Calculation
        // This is where we inject the calculated Naquadah
        $mockIncomeData = [
            'total_credit_income' => 1000,
            'interest' => 50,
            'total_citizens' => 10,
            'research_data_income' => 0,
            'dark_matter_income' => 0,
            'naquadah_income' => 7.5, // EXPECTED VALUE
            'protoform_income' => 0.0
        ];
        $mockPowerCalc->shouldReceive('calculateIncomePerTurn')->andReturn($mockIncomeData);

        // Mock Upkeep
        $mockGeneralRepo->shouldReceive('getGeneralCount')->with($userId)->andReturn(0);
        $mockScientistRepo->shouldReceive('getActiveScientistCount')->with($userId)->andReturn(0);

        // Mock DB Transaction
        $mockDb->shouldReceive('inTransaction')->andReturn(false);
        $mockDb->shouldReceive('beginTransaction')->once();
        $mockDb->shouldReceive('commit')->once();

        // 2. Expect Apply Turn Income
        $mockResourceRepo->shouldReceive('applyTurnIncome')
            ->once()
            ->with(
                $userId,
                1000, // Credits
                50,   // Interest
                10,   // Citizens
                0,    // Research
                0,    // Dark Matter
                7.5,   // Naquadah (The New Parameter)
                0.0    // Protoform
            )
            ->andReturn(true);

        $mockStatsRepo->shouldReceive('applyTurnAttackTurn')->once()->andReturn(true);

        // 3. Act
        $service->processAllUsers();
        
        // Assertions handled by Mockery expectations
        $this->assertTrue(true); // Explicitly mark test as passed if no exceptions thrown
    }
}
