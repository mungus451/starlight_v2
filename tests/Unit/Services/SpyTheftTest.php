<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\SpyService;
use App\Core\Config;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\SpyRepository;
use App\Models\Repositories\WarRepository;
use App\Models\Repositories\WarSpyLogRepository;
use App\Models\Services\ArmoryService;
use App\Models\Services\PowerCalculatorService;
use App\Models\Services\LevelUpService;
use App\Models\Services\NotificationService;
use App\Models\Services\EffectService;
use App\Models\Entities\User;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStats;
use App\Models\Entities\UserStructure;
use Mockery;
use PDO;

class SpyTheftTest extends TestCase
{
    private SpyService $service;
    private PDO|Mockery\MockInterface $mockDb;
    private Config|Mockery\MockInterface $mockConfig;
    private UserRepository|Mockery\MockInterface $mockUserRepo;
    private ResourceRepository|Mockery\MockInterface $mockResourceRepo;
    private StructureRepository|Mockery\MockInterface $mockStructureRepo;
    private StatsRepository|Mockery\MockInterface $mockStatsRepo;
    private SpyRepository|Mockery\MockInterface $mockSpyRepo;
    private ArmoryService|Mockery\MockInterface $mockArmoryService;
    private PowerCalculatorService|Mockery\MockInterface $mockPowerCalcService;
    private LevelUpService|Mockery\MockInterface $mockLevelUpService;
    private NotificationService|Mockery\MockInterface $mockNotificationService;
    private EffectService|Mockery\MockInterface $mockEffectService;
    private WarRepository|Mockery\MockInterface $mockWarRepo;
    private WarSpyLogRepository|Mockery\MockInterface $mockWarSpyLogRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockDb = Mockery::mock(PDO::class);
        $this->mockConfig = Mockery::mock(Config::class);
        $this->mockUserRepo = Mockery::mock(UserRepository::class);
        $this->mockResourceRepo = Mockery::mock(ResourceRepository::class);
        $this->mockStructureRepo = Mockery::mock(StructureRepository::class);
        $this->mockStatsRepo = Mockery::mock(StatsRepository::class);
        $this->mockSpyRepo = Mockery::mock(SpyRepository::class);
        $this->mockArmoryService = Mockery::mock(ArmoryService::class);
        $this->mockPowerCalcService = Mockery::mock(PowerCalculatorService::class);
        $this->mockLevelUpService = Mockery::mock(LevelUpService::class);
        $this->mockNotificationService = Mockery::mock(NotificationService::class);
        $this->mockEffectService = Mockery::mock(EffectService::class);
        $this->mockWarRepo = Mockery::mock(WarRepository::class);
        $this->mockWarSpyLogRepo = Mockery::mock(WarSpyLogRepository::class);

        $this->mockEffectService->shouldReceive('hasActiveEffect')->andReturn(false)->byDefault();
        $this->mockEffectService->shouldReceive('getEffectDetails')->andReturn(null)->byDefault();

        $this->service = new SpyService(
            $this->mockDb,
            $this->mockConfig,
            $this->mockUserRepo,
            $this->mockResourceRepo,
            $this->mockStructureRepo,
            $this->mockStatsRepo,
            $this->mockSpyRepo,
            $this->mockArmoryService,
            $this->mockPowerCalcService,
            $this->mockLevelUpService,
            $this->mockNotificationService,
            $this->mockEffectService,
            $this->mockWarRepo,
            $this->mockWarSpyLogRepo
        );
    }

    public function testConductOperationStealsPremiumResourcesOnSuccess(): void
    {
        $attackerId = 1;
        $defenderId = 2;
        $defenderName = 'Defender';

        $defender = $this->createMockUser($defenderId, $defenderName);
        $this->mockUserRepo->shouldReceive('findByCharacterName')->with($defenderName)->andReturn($defender);
        $this->mockUserRepo->shouldReceive('findById')->with($attackerId)->andReturn($this->createMockUser($attackerId, 'Attacker'));

        // Defender has 1000 crystals, 100 dark matter, and 500 protoform
        $defenderResources = new UserResource($defenderId, 10000, 0, 0, 1000.0, 50, 10, 10, 50, 0, 0, 0, 0, 100, 500.0);
        
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($attackerId)->andReturn($this->createMockResources($attackerId, 10000, 10));
        $this->mockStatsRepo->shouldReceive('findByUserId')->with($attackerId)->andReturn($this->createMockStats($attackerId));
        $this->mockStructureRepo->shouldReceive('findByUserId')->andReturn($this->createMockStructure($attackerId));
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($defenderId)->andReturn($defenderResources);

        $this->mockConfig->shouldReceive('get')->with('game_balance.spy')->andReturn([
            'cost_per_spy' => 100, 
            'attack_turn_cost' => 1,
            'base_success_multiplier' => 100.0, // Ensure success
            'base_success_chance_floor' => 0.0,
            'base_success_chance_cap' => 1.0,
            'base_counter_spy_multiplier' => 0.0,
            'base_counter_spy_chance_cap' => 0.0,
            'crystal_steal_rate' => 0.05, // 5%
            'dark_matter_steal_rate' => 0.02, // 2%
            'protoform_steal_rate' => 0.01 // 1%
        ]);
        $this->mockConfig->shouldReceive('get')->with('game_balance.xp.rewards')->andReturn(['spy_success' => 100]);

        $this->mockPowerCalcService->shouldReceive('calculateSpyPower')->andReturn(['total' => 1000]);
        $this->mockPowerCalcService->shouldReceive('calculateSentryPower')->andReturn(['total' => 1]);

        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction');
        $this->mockDb->shouldReceive('commit');

        $this->mockResourceRepo->shouldReceive('updateSpyAttacker')->once();
        $this->mockStatsRepo->shouldReceive('updateAttackTurns')->once();
        $this->mockStatsRepo->shouldReceive('incrementSpyStats')->once();
        $this->mockLevelUpService->shouldReceive('grantExperience')->once();

        // EXPECTED THEFT: 
        // 5% of 1000 = 50.0 (Naquadah)
        // 2% of 100 = 2 (Dark Matter)
        // 1% of 500 = 5.0 (Protoform)
        $this->mockResourceRepo->shouldReceive('updateResources')
            ->with($attackerId, 0, 50.0, 2, 5.0)
            ->once()
            ->andReturn(true);
        $this->mockResourceRepo->shouldReceive('updateResources')
            ->with($defenderId, 0, -50.0, -2, -5.0)
            ->once()
            ->andReturn(true);

        $this->mockSpyRepo->shouldReceive('createReport')
            ->with(
                $attackerId, $defenderId, 'success', Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(),
                Mockery::any(), // credits
                Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), // old optionals
                Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(),
                50.0, 2, 1000.0, 100, // Naquadah/DM (stolen/seen)
                5.0, 500.0 // Protoform (stolen/seen)
            )
            ->once()
            ->andReturn(99);

        $response = $this->service->conductOperation($attackerId, $defenderName);

        $this->assertTrue($response->isSuccess());
        $this->assertStringContainsString('50.00 crystals', $response->message);
        $this->assertStringContainsString('2 dark matter', $response->message);
        $this->assertStringContainsString('5.00 protoform', $response->message);
    }

    private function createMockUser(int $id, string $name): User
    {
        return new User($id, "{$name}@test.com", $name, null, null, null, null, null, 'hash', '2024-01-01', false);
    }

    private function createMockResources(int $userId, int $credits, int $spies, int $sentries = 0): UserResource
    {
        return new UserResource($userId, $credits, 0, 0, 0.0, 50, 10, 10, 50, $spies, $sentries);
    }

    private function createMockStats(int $userId): UserStats
    {
        return new UserStats($userId, 5, 1000, 500000, 100, 100, 50, 0, 0, 0, 0, 0, 0, 5, null, 0, 0, 0, 0);
    }

    private function createMockStructure(int $userId): UserStructure
    {
        return new UserStructure($userId, 10, 5, 3, 2, 8, 1, 1, 0);
    }
}
