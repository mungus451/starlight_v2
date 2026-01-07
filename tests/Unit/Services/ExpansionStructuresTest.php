<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\PowerCalculatorService;
use App\Models\Services\BankService;
use App\Models\Services\TrainingService;
use App\Models\Services\BlackMarketService;
use App\Models\Services\AttackService;
use App\Models\Services\GeneralService;
use App\Core\Config;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\GeneralRepository;
use App\Models\Repositories\AllianceStructureRepository;
use App\Models\Repositories\AllianceStructureDefinitionRepository;
use App\Models\Repositories\EdictRepository;
use App\Models\Repositories\BountyRepository;
use App\Models\Repositories\BattleRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceBankLogRepository;
use App\Models\Repositories\BlackMarketLogRepository;
use App\Models\Repositories\HouseFinanceRepository;
use App\Models\Services\ArmoryService;
use App\Models\Services\EffectService;
use App\Models\Services\LevelUpService;
use App\Models\Services\NetWorthCalculatorService;
use App\Core\Events\EventDispatcher;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStats;
use App\Models\Entities\UserStructure;
use App\Models\Entities\User;
use App\Core\ServiceResponse; 
use Mockery;

class ExpansionStructuresTest extends TestCase
{
    // Mocks - Declared as properties for consistent access
    private $mockConfig;
    private $mockResourceRepo;
    private $mockStatsRepo;
    private $mockStructureRepo;
    private $mockUserRepo;
    private $mockGeneralRepo;
    private $mockEffectService;
    private $mockArmoryService;
    private $mockLogRepo; 
    private $mockPdo; 
    private $mockLevelUpService; 
    private $mockEventDispatcher; 
    private $mockBattleRepo; 
    private $mockNWCalc; 
    private $mockAllianceStructRepo;
    private $mockStructDefRepo;
    private $mockEdictRepo;
    private $mockBountyRepo; 
    
    // Services under test
    private $powerService;
    private $bankService;
    private $trainingService;
    private $blackMarketService;
    private $attackService;

    protected function setUp(): void
    {
        parent::setUp();

        // --- Instantiate all common mocks once and assign to properties ---
        $this->mockConfig = Mockery::mock(Config::class);
        $this->mockResourceRepo = Mockery::mock(ResourceRepository::class);
        $this->mockStatsRepo = Mockery::mock(StatsRepository::class);
        $this->mockStructureRepo = Mockery::mock(StructureRepository::class);
        $this->mockUserRepo = Mockery::mock(UserRepository::class);
        $this->mockGeneralRepo = Mockery::mock(GeneralRepository::class);
        $this->mockEffectService = Mockery::mock(EffectService::class);
        $this->mockArmoryService = Mockery::mock(ArmoryService::class);
        $this->mockLogRepo = Mockery::mock(BlackMarketLogRepository::class); 
        $this->mockPdo = Mockery::mock(\PDO::class); 
        $this->mockLevelUpService = Mockery::mock(LevelUpService::class); 
        $this->mockEventDispatcher = Mockery::mock(EventDispatcher::class); 
        $this->mockBattleRepo = Mockery::mock(BattleRepository::class); 
        $this->mockNWCalc = Mockery::mock(NetWorthCalculatorService::class); 
        $this->mockAllianceStructRepo = Mockery::mock(AllianceStructureRepository::class);
        $this->mockStructDefRepo = Mockery::mock(AllianceStructureDefinitionRepository::class);
        $this->mockEdictRepo = Mockery::mock(EdictRepository::class);
        $this->mockBountyRepo = Mockery::mock(BountyRepository::class); 

        // --- Default Expectations for commonly used mocks ---
        $this->mockPdo->shouldReceive('inTransaction')->andReturn(false)->byDefault();
        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('commit')->andReturn(true)->byDefault();
        
        $this->mockConfig->shouldReceive('get')->with('game_balance.generals', [])->andReturn([])->byDefault();
        $this->mockConfig->shouldReceive('get')->with('elite_weapons', [])->andReturn([])->byDefault();
        $this->mockEffectService->shouldReceive('hasActiveEffect')->andReturn(false)->byDefault();
        $this->mockEffectService->shouldReceive('getEffectDetails')->andReturn(null)->byDefault(); 
        $this->mockArmoryService->shouldReceive('getAggregateBonus')->andReturn(0)->byDefault();
        $this->mockLevelUpService->shouldReceive('grantExperience')->andReturn(ServiceResponse::success('XP granted'))->byDefault();
        $this->mockEventDispatcher->shouldReceive('dispatch')->andReturn(null)->byDefault();
        $this->mockNWCalc->shouldReceive('calculateTotalNetWorth')->andReturn(1000000)->byDefault(); 
        $this->mockStatsRepo->shouldReceive('findByUserId')->andReturn($this->createMockStats(1))->byDefault(); 
        $this->mockStatsRepo->shouldReceive('updateBaseStats')->andReturn(true)->byDefault();
        $this->mockGeneralRepo->shouldReceive('findByUserId')->andReturn([])->byDefault();
        $this->mockGeneralRepo->shouldReceive('countByUserId')->andReturn(0)->byDefault(); 
        $this->mockEdictRepo->shouldReceive('findActiveByUserId')->andReturn([])->byDefault();
        $this->mockBountyRepo->shouldReceive('findActiveByTargetId')->andReturn(null)->byDefault(); 

        // --- 1. Setup PowerCalculatorService ---
        $this->powerService = new PowerCalculatorService(
            $this->mockConfig,
            $this->mockArmoryService,
            $this->mockAllianceStructRepo,
            $this->mockStructDefRepo,
            $this->mockEdictRepo,
            $this->mockGeneralRepo,
            $this->mockEffectService
        );

        // --- 2. Setup BankService ---
        $this->bankService = new BankService(
            $this->mockPdo, 
            $this->mockConfig,
            $this->mockResourceRepo,
            $this->mockUserRepo,
            $this->mockStatsRepo,
            $this->mockStructureRepo
        );

        // --- 3. Setup TrainingService ---
        $mockGeneralService = Mockery::mock(GeneralService::class);
        $this->trainingService = new TrainingService(
            $this->mockConfig,
            $this->mockResourceRepo,
            $mockGeneralService,
            $this->mockStructureRepo
        );

        // --- 4. Setup BlackMarketService ---
        $mockAttackServiceForBlackMarket = Mockery::mock(AttackService::class); 
        $mockHouseRepo = Mockery::mock(HouseFinanceRepository::class);
        
        $this->blackMarketService = new BlackMarketService(
            $this->mockPdo,
            $this->mockConfig,
            $this->mockResourceRepo,
            $this->mockStatsRepo,
            $this->mockUserRepo,
            $this->mockBountyRepo,
            $mockAttackServiceForBlackMarket,
            $this->mockLogRepo,
            $this->mockEffectService,
            $mockHouseRepo,
            $this->mockLevelUpService,
            $this->mockStructureRepo,
            Mockery::mock(GeneralService::class)
        );

        // --- 5. Setup AttackService ---
        $mockAllianceRepo = Mockery::mock(AllianceRepository::class);
        $mockBankLogRepo = Mockery::mock(AllianceBankLogRepository::class);
        
        $this->attackService = new AttackService(
            $this->mockPdo, 
            $this->mockConfig,
            $this->mockUserRepo,
            $this->mockResourceRepo,
            $this->mockStructureRepo,
            $this->mockStatsRepo,
            $this->mockBattleRepo, 
            $mockAllianceRepo,
            $mockBankLogRepo,
            $this->mockBountyRepo, 
            $this->mockArmoryService,
            $this->powerService, 
            $this->mockLevelUpService, 
            $this->mockEventDispatcher, 
            $this->mockEffectService,
            $this->mockNWCalc 
        );
    }

    // --- TEST 1: Fusion Plant ---
    public function testFusionPlantBoostsProduction(): void
    {
        $userId = 1;
        $resources = $this->createMockResources($userId, workers: 100);
        $stats = $this->createMockStats($userId);
        
        $structures = $this->createMockStructures($userId, fusionPlantLevel: 10); 

        $this->mockConfig->shouldReceive('get')->with('game_balance.turn_processor')->andReturn([
            'credit_income_per_worker' => 10,
            'credit_income_per_econ_level' => 0,
            'credit_bonus_per_wealth_point' => 0,
            'bank_interest_rate' => 0,
            'citizen_growth_per_pop_level' => 0,
            'fusion_plant_bonus_per_level' => 0.005,
            'research_data_per_lab_level' => 0,
            'dark_matter_per_siphon_level' => 0,
            'dark_matter_production_multiplier' => 1.0,
            'naquadah_per_mining_complex_level' => 0,
            'naquadah_production_multiplier' => 1.0,
            'protoform_per_vat_level' => 0,
            'accounting_firm_base_bonus' => 0,
            'accounting_firm_multiplier' => 1.0
        ]);
        $this->mockArmoryService->shouldReceive('getAggregateBonus')->andReturn(0);

        $result = $this->powerService->calculateIncomePerTurn($userId, $resources, $stats, $structures);

        $this->assertEquals(1050, $result['total_credit_income']);
    }

    // --- TEST 2: Banking Datacenter ---
    public function testBankingDatacenterReducesRegenTime(): void
    {
        $userId = 1;
        
        $stats = $this->createMockStats($userId, depositCharges: 0, lastDepositAt: (new \DateTime('-5 hours'))->format('Y-m-d H:i:s'));
        $structures = $this->createMockStructures($userId, bankingDatacenterLevel: 12); 

        $this->mockStatsRepo->shouldReceive('findByUserId')->andReturn($stats);
        $this->mockStructureRepo->shouldReceive('findByUserId')->andReturn($structures);
        $this->mockResourceRepo->shouldReceive('findByUserId')->andReturn($this->createMockResources($userId)); 
        
        $this->mockConfig->shouldReceive('get')->with('bank')->andReturn([
            'deposit_max_charges' => 4,
            'deposit_charge_regen_hours' => 6,
            'banking_datacenter_regen_reduction_minutes' => 10,
            'max_banking_datacenter_reduction_minutes' => 180
        ]);

        $this->mockStatsRepo->shouldReceive('regenerateDepositCharges')->with($userId, 1)->once();

        $this->bankService->getBankData($userId);
        $this->addToAssertionCount(1); 
    }

    // --- TEST 3: Cloning Vats ---
    public function testCloningVatsDiscountsTraining(): void
    {
        $userId = 1;
        $structures = $this->createMockStructures($userId, cloningVatsLevel: 20); 

        $this->mockResourceRepo->shouldReceive('findByUserId')->andReturn($this->createMockResources($userId));
        $this->mockStructureRepo->shouldReceive('findByUserId')->andReturn($structures);

        $this->mockConfig->shouldReceive('get')->with('game_balance.training', [])->andReturn([
            'soldiers' => ['credits' => 10000, 'citizens' => 1],
            'cloning_vats_discount_per_level' => 0.01,
            'cloning_vats_max_discount' => 0.40
        ]);

        $data = $this->trainingService->getTrainingData($userId);
        
        $this->assertEquals(8000, $data['costs']['soldiers']['credits']);
    }

    // --- TEST 4: Orbital Trade Port (Isolated) ---
    public function testOrbitalTradePortDiscountsBlackMarket(): void
    {
        $userId = 1;
        $initialCost = 100;
        $expectedDiscountedCost = 75.0;

        // Local Mocks for BlackMarketService and its direct dependencies
        $localMockPdo = Mockery::mock(\PDO::class);
        $localMockConfig = Mockery::mock(Config::class);
        $localMockResourceRepo = Mockery::mock(ResourceRepository::class);
        $localMockStatsRepo = Mockery::mock(StatsRepository::class);
        $localMockUserRepo = Mockery::mock(UserRepository::class);
        $localMockBountyRepo = Mockery::mock(BountyRepository::class);
        $localMockAttackService = Mockery::mock(AttackService::class);
        $localMockLogRepo = Mockery::mock(BlackMarketLogRepository::class);
        $localMockEffectService = Mockery::mock(EffectService::class);
        $localMockHouseRepo = Mockery::mock(HouseFinanceRepository::class);
        $localMockLevelUpService = Mockery::mock(LevelUpService::class);
        $localMockStructureRepo = Mockery::mock(StructureRepository::class);
        
        // Configure Local PDO Mocks for the transaction
        $localMockPdo->shouldReceive('beginTransaction')->once()->andReturn(true);
        $localMockPdo->shouldReceive('commit')->once()->andReturn(true);

        // Configure Local Config Mocks
        $localMockConfig->shouldReceive('get')->with('game_balance.black_market')->once()->andReturn([
            'orbital_trade_port_discount_per_level' => 0.005,
            'max_orbital_trade_port_discount' => 0.25
        ]);
        $localMockConfig->shouldReceive('get')->with('black_market.costs.stat_respec', 50)->once()->andReturn($initialCost);

        // Configure Local ResourceRepo Mocks
        $userResources = $this->createMockResources($userId, crystals: 100000.0);
        $localMockResourceRepo->shouldReceive('findByUserId')->with($userId)->once()->andReturn($userResources);
        $localMockResourceRepo->shouldReceive('updateResources')
            ->withArgs(function($id, $creds, $crystals) use ($userId, $expectedDiscountedCost) {
                return $id === $userId && $crystals === -$expectedDiscountedCost;
            })
            ->once()
            ->andReturn(true); // updateResources returns bool
        
        // Configure Local StatsRepo Mocks (for the callable)
        $userStats = $this->createMockStats($userId, strengthPoints: 10);
        $localMockStatsRepo->shouldReceive('findByUserId')->with($userId)->once()->andReturn($userStats);
        $localMockStatsRepo->shouldReceive('updateBaseStats')->once()->andReturn(true);

        // Configure Local LogRepo Mocks (for the callable)
        $localMockLogRepo->shouldReceive('log')->once()->andReturn(true);

        // Instantiate BlackMarketService with local mocks
        $blackMarketService = new BlackMarketService(
            $localMockPdo,
            $localMockConfig,
            $localMockResourceRepo,
            $localMockStatsRepo,
            $localMockUserRepo, // Using a generic mock for this as it's not used in path
            $localMockBountyRepo,
            $localMockAttackService,
            $localMockLogRepo,
            $localMockEffectService,
            $localMockHouseRepo,
            $localMockLevelUpService,
            $localMockStructureRepo, // This is used for orbital_trade_port_level
            Mockery::mock(GeneralService::class)
        );

        // Mock StructureRepo to return relevant structure level
        $userStructures = $this->createMockStructures($userId, orbitalTradePortLevel: 50);
        $localMockStructureRepo->shouldReceive('findByUserId')->with($userId)->once()->andReturn($userStructures);

        // Act
        $response = $blackMarketService->purchaseStatRespec($userId);

        // Assert
        $this->assertTrue($response->isSuccess());
        $this->assertEquals('Neural pathways reset. You have 10 points available.', $response->message);
        
        // Mockery::close() is handled by parent::tearDown()
    }

    // --- TEST 5: War College ---
    public function testWarCollegeBoostsXP(): void
    {
        $userId = 1;
        $defenderId = 2;
        $structures = $this->createMockStructures($userId, warCollegeLevel: 10); 

        $this->mockUserRepo->shouldReceive('findByCharacterName')->andReturn($this->createMockUser($defenderId, 'Target'));
        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($this->createMockUser($userId));
        
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($userId)->andReturn($this->createMockResources($userId, soldiers: 1000));
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($defenderId)->andReturn($this->createMockResources($defenderId)); 
        
        $this->mockStatsRepo->shouldReceive('findByUserId')->andReturn($this->createMockStats($userId, attackTurns: 10));
        $this->mockStatsRepo->shouldReceive('findByUserId')->andReturn($this->createMockStats($defenderId));
        
        $this->mockStructureRepo->shouldReceive('findByUserId')->with($userId)->andReturn($structures);
        $this->mockStructureRepo->shouldReceive('findByUserId')->with($defenderId)->andReturn($this->createMockStructures($defenderId));

        $this->mockConfig->shouldReceive('get')->with('game_balance.attack')->andReturn([
            'attack_turn_cost' => 1,
            'war_college_xp_bonus_per_level' => 0.02,
            'power_per_soldier' => 1,
            'plunder_percent' => 0.1,
            'war_prestige_gain_base' => 5,
            'power_per_offense_level' => 0,
            'power_per_strength_point' => 0,
            'power_per_guard' => 1,
            'power_per_fortification_level' => 0,
            'power_per_defense_level' => 0,
            'power_per_constitution_point' => 0,
            'shield_hp_per_level' => 0,
            'nanite_casualty_reduction_per_level' => 0,
            'max_nanite_casualty_reduction' => 0,
            'ion_cannon_damage_per_level' => 0,
            'max_ion_cannon_damage' => 0,
            'phase_bunker_protection_per_level' => 0,
            'max_phase_bunker_protection' => 0
        ]);
        $this->mockConfig->shouldReceive('get')->with('game_balance.alliance_treasury')->andReturn([]);
        $this->mockConfig->shouldReceive('get')->with('game_balance.xp.rewards')->andReturn(['battle_win' => 100, 'battle_loss' => 0, 'battle_stalemate' => 0, 'battle_defense_loss' => 0, 'battle_defense_win' => 0]); 

        $this->mockStatsRepo->shouldReceive('updateBattleAttackerStats')
            ->with($userId, Mockery::any(), Mockery::any(), 120, Mockery::any())
            ->once();

        // Loose mocks for BattleService dependencies
        $this->mockResourceRepo->shouldReceive('updateBattleAttacker')->byDefault();
        $this->mockResourceRepo->shouldReceive('updateBattleDefender')->byDefault();
        $this->mockStatsRepo->shouldReceive('incrementBattleStats')->byDefault();
        $this->mockStatsRepo->shouldReceive('updateBattleDefenderStats')->byDefault();
        
        $this->mockBattleRepo->shouldReceive('createReport')->andReturn(1)->byDefault(); 
        
        $this->attackService->conductAttack($userId, 'Target', 'plunder');
        $this->addToAssertionCount(1); 
    }

    // --- TEST 6: Ion Cannon & Phase Bunker ---
    public function testIonCannonAndPhaseBunker(): void
    {
        $attackerId = 1;
        $defenderId = 2;
        
        $attStructs = $this->createMockStructures($attackerId);
        $defStructs = $this->createMockStructures($defenderId, 
            phaseBunkerLevel: 20, // 10% Protection
            ionCannonNetworkLevel: 100 // 100 * 0.1 = 10% -> Max 5% damage
        );

        $this->mockStructureRepo->shouldReceive('findByUserId')->with($attackerId)->andReturn($attStructs)->byDefault();
        $this->mockStructureRepo->shouldReceive('findByUserId')->with($defenderId)->andReturn($defStructs)->byDefault();

        $originalAttackerResources = $this->createMockResources($attackerId, soldiers: 1000);
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($attackerId)->andReturn($originalAttackerResources)->byDefault();

        $this->mockResourceRepo->shouldReceive('findByUserId')->with($defenderId)->andReturn($this->createMockResources($defenderId, credits: 10000))->byDefault();

        $this->mockConfig->shouldReceive('get')->with('game_balance.attack')->andReturn([
            'attack_turn_cost' => 1,
            'ion_cannon_damage_per_level' => 0.001,
            'max_ion_cannon_damage' => 0.05,
            'phase_bunker_protection_per_level' => 0.005,
            'max_phase_bunker_protection' => 0.20,
            'plunder_percent' => 0.10, 
            'power_per_soldier' => 1,
            'war_prestige_gain_base' => 5,
            'power_per_offense_level' => 0,
            'power_per_strength_point' => 0,
            'power_per_guard' => 1,
            'power_per_fortification_level' => 0,
            'power_per_defense_level' => 0,
            'power_per_constitution_point' => 0,
            'shield_hp_per_level' => 0,
            'nanite_casualty_reduction_per_level' => 0,
            'max_nanite_casualty_reduction' => 0
        ])->byDefault();
        
        $this->mockUserRepo->shouldReceive('findByCharacterName')->andReturn($this->createMockUser($defenderId, 'Target'))->byDefault();
        $this->mockUserRepo->shouldReceive('findById')->andReturn($this->createMockUser($attackerId))->byDefault();
        
        $this->mockStatsRepo->shouldReceive('findByUserId')->andReturn($this->createMockStats($attackerId, attackTurns: 10))->byDefault();
        
        $this->mockConfig->shouldReceive('get')->with('game_balance.alliance_treasury')->andReturn([]);
        $this->mockConfig->shouldReceive('get')->with('game_balance.xp.rewards')->andReturn(['battle_win' => 100, 'battle_loss' => 0, 'battle_stalemate' => 0, 'battle_defense_loss' => 0, 'battle_defense_win' => 0])->byDefault();

        $this->mockResourceRepo->shouldReceive('updateBattleAttacker')
            ->withArgs(function($id, $creds, $soldiersAfterBattle) use ($originalAttackerResources) {
                return ($originalAttackerResources->soldiers - $soldiersAfterBattle) >= 50; 
            })->once();

        $this->mockResourceRepo->shouldReceive('updateBattleDefender')
            ->withArgs(function($id, $credits, $guards) {
                return $credits == 9100;
            })->once();
            
        $this->mockStatsRepo->shouldReceive('updateBattleAttackerStats')->byDefault();
        $this->mockStatsRepo->shouldReceive('incrementBattleStats')->byDefault();
        $this->mockStatsRepo->shouldReceive('updateBattleDefenderStats')->byDefault();

        $this->mockBattleRepo->shouldReceive('createReport')->andReturn(1)->byDefault();

        $this->attackService->conductAttack($attackerId, 'Target', 'plunder');
        $this->addToAssertionCount(1); 
    }

    // --- TEST 7: Neural Uplink ---
    public function testNeuralUplinkBoostsSentry(): void
    {
        $userId = 1;
        $resources = $this->createMockResources($userId, sentries: 100);
        $structures = $this->createMockStructures($userId, neuralUplinkLevel: 10); // +20%

        $this->mockConfig->shouldReceive('get')->with('game_balance.spy')->andReturn([
            'base_power_per_sentry' => 1,
            'defense_power_per_level' => 0,
            'neural_uplink_bonus_per_level' => 0.02
        ]);
        $this->mockArmoryService->shouldReceive('getAggregateBonus')->andReturn(0);

        $result = $this->powerService->calculateSentryPower($userId, $resources, $structures);

        // Base 100 * 1.20 = 120
        $this->assertEquals(120, $result['total']);
    }

    // --- TEST 8: Mercenary Outpost ---
    public function testMercenaryOutpostDrafting(): void
    {
        $userId = 1;
        $unitType = 'soldiers';
        $quantity = 1000;
        
        $structures = $this->createMockStructures($userId, mercenaryOutpostLevel: 5);
        $resources = $this->createMockResources($userId, darkMatter: 1000.0, soldiers: 500);
        
        $this->mockStructureRepo->shouldReceive('findByUserId')->with($userId)->andReturn($structures);
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($userId)->andReturn($resources);
        
        $this->mockConfig->shouldReceive('get')->with('black_market.mercenary_outpost')->andReturn([
            'limit_per_level' => 500,
            'costs' => ['soldiers' => ['dark_matter' => 0.75]]
        ]);
        
        $mockGeneralService = Mockery::mock(GeneralService::class);
        $mockGeneralService->shouldReceive('getArmyCapacity')->with($userId)->andReturn(10000); // Plenty of capacity

        // We need to re-inject the mocked GeneralService into the BlackMarketService for this test
        $this->blackMarketService = new BlackMarketService(
            $this->mockPdo, $this->mockConfig, $this->mockResourceRepo, $this->mockStatsRepo,
            $this->mockUserRepo, $this->mockBountyRepo, Mockery::mock(AttackService::class),
            $this->mockLogRepo, $this->mockEffectService, Mockery::mock(HouseFinanceRepository::class),
            $this->mockLevelUpService, $this->mockStructureRepo, $mockGeneralService
        );
        
        $this->mockResourceRepo->shouldReceive('updateResources')
            ->with($userId, 0, 0, -750.0) // 1000 * 0.75
            ->once();
        
        $this->mockResourceRepo->shouldReceive('updateTrainedUnits')
            ->with($userId, $resources->credits, $resources->untrained_citizens, $resources->workers, 1500, $resources->guards, $resources->spies, $resources->sentries)
            ->once();
            
        $this->mockLogRepo->shouldReceive('log')->once();

        $response = $this->blackMarketService->draftMercenaries($userId, $unitType, $quantity);

        $this->assertTrue($response->isSuccess());
    }

    // Helpers
    private function createMockResources(int $userId, int $soldiers = 0, int $workers = 0, int $banked = 0, int $credits = 0, int $sentries = 0, float $crystals = 0.0, float $darkMatter = 0.0): UserResource
    {
        return new UserResource(
            user_id: $userId,
            credits: $credits,
            banked_credits: $banked,
            gemstones: 0,
            naquadah_crystals: $crystals,
            untrained_citizens: 0,
            workers: $workers,
            soldiers: $soldiers,
            guards: 0,
            spies: 0,
            sentries: $sentries,
            untraceable_chips: 0,
            research_data: 0,
            dark_matter: $darkMatter,
            protoform: 0.0
        );
    }

    private function createMockStats(int $userId, int $attackTurns = 10, int $wealthPoints = 0, int $depositCharges = 0, ?string $lastDepositAt = null, int $strengthPoints = 0): UserStats
    {
        return new UserStats(
            user_id: $userId,
            level: 1,
            experience: 0,
            net_worth: 0,
            war_prestige: 0,
            energy: 10,
            attack_turns: $attackTurns,
            level_up_points: 0,
            strength_points: $strengthPoints,
            constitution_points: 0,
            wealth_points: $wealthPoints,
            dexterity_points: 0,
            charisma_points: 0,
            deposit_charges: $depositCharges,
            last_deposit_at: $lastDepositAt,
            battles_won: 0,
            battles_lost: 0,
            spy_successes: 0,
            spy_failures: 0
        );
    }

    private function createMockStructures(
        int $userId, 
        int $fortificationLevel = 0,
        int $offenseUpgradeLevel = 0,
        int $defenseUpgradeLevel = 0,
        int $spyUpgradeLevel = 0,
        int $economyUpgradeLevel = 0,
        int $populationLevel = 0,
        int $armoryLevel = 0,
        int $accountingFirmLevel = 0,
        int $quantumResearchLabLevel = 0,
        int $naniteForgeLevel = 0,
        int $darkMatterSiphonLevel = 0,
        int $planetaryShieldLevel = 0,
        int $naquadahMiningComplexLevel = 0,
        int $protoformVatLevel = 0,
        int $weaponVaultLevel = 0,
        int $embassyLevel = 0,
        // New structures
        int $fusionPlantLevel = 0,
        int $orbitalTradePortLevel = 0,
        int $bankingDatacenterLevel = 0,
        int $cloningVatsLevel = 0,
        int $warCollegeLevel = 0,
        int $mercenaryOutpostLevel = 0,
        int $phaseBunkerLevel = 0,
        int $ionCannonNetworkLevel = 0,
        int $neuralUplinkLevel = 0,
        int $subspaceScannerLevel = 0
    ): UserStructure
    {
        return new UserStructure(
            user_id: $userId,
            fortification_level: $fortificationLevel,
            offense_upgrade_level: $offenseUpgradeLevel,
            defense_upgrade_level: $defenseUpgradeLevel,
            spy_upgrade_level: $spyUpgradeLevel,
            economy_upgrade_level: $economyUpgradeLevel,
            population_level: $populationLevel,
            armory_level: $armoryLevel,
            accounting_firm_level: $accountingFirmLevel,
            quantum_research_lab_level: $quantumResearchLabLevel,
            nanite_forge_level: $naniteForgeLevel,
            dark_matter_siphon_level: $darkMatterSiphonLevel,
            planetary_shield_level: $planetaryShieldLevel,
            naquadah_mining_complex_level: $naquadahMiningComplexLevel,
            protoform_vat_level: $protoformVatLevel,
            weapon_vault_level: $weaponVaultLevel,
            embassy_level: $embassyLevel,
            fusion_plant_level: $fusionPlantLevel,
            orbital_trade_port_level: $orbitalTradePortLevel,
            banking_datacenter_level: $bankingDatacenterLevel,
            cloning_vats_level: $cloningVatsLevel,
            war_college_level: $warCollegeLevel,
            mercenary_outpost_level: $mercenaryOutpostLevel,
            phase_bunker_level: $phaseBunkerLevel,
            ion_cannon_network_level: $ionCannonNetworkLevel,
            neural_uplink_level: $neuralUplinkLevel,
            subspace_scanner_level: $subspaceScannerLevel
        );
    }

    private function createMockUser(int $id, string $name = 'TestUser', ?int $allianceId = null): User
    {
        return new User(
            id: $id,
            email: 'test' . $id . '@example.com',
            characterName: $name,
            bio: null,
            profile_picture_url: null,
            phone_number: null,
            alliance_id: $allianceId,
            alliance_role_id: null,
            passwordHash: 'hash',
            createdAt: '2024-01-01 00:00:00'
        );
    }
}