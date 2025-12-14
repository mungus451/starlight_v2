<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\PowerCalculatorService;
use App\Models\Services\ArmoryService;
use App\Core\Config;
use App\Models\Repositories\AllianceStructureRepository;
use App\Models\Repositories\AllianceStructureDefinitionRepository;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStats;
use App\Models\Entities\UserStructure;
use App\Models\Entities\AllianceStructure;
use App\Models\Entities\AllianceStructureDefinition;
use Mockery;

class PowerCalculatorServiceTest extends TestCase
{
    private PowerCalculatorService $service;
    private Config|Mockery\MockInterface $mockConfig;
    private ArmoryService|Mockery\MockInterface $mockArmoryService;
    private AllianceStructureRepository|Mockery\MockInterface $mockAllianceStructRepo;
    private AllianceStructureDefinitionRepository|Mockery\MockInterface $mockStructDefRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConfig = Mockery::mock(Config::class);
        $this->mockArmoryService = Mockery::mock(ArmoryService::class);
        $this->mockAllianceStructRepo = Mockery::mock(AllianceStructureRepository::class);
        $this->mockStructDefRepo = Mockery::mock(AllianceStructureDefinitionRepository::class);

        $this->service = new PowerCalculatorService(
            $this->mockConfig,
            $this->mockArmoryService,
            $this->mockAllianceStructRepo,
            $this->mockStructDefRepo
        );
    }

    public function testCalculateOffensePowerCalculatesCorrectlyWithoutAlliance(): void
    {
        $userId = 1;
        
        $resources = $this->createMockResources($userId, soldiers: 100);
        $stats = $this->createMockStats($userId, strengthPoints: 10);
        $structures = $this->createMockStructures($userId, offenseLevel: 5);

        // Config Mock
        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.attack')
            ->andReturn([
                'power_per_soldier' => 10,
                'power_per_offense_level' => 0.05, // 5% per level
                'power_per_strength_point' => 0.01 // 1% per point
            ]);

        // Armory Mock
        $this->mockArmoryService->shouldReceive('getAggregateBonus')
            ->with($userId, 'soldier', 'attack', 100)
            ->andReturn(500); // Flat bonus

        // Logic Check:
        // Base Unit Power = 100 * 10 = 1000
        // Total Base = 1000 + 500 = 1500
        // Structure Bonus = 5 * 0.05 = 0.25 (25%)
        // Stat Bonus = 10 * 0.01 = 0.10 (10%)
        // Total Multiplier = 1 + 0.25 + 0.10 = 1.35
        // Total Power = 1500 * 1.35 = 2025

        $result = $this->service->calculateOffensePower($userId, $resources, $stats, $structures, null);

        $this->assertEquals(2025, $result['total']);
        $this->assertEquals(1500, $result['total_base_power']);
        $this->assertEquals(0.25, $result['structure_bonus_pct']);
        $this->assertEquals(0.10, $result['stat_bonus_pct']);
    }

    public function testCalculateOffensePowerIncludesAllianceBonuses(): void
    {
        $userId = 1;
        $allianceId = 99;
        
        $resources = $this->createMockResources($userId, soldiers: 100);
        $stats = $this->createMockStats($userId, strengthPoints: 0);
        $structures = $this->createMockStructures($userId, offenseLevel: 0);

        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.attack')
            ->andReturn([
                'power_per_soldier' => 10,
                'power_per_offense_level' => 0.05,
                'power_per_strength_point' => 0.01
            ]);

        $this->mockArmoryService->shouldReceive('getAggregateBonus')
            ->andReturn(0);

        // Mock Alliance Structures
        $mockStructDef = new AllianceStructureDefinition(
            structure_key: 'training_ground',
            name: 'Training Ground',
            description: 'Boosts offense',
            base_cost: 1000,
            cost_multiplier: 1.5,
            bonus_text: 'Test Bonus',
            bonuses_json: json_encode([['type' => 'offense_bonus_percent', 'value' => 0.05]]) // 5% per level
        );

        $mockOwnedStruct = new AllianceStructure(
            id: 1,
            alliance_id: $allianceId,
            structure_key: 'training_ground',
            level: 2, // 2 levels * 5% = 10% bonus
            created_at: '2024-01-01',
            updated_at: '2024-01-01'
        );

        $this->mockAllianceStructRepo->shouldReceive('findByAllianceId')
            ->with($allianceId)
            ->andReturn(['training_ground' => $mockOwnedStruct]);

        $this->mockStructDefRepo->shouldReceive('getAllDefinitions')
            ->once() // Cached internally, so called only once if cache works
            ->andReturn([$mockStructDef]);

        // Logic Check:
        // Base Unit Power = 1000
        // Total Base = 1000
        // Personal Bonus = 0
        // Alliance Bonus = 0.10 (10%)
        // Total Multiplier = 1.10
        // Total Power = 1100

        $result = $this->service->calculateOffensePower($userId, $resources, $stats, $structures, $allianceId);

        $this->assertEquals(1100, $result['total']);
        $this->assertEquals(0.10, $result['alliance_bonus_pct']);
    }

    public function testCalculateIncomePerTurnCalculatesCorrectly(): void
    {
        $userId = 1;
        
        $resources = $this->createMockResources($userId, workers: 50, banked: 10000);
        $stats = $this->createMockStats($userId, wealthPoints: 5);
        $structures = $this->createMockStructures($userId, economyLevel: 10, accountingLevel: 2);

        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.turn_processor')
            ->andReturn([
                'credit_income_per_econ_level' => 100,
                'credit_income_per_worker' => 10,
                'credit_bonus_per_wealth_point' => 0.02,
                'bank_interest_rate' => 0.01,
                'citizen_growth_per_pop_level' => 5
            ]);

        $this->mockArmoryService->shouldReceive('getAggregateBonus')
            ->with($userId, 'worker', 'credit_bonus', 50)
            ->andReturn(200);

        // Logic Check:
        // Base from Econ = 10 * 100 = 1000
        // Base from Workers = 50 * 10 = 500
        // Base from Armory = 200
        // Total Base Production = 1700
        
        // Multipliers:
        // Wealth = 5 * 0.02 = 0.10 (10%)
        // Accounting = 2 * 0.01 = 0.02 (2%)
        // Alliance = 0
        // Total Multiplier = 1.12
        
        // Income = 1700 * 1.12 = 1904
        
        // Interest = 10000 * 0.01 = 100

        $result = $this->service->calculateIncomePerTurn($userId, $resources, $stats, $structures, null);

        $this->assertEquals(1904, $result['total_credit_income']);
        $this->assertEquals(100, $result['interest']);
        $this->assertEquals(1700, $result['base_production']);
        $this->assertEquals(0.10, $result['stat_bonus_pct']);
        $this->assertEquals(0.02, $result['accounting_firm_bonus_pct']);
    }

    public function testCalculateIncomePerTurnIncludesResearchAndDarkMatter(): void
    {
        $userId = 1;
        
        $resources = $this->createMockResources($userId);
        $stats = $this->createMockStats($userId);
        $structures = $this->createMockStructures($userId, qrlLevel: 5, siphonLevel: 2);

        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.turn_processor')
            ->andReturn([
                'credit_income_per_econ_level' => 0,
                'credit_income_per_worker' => 0,
                'credit_bonus_per_wealth_point' => 0,
                'bank_interest_rate' => 0,
                'citizen_growth_per_pop_level' => 0,
                'research_data_per_lab_level' => 20,
                'dark_matter_per_siphon_level' => 0.5
            ]);

        $this->mockArmoryService->shouldReceive('getAggregateBonus')->andReturn(0);

        $result = $this->service->calculateIncomePerTurn($userId, $resources, $stats, $structures, null);

        $this->assertEquals(100, $result['research_data_income']);
        $this->assertEquals(1.0, $result['dark_matter_income']);
    }

    public function testCalculateIncomePerTurnCalculatesCompoundingFormulas(): void
    {
        $userId = 1;
        $resources = $this->createMockResources($userId);
        $stats = $this->createMockStats($userId);
        
        // Setup Levels
        // Accounting: Level 5
        // Naquadah: Level 10
        // Dark Matter: Level 5
        $structures = $this->createMockStructures($userId, accountingLevel: 5, siphonLevel: 5, naquadahLevel: 10);

        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.turn_processor')
            ->andReturn([
                // Zeros for base stuff
                'credit_income_per_econ_level' => 0, 'credit_income_per_worker' => 0, 'credit_bonus_per_wealth_point' => 0,
                'bank_interest_rate' => 0, 'citizen_growth_per_pop_level' => 0, 'research_data_per_lab_level' => 0,
                
                // Accounting Firm
                'accounting_firm_base_bonus' => 0.01, // 1%
                'accounting_firm_multiplier' => 1.10, // 10% compounding for easy math
                
                // Dark Matter
                'dark_matter_per_siphon_level' => 10,
                'dark_matter_production_multiplier' => 1.05, // 5% compounding
                
                // Naquadah
                'naquadah_per_mining_complex_level' => 100,
                'naquadah_production_multiplier' => 1.02 // 2% compounding
            ]);

        $this->mockArmoryService->shouldReceive('getAggregateBonus')->andReturn(0);

        $result = $this->service->calculateIncomePerTurn($userId, $resources, $stats, $structures, null);

        // 1. Accounting Firm: 
        // Formula: Base * Level * (Mult ^ (Level - 1))
        // 0.01 * 5 * (1.10 ^ 4)
        // 0.05 * 1.4641 = 0.073205
        $this->assertEqualsWithDelta(0.073205, $result['accounting_firm_bonus_pct'], 0.000001);

        // 2. Dark Matter:
        // Formula: Base * Level * (Mult ^ (Level - 1))
        // 10 * 5 * (1.05 ^ 4)
        // 50 * 1.21550625 = 60.7753125
        $this->assertEqualsWithDelta(60.7753125, $result['dark_matter_income'], 0.000001);

        // 3. Naquadah:
        // Formula: Base * Level * (Mult ^ (Level - 1))
        // 100 * 10 * (1.02 ^ 9)
        // 1000 * 1.19509257 = 1195.09257
        $this->assertEqualsWithDelta(1195.09257, $result['naquadah_income'], 0.00001);
    }

    public function testCalculateShieldPower(): void
    {
        $structures = $this->createMockStructures(1, shieldLevel: 10);

        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.attack')
            ->andReturn(['shield_hp_per_level' => 5000]);

        $result = $this->service->calculateShieldPower($structures);

        $this->assertEquals(50000, $result['total_shield_hp']);
        $this->assertEquals(10, $result['level']);
    }

    public function testCalculateOffensePowerWithWarlordsThrone(): void
    {
        $userId = 1;
        $allianceId = 100;
        
        $resources = $this->createMockResources($userId, soldiers: 100);
        $stats = $this->createMockStats($userId);
        $structures = $this->createMockStructures($userId);

        $this->mockConfig->shouldReceive('get')->with('game_balance.attack')->andReturn([
            'power_per_soldier' => 10, 'power_per_offense_level' => 0, 'power_per_strength_point' => 0
        ]);
        $this->mockArmoryService->shouldReceive('getAggregateBonus')->andReturn(0);

        // 1. Define Training Ground (5% offense)
        $defTraining = new AllianceStructureDefinition(
            'training_ground', 'T', 'D', 1, 1, 'B', json_encode([['type' => 'offense_bonus_percent', 'value' => 0.05]])
        );
        // 2. Define Warlord's Throne (10% boost to bonuses)
        $defThrone = new AllianceStructureDefinition(
            'warlords_throne', 'W', 'D', 1, 1, 'B', json_encode([['type' => 'all_bonus_multiplier', 'value' => 0.10]])
        );

        $ownedTraining = new AllianceStructure(1, $allianceId, 'training_ground', 2, '', ''); // 10% base
        $ownedThrone = new AllianceStructure(2, $allianceId, 'warlords_throne', 1, '', ''); // 10% boost

        $this->mockAllianceStructRepo->shouldReceive('findByAllianceId')->with($allianceId)
            ->andReturn(['training_ground' => $ownedTraining, 'warlords_throne' => $ownedThrone]);

        $this->mockStructDefRepo->shouldReceive('getAllDefinitions')->andReturn([$defTraining, $defThrone]);

        // Logic:
        // Base Bonus (Training Lvl 2) = 0.05 * 2 = 0.10
        // Throne Multiplier (Lvl 1) = 0.10
        // Boosted Bonus = 0.10 * (1 + 0.10) = 0.11 (11%)
        // Total Power = 1000 * (1 + 0.11) = 1110

        $result = $this->service->calculateOffensePower($userId, $resources, $stats, $structures, $allianceId);

        $this->assertEquals(1110, $result['total']);
        $this->assertEqualsWithDelta(0.11, $result['alliance_bonus_pct'], 0.0001);
    }

    // --- Helpers ---

    private function createMockResources(int $userId, int $soldiers = 0, int $workers = 0, int $banked = 0): UserResource
    {
        return new UserResource(
            user_id: $userId,
            credits: 0,
            banked_credits: $banked,
            gemstones: 0,
            naquadah_crystals: 0.0,
            untrained_citizens: 0,
            workers: $workers,
            soldiers: $soldiers,
            guards: 0,
            spies: 0,
            sentries: 0
        );
    }

    private function createMockStats(int $userId, int $strengthPoints = 0, int $wealthPoints = 0): UserStats
    {
        return new UserStats(
            user_id: $userId,
            level: 1,
            experience: 0,
            net_worth: 0,
            war_prestige: 0,
            energy: 10,
            attack_turns: 10,
            level_up_points: 0,
            strength_points: $strengthPoints,
            constitution_points: 0,
            wealth_points: $wealthPoints,
            dexterity_points: 0,
            charisma_points: 0,
            deposit_charges: 0,
            last_deposit_at: null
        );
    }

    private function createMockStructures(int $userId, int $offenseLevel = 0, int $economyLevel = 0, int $accountingLevel = 0, int $qrlLevel = 0, int $naniteForgeLevel = 0, int $siphonLevel = 0, int $shieldLevel = 0, int $naquadahLevel = 0): UserStructure
    {
        return new UserStructure(
            user_id: $userId,
            fortification_level: 0,
            offense_upgrade_level: $offenseLevel,
            defense_upgrade_level: 0,
            spy_upgrade_level: 0,
            economy_upgrade_level: $economyLevel,
            population_level: 0,
            armory_level: 0,
            accounting_firm_level: $accountingLevel,
            quantum_research_lab_level: $qrlLevel,
            nanite_forge_level: $naniteForgeLevel,
            dark_matter_siphon_level: $siphonLevel,
            planetary_shield_level: $shieldLevel,
            naquadah_mining_complex_level: $naquadahLevel
        );
    }
}