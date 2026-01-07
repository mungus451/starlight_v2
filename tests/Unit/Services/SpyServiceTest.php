<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\SpyService;
use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\SpyRepository;
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

class SpyServiceTest extends TestCase
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
            $this->mockEffectService
        );
    }

    // --- Basic Validation Tests ---

    public function testConductOperationRejectsEmptyTarget(): void
    {
        $response = $this->service->conductOperation(1, '');
        $this->assertEquals('You must enter a target.', $response->message);
    }

    public function testConductOperationRejectsNonExistentTarget(): void
    {
        $this->mockUserRepo->shouldReceive('findByCharacterName')->with('NonExistent')->andReturn(null);
        $response = $this->service->conductOperation(1, 'NonExistent');
        $this->assertEquals("Character 'NonExistent' not found.", $response->message);
    }

    public function testConductOperationRejectsSelfSpying(): void
    {
        $attacker = $this->createMockUser(1, 'Attacker');
        $this->mockUserRepo->shouldReceive('findByCharacterName')->with('Attacker')->andReturn($attacker);
        $response = $this->service->conductOperation(1, 'Attacker');
        $this->assertEquals('You cannot spy on yourself.', $response->message);
    }

    public function testConductOperationRejectsWhenNoSpies(): void
    {
        $attacker = $this->createMockUser(1, 'Attacker');
        $defender = $this->createMockUser(2, 'Defender');
        $this->mockUserRepo->shouldReceive('findByCharacterName')->andReturn($defender);
        $this->mockUserRepo->shouldReceive('findById')->andReturn($attacker);
        
        $this->mockResourceRepo->shouldReceive('findByUserId')->with(1)->andReturn($this->createMockResources(1, 1000, 0));
        $this->mockStatsRepo->shouldReceive('findByUserId')->andReturn($this->createMockStats(1));
        $this->mockStructureRepo->shouldReceive('findByUserId')->andReturn($this->createMockStructure(1));
        $this->mockResourceRepo->shouldReceive('findByUserId')->with(2)->andReturn($this->createMockResources(2, 1000, 0));
        $this->mockStructureRepo->shouldReceive('findByUserId')->andReturn($this->createMockStructure(2));

        $this->mockConfig->shouldReceive('get')->andReturn(['cost_per_spy' => 100, 'attack_turn_cost' => 1]);

        $response = $this->service->conductOperation(1, 'Defender');
        $this->assertEquals('You have no spies to send.', $response->message);
    }

    // --- Advanced Logic Tests ---

    public function testConductOperationFailsWhenJammed(): void
    {
        $attackerId = 1;
        $defenderId = 2;
        $defenderName = 'Defender';

        $defender = $this->createMockUser($defenderId, $defenderName);
        $this->mockUserRepo->shouldReceive('findByCharacterName')->with($defenderName)->andReturn($defender);
        $this->mockUserRepo->shouldReceive('findById')->with($attackerId)->andReturn($this->createMockUser($attackerId, 'Attacker'));

        $this->mockResourceRepo->shouldReceive('findByUserId')->with($attackerId)->andReturn($this->createMockResources($attackerId, 10000, 10));
        $this->mockStatsRepo->shouldReceive('findByUserId')->with($attackerId)->andReturn($this->createMockStats($attackerId));
        $this->mockStructureRepo->shouldReceive('findByUserId')->andReturn($this->createMockStructure($attackerId));
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($defenderId)->andReturn($this->createMockResources($defenderId, 1000, 0));

        $this->mockConfig->shouldReceive('get')->with('game_balance.spy')->andReturn(['cost_per_spy' => 100, 'attack_turn_cost' => 1]);
        $this->mockConfig->shouldReceive('get')->with('game_balance.xp.rewards')->andReturn([]);

        // MOCK JAMMING
        $this->mockEffectService->shouldReceive('hasActiveEffect')
            ->once()
            ->with($defenderId, 'jamming')
            ->andReturn(true);

        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction');
        $this->mockDb->shouldReceive('commit');
        $this->mockResourceRepo->shouldReceive('updateSpyAttacker')->once();
        $this->mockStatsRepo->shouldReceive('updateAttackTurns')->once();

        $response = $this->service->conductOperation($attackerId, $defenderName);

        $this->assertTrue($response->isSuccess());
        $this->assertStringContainsString('CRITICAL FAILURE: Target signal is jammed', $response->message);
    }

    public function testConductOperationCaughtTriggersCombatAndNotifications(): void
    {
        $attackerId = 1;
        $defenderId = 2;
        $defenderName = 'Defender';

        $defender = $this->createMockUser($defenderId, $defenderName);
        $this->mockUserRepo->shouldReceive('findByCharacterName')->with($defenderName)->andReturn($defender);
        $this->mockUserRepo->shouldReceive('findById')->with($attackerId)->andReturn($this->createMockUser($attackerId, 'Attacker'));

        $this->mockResourceRepo->shouldReceive('findByUserId')->with($attackerId)->andReturn($this->createMockResources($attackerId, 10000, 10));
        $this->mockStatsRepo->shouldReceive('findByUserId')->with($attackerId)->andReturn($this->createMockStats($attackerId));
        $this->mockStructureRepo->shouldReceive('findByUserId')->andReturn($this->createMockStructure($attackerId));
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($defenderId)->andReturn($this->createMockResources($defenderId, 10000, 0, 100));

        $this->mockConfig->shouldReceive('get')->with('game_balance.spy')->andReturn([
            'cost_per_spy' => 100, 
            'attack_turn_cost' => 1,
            'base_success_multiplier' => 0.01,
            'base_success_chance_floor' => 0.0,
            'base_success_chance_cap' => 0.01,
            'base_counter_spy_multiplier' => 100.0,
            'base_counter_spy_chance_cap' => 1.0
        ]);
        $this->mockConfig->shouldReceive('get')->with('game_balance.xp.rewards')->andReturn(['spy_caught' => 10, 'defense_caught_spy' => 50]);

        $this->mockPowerCalcService->shouldReceive('calculateSpyPower')->andReturn(['total' => 100]);
        $this->mockPowerCalcService->shouldReceive('calculateSentryPower')->andReturn(['total' => 200]); // Ratio 2:1 ensures losses > 0

        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction');
        $this->mockDb->shouldReceive('commit');

        $this->mockResourceRepo->shouldReceive('updateSpyAttacker')->once();
        $this->mockResourceRepo->shouldReceive('updateSpyDefender')->once(); // Recovered
        $this->mockStatsRepo->shouldReceive('updateAttackTurns')->once();
        $this->mockStatsRepo->shouldReceive('incrementSpyStats')->once();
        $this->mockLevelUpService->shouldReceive('grantExperience')->twice();

        $this->mockSpyRepo->shouldReceive('createReport')->once()->andReturn(99);

        $this->mockNotificationService->shouldReceive('sendNotification')->once();

        $response = $this->service->conductOperation($attackerId, $defenderName);

        $this->assertTrue($response->isSuccess());
    }

    // --- Helpers ---

    private function createMockUser(int $id, string $name): User
    {
        return new User(
            id: $id,
            email: "{$name}@test.com",
            characterName: $name,
            bio: null,
            profile_picture_url: null,
            phone_number: null,
            alliance_id: null,
            alliance_role_id: null,
            passwordHash: 'hash',
            createdAt: '2024-01-01',
            is_npc: false
        );
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
