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

    private function createMockStructures(int $userId, int $offenseLevel = 0, int $economyLevel = 0, int $accountingLevel = 0): UserStructure
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
            accounting_firm_level: $accountingLevel
        );
    }
}
