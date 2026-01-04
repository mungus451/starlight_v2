<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\PowerCalculatorService;
use App\Models\Services\ArmoryService;
use App\Models\Services\EffectService; // Import EffectService
use App\Core\Config;
use App\Models\Repositories\AllianceStructureRepository;
use App\Models\Repositories\AllianceStructureDefinitionRepository;
use App\Models\Repositories\EdictRepository;
use App\Models\Repositories\GeneralRepository;
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
    private EdictRepository|Mockery\MockInterface $mockEdictRepo;
    private GeneralRepository|Mockery\MockInterface $mockGeneralRepo;
    private EffectService|Mockery\MockInterface $mockEffectService; // Add Mock

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConfig = Mockery::mock(Config::class);
        $this->mockArmoryService = Mockery::mock(ArmoryService::class);
        $this->mockAllianceStructRepo = Mockery::mock(AllianceStructureRepository::class);
        $this->mockStructDefRepo = Mockery::mock(AllianceStructureDefinitionRepository::class);
        $this->mockEdictRepo = Mockery::mock(EdictRepository::class);
        $this->mockGeneralRepo = Mockery::mock(GeneralRepository::class);
        $this->mockEffectService = Mockery::mock(EffectService::class); // Instantiate Mock

        // Default: No active edicts
        $this->mockEdictRepo->shouldReceive('findActiveByUserId')->andReturn([]);
        
        // Default: General Config
        $this->mockConfig->shouldReceive('get')->with('game_balance.generals', [])
            ->andReturn(['base_capacity' => 500, 'capacity_per_general' => 10000])
            ->byDefault();
            
        // Default: No Generals
        $this->mockGeneralRepo->shouldReceive('findByUserId')->andReturn([])->byDefault();
        $this->mockGeneralRepo->shouldReceive('countByUserId')->andReturn(0)->byDefault();
        
        // Default: Elite Weapons
        $this->mockConfig->shouldReceive('get')->with('elite_weapons', [])->andReturn([])->byDefault();

        // Default: No active effects
        $this->mockEffectService->shouldReceive('hasActiveEffect')->andReturn(false)->byDefault();

        $this->service = new PowerCalculatorService(
            $this->mockConfig,
            $this->mockArmoryService,
            $this->mockAllianceStructRepo,
            $this->mockStructDefRepo,
            $this->mockEdictRepo,
            $this->mockGeneralRepo,
            $this->mockEffectService // Inject Mock
        );
    }

    public function testCalculateOffensePowerCalculatesCorrectlyWithoutAlliance(): void
    {
        $userId = 1;
        
        $resources = $this->createMockResources($userId, soldiers: 100);
        $stats = $this->createMockStats($userId, strengthPoints: 10);
        $structures = $this->createMockStructures($userId, offenseLevel: 5);

        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.attack')
            ->andReturn([
                'power_per_soldier' => 10,
                'power_per_offense_level' => 0.05,
                'power_per_strength_point' => 0.01
            ]);

        $this->mockArmoryService->shouldReceive('getAggregateBonus')
            ->with($userId, 'soldier', 'attack', 100)
            ->andReturn(500);

        $result = $this->service->calculateOffensePower($userId, $resources, $stats, $structures, null);

        $this->assertEquals(2025, $result['total']);
        $this->assertEquals(1500, $result['total_base_power']);
    }

    public function testCalculateOffensePowerCappedByArmyLimit(): void
    {
        $userId = 1;
        
        // 600 Soldiers. Cap is 500 (0 Generals).
        $resources = $this->createMockResources($userId, soldiers: 600);
        $stats = $this->createMockStats($userId);
        $structures = $this->createMockStructures($userId);

        $this->mockConfig->shouldReceive('get')->with('game_balance.attack')->andReturn([
            'power_per_soldier' => 10, 'power_per_offense_level' => 0, 'power_per_strength_point' => 0
        ]);

        // Armory bonus should be calculated on EFFECTIVE soldiers (500)
        $this->mockArmoryService->shouldReceive('getAggregateBonus')
            ->with($userId, 'soldier', 'attack', 500)
            ->andReturn(0);

        $result = $this->service->calculateOffensePower($userId, $resources, $stats, $structures, null);

        // Effective soldiers = 500
        // Power = 500 * 10 = 5000
        $this->assertEquals(5000, $result['total']);
        $this->assertEquals(500, $result['effective_soldiers']);
        $this->assertEquals(100, $result['ineffective_soldiers']);
    }

    public function testCalculateOffensePowerWithGeneralBonusAndMultiplier(): void
    {
        $userId = 1;
        $resources = $this->createMockResources($userId, soldiers: 500);
        $stats = $this->createMockStats($userId);
        $structures = $this->createMockStructures($userId);
        
        // 1 General equipped with "Warlord" weapon
        $this->mockGeneralRepo->shouldReceive('findByUserId')->with($userId)->andReturn([
            ['id' => 1, 'weapon_slot_1' => 'warlord_blade']
        ]);
        $this->mockGeneralRepo->shouldReceive('countByUserId')->with($userId)->andReturn(1); // Cap = 10500
        
        $this->mockConfig->shouldReceive('get')->with('elite_weapons', [])->andReturn([
            'warlord_blade' => [
                'modifiers' => ['flat_offense' => 1000, 'global_offense_mult' => 1.2]
            ]
        ]);
        
        $this->mockConfig->shouldReceive('get')->with('game_balance.attack')->andReturn([
            'power_per_soldier' => 10, 'power_per_offense_level' => 0, 'power_per_strength_point' => 0
        ]);
        
        $this->mockArmoryService->shouldReceive('getAggregateBonus')->andReturn(0);
        
        // Logic:
        // Effective Soldiers: 500 (Cap 10500)
        // Base Unit Power: 500 * 10 = 5000
        // General Flat: +1000
        // Total Base: 6000
        // Multiplier: 1.2
        // Total: 6000 * 1.2 = 7200
        
        $result = $this->service->calculateOffensePower($userId, $resources, $stats, $structures, null);
        
        $this->assertEquals(7200, $result['total']);
        $this->assertEquals(1000, $result['general_flat']);
        $this->assertEquals(1.2, $result['general_mult']);
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

        $result = $this->service->calculateIncomePerTurn($userId, $resources, $stats, $structures, null);

        $this->assertEquals(1904, $result['total_credit_income']);
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