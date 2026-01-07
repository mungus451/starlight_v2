<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\BlackMarketService;
use App\Core\Config;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\BountyRepository;
use App\Models\Services\AttackService;
use App\Models\Repositories\BlackMarketLogRepository;
use App\Models\Services\EffectService;
use App\Models\Repositories\HouseFinanceRepository;
use App\Models\Services\LevelUpService;
use App\Models\Entities\UserResource;
use App\Models\Repositories\StructureRepository;
use App\Models\Entities\UserStructure;
use Mockery;
use PDO;

class BlackMarketVoidEffectsTest extends TestCase
{
    private $db;
    private $config;
    private $resourceRepo;
    private $statsRepo;
    private $userRepo;
    private $bountyRepo;
    private $attackService;
    private $logRepo;
    private $effectService;
    private $houseFinanceRepo;
    private $levelUpService;
    private $structureRepo;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Core Mocks
        $this->db = $this->createMockPDO();
        $this->db->shouldReceive('beginTransaction')->byDefault();
        $this->db->shouldReceive('commit')->byDefault();
        $this->db->shouldReceive('rollBack')->byDefault();
        $this->db->shouldReceive('inTransaction')->andReturn(true)->byDefault();

        $this->config = Mockery::mock(Config::class);
        
        // 2. Repository Mocks
        $this->resourceRepo = Mockery::mock(ResourceRepository::class);
        $this->statsRepo = Mockery::mock(StatsRepository::class);
        $this->userRepo = Mockery::mock(UserRepository::class);
        $this->bountyRepo = Mockery::mock(BountyRepository::class);
        $this->attackService = Mockery::mock(AttackService::class);
        $this->logRepo = Mockery::mock(BlackMarketLogRepository::class);
        $this->effectService = Mockery::mock(EffectService::class);
        $this->houseFinanceRepo = Mockery::mock(HouseFinanceRepository::class);
        $this->levelUpService = Mockery::mock(LevelUpService::class);
        $this->structureRepo = Mockery::mock(StructureRepository::class);

        // 3. Service Instantiation
        $this->service = new BlackMarketService(
            $this->db,
            $this->config,
            $this->resourceRepo,
            $this->statsRepo,
            $this->userRepo,
            $this->bountyRepo,
            $this->attackService,
            $this->logRepo,
            $this->effectService,
            $this->houseFinanceRepo,
            $this->levelUpService,
            $this->structureRepo
        );

        // Common Config Setup
        $this->config->shouldReceive('get')
            ->with('black_market.costs.void_container', 100)
            ->andReturn(100);
        $this->config->shouldReceive('get')
            ->with('game_balance.black_market')
            ->andReturn([])->byDefault();

        // Common Resource Setup (Sufficient Funds)
        $resources = new UserResource(
            user_id: 1,
            credits: 0,
            banked_credits: 0,
            gemstones: 0,
            naquadah_crystals: 1000.0,
            untrained_citizens: 0,
            workers: 0,
            soldiers: 0,
            guards: 0,
            spies: 0,
            sentries: 0,
            untraceable_chips: 0,
            research_data: 0,
            dark_matter: 0.0,
            protoform: 0.0
        );
        
        $this->resourceRepo->shouldReceive('findByUserId')
            ->with(1)
            ->andReturn($resources)
            ->byDefault();

        $this->resourceRepo->shouldReceive('updateResources')
            ->with(1, 0, Mockery::any())
            ->andReturn(true)
            ->byDefault();

        $structures = new UserStructure(
            user_id: 1,
            fortification_level: 0,
            offense_upgrade_level: 0,
            defense_upgrade_level: 0,
            spy_upgrade_level: 0,
            economy_upgrade_level: 0,
            population_level: 0,
            armory_level: 0,
            accounting_firm_level: 0
        );
        $this->structureRepo->shouldReceive('findByUserId')
            ->with(1)
            ->andReturn($structures)
            ->byDefault();
            
        $this->logRepo->shouldReceive('log')->byDefault();
    }

    public function testApplyOffenseBuff(): void
    {
        // Setup Loot Table: Only Offense Buff
        $loot = [
            'offense_buff' => [
                'weight' => 100,
                'type' => 'buff',
                'buff_key' => 'void_offense_boost',
                'duration' => 1440,
                'text' => 'Buff Applied Test'
            ]
        ];
        
        $this->config->shouldReceive('get')
            ->with('black_market.void_container_loot', [])
            ->andReturn($loot);

        // Expectation: Apply Effect
        $this->effectService->shouldReceive('applyEffect')
            ->once()
            ->with(1, 'void_offense_boost', 1440);

        // Run
        $response = $this->service->openVoidContainer(1);

        $this->assertServiceSuccess($response);
        $this->assertServiceMessageContains($response, 'Buff Applied Test');
    }

    public function testApplyDefenseDebuff(): void
    {
        // Setup Loot Table: Only Defense Debuff
        $loot = [
            'defense_debuff' => [
                'weight' => 100,
                'type' => 'debuff',
                'buff_key' => 'void_defense_penalty',
                'duration' => 360,
                'text' => 'Debuff Applied Test'
            ]
        ];

        $this->config->shouldReceive('get')
            ->with('black_market.void_container_loot', [])
            ->andReturn($loot);

        // Expectation: Apply Effect
        $this->effectService->shouldReceive('applyEffect')
            ->once()
            ->with(1, 'void_defense_penalty', 360);

        // Run
        $response = $this->service->openVoidContainer(1);

        $this->assertServiceSuccess($response); // Debuff is a valid "successful" transaction outcome
        $this->assertEquals('negative', $response->data['outcome_type']);
        $this->assertServiceMessageContains($response, 'Debuff Applied Test');
    }

    public function testSafehouseClearWithActiveCooldown(): void
    {
        // Setup Loot Table: Clear Cooldown
        $loot = [
            'cooldown_clear' => [
                'weight' => 100,
                'type' => 'buff',
                'buff_key' => 'action_clear_safehouse',
                'text' => 'Cooldown Reset Test'
            ]
        ];

        $this->config->shouldReceive('get')
            ->with('black_market.void_container_loot', [])
            ->andReturn($loot);

        // Mock: Cooldown IS active
        $this->effectService->shouldReceive('hasActiveEffect')
            ->with(1, 'safehouse_cooldown')
            ->andReturn(true);

        // Expectation: Break Effect
        $this->effectService->shouldReceive('breakEffect')
            ->once()
            ->with(1, 'safehouse_cooldown');

        // Run
        $response = $this->service->openVoidContainer(1);

        $this->assertServiceSuccess($response);
        $this->assertServiceMessageContains($response, 'Cooldown Reset Test');
    }

    public function testSafehouseClearWithNoCooldown(): void
    {
        // Setup Loot Table
        $loot = [
            'cooldown_clear' => [
                'weight' => 100,
                'type' => 'buff',
                'buff_key' => 'action_clear_safehouse'
            ]
        ];

        $this->config->shouldReceive('get')
            ->with('black_market.void_container_loot', [])
            ->andReturn($loot);

        // Mock: Cooldown is NOT active
        $this->effectService->shouldReceive('hasActiveEffect')
            ->with(1, 'safehouse_cooldown')
            ->andReturn(false);

        // Expectation: Do NOT break effect
        $this->effectService->shouldNotReceive('breakEffect');

        // Run
        $response = $this->service->openVoidContainer(1);

        $this->assertServiceSuccess($response);
        $this->assertServiceMessageContains($response, 'No cooldown was active');
    }

    public function testCursedCrystals(): void
    {
        // Setup Loot Table: Cursed Crystals
        $loot = [
            'cursed_crystals' => [
                'weight' => 100,
                'type' => 'cursed',
                'resource' => 'crystals',
                'min' => 100,
                'max' => 100,
                'debuff_key' => 'radiation_sickness',
                'duration' => 240,
                'text' => 'Radiation Test'
            ]
        ];

        $this->config->shouldReceive('get')
            ->with('black_market.void_container_loot', [])
            ->andReturn($loot);

        // Expectation 1: Apply Effect (Debuff)
        $this->effectService->shouldReceive('applyEffect')
            ->once()
            ->with(1, 'radiation_sickness', 240);

        // Expectation 2: Grant Resource (Crystals)
        // Note: The service calls updateResources(userId, 0, qty) for crystals
        $this->resourceRepo->shouldReceive('updateResources')
            ->with(1, 0, 100)
            ->once()
            ->andReturn(true);

        // Run
        $response = $this->service->openVoidContainer(1);

        $this->assertServiceSuccess($response);
        $this->assertEquals('warning', $response->data['outcome_type']); 
        
        $this->assertServiceMessageContains($response, 'Radiation Test');
    }

    public function testUnitLossTrapDoesNotGoNegative(): void
    {
        // Setup Loot Table: Unit Loss Trap
        $loot = [
            'ambush_soldiers' => [
                'weight' => 100,
                'type' => 'unit_loss',
                'unit' => 'soldiers',
                'min' => 10,
                'max' => 10,
                'text' => 'Ambush Test'
            ]
        ];

        $this->config->shouldReceive('get')
            ->with('black_market.void_container_loot', [])
            ->andReturn($loot);

        // Note: User has 0 soldiers in setUp default resource.
        // Logic: max(0, 0 - 10) = 0.

        $this->resourceRepo->shouldReceive('updateTrainedUnits')
            ->once()
            ->with(
                1, 
                0, // credits (from setUp)
                0, // untrained (from setUp)
                0, // workers (from setUp)
                0, // new soldiers (should be 0, not -10)
                0, // guards (from setUp)
                0, // spies (from setUp)
                0  // sentries (from setUp)
            )
            ->andReturn(true);

        // Run
        $response = $this->service->openVoidContainer(1);

        $this->assertServiceSuccess($response);
        $this->assertEquals('negative', $response->data['outcome_type']);
        $this->assertServiceMessageContains($response, 'Ambush Test');
    }
}
