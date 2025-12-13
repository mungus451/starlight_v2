<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\NpcService;
use App\Models\Entities\User;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Services\StructureService;
use App\Models\Services\TrainingService;
use App\Models\Services\ArmoryService;
use App\Models\Services\AttackService;
use App\Models\Services\AllianceStructureService;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStructure;
use App\Models\Entities\UserStats;
use App\Core\Logger;
use App\Core\ServiceResponse;
use Mockery;
use PDO;

class NpcServiceTest extends TestCase
{
    private NpcService $service;
    private PDO|Mockery\MockInterface $mockDb;
    private UserRepository|Mockery\MockInterface $mockUserRepo;
    private ResourceRepository|Mockery\MockInterface $mockResourceRepo;
    private StructureRepository|Mockery\MockInterface $mockStructureRepo;
    private StatsRepository|Mockery\MockInterface $mockStatsRepo;
    private AllianceRepository|Mockery\MockInterface $mockAllianceRepo;
    private StructureService|Mockery\MockInterface $mockStructureService;
    private TrainingService|Mockery\MockInterface $mockTrainingService;
    private ArmoryService|Mockery\MockInterface $mockArmoryService;
    private AttackService|Mockery\MockInterface $mockAttackService;
    private AllianceStructureService|Mockery\MockInterface $mockAllianceStructService;
    private Logger|Mockery\MockInterface $mockLogger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockDb = Mockery::mock(PDO::class);
        $this->mockUserRepo = Mockery::mock(UserRepository::class);
        $this->mockResourceRepo = Mockery::mock(ResourceRepository::class);
        $this->mockStructureRepo = Mockery::mock(StructureRepository::class);
        $this->mockStatsRepo = Mockery::mock(StatsRepository::class);
        $this->mockAllianceRepo = Mockery::mock(AllianceRepository::class);
        $this->mockStructureService = Mockery::mock(StructureService::class);
        $this->mockTrainingService = Mockery::mock(TrainingService::class);
        $this->mockArmoryService = Mockery::mock(ArmoryService::class);
        $this->mockAttackService = Mockery::mock(AttackService::class);
        $this->mockAllianceStructService = Mockery::mock(AllianceStructureService::class);
        $this->mockLogger = Mockery::mock(Logger::class);

        $this->service = new NpcService(
            $this->mockDb,
            $this->mockUserRepo,
            $this->mockResourceRepo,
            $this->mockStructureRepo,
            $this->mockStatsRepo,
            $this->mockAllianceRepo,
            $this->mockStructureService,
            $this->mockTrainingService,
            $this->mockArmoryService,
            $this->mockAttackService,
            $this->mockAllianceStructService,
            $this->mockLogger
        );

        // Relaxed Defaults to prevent crashes in non-tested managers
        $this->mockTrainingService->shouldReceive('trainUnits')->byDefault()->andReturn(ServiceResponse::success('Default'));
        $this->mockArmoryService->shouldReceive('getArmoryData')->byDefault()->andReturn(['armoryConfig' => [], 'inventory' => [], 'loadouts' => []]);
        $this->mockStatsRepo->shouldReceive('findByUserId')->byDefault()->andReturn($this->createMockStats(0, 0));
        $this->mockStatsRepo->shouldReceive('getTotalTargetCount')->byDefault()->andReturn(0);
        $this->mockAllianceRepo->shouldReceive('findById')->byDefault()->andReturn(null);
    }

    public function testRunNpcCycleDoesNothingIfNoNpcs(): void
    {
        $this->mockUserRepo->shouldReceive('findNpcs')->once()->andReturn([]);
        $this->mockLogger->shouldReceive('info')->byDefault();
        
        $this->service->runNpcCycle();
        $this->assertTrue(true);
    }

    public function testManageEconomyPrioritizesPopulationWhenLowCitizens(): void
    {
        $npcId = 10;
        $npc = $this->createMockUser($npcId, 'NPC');
        $this->mockUserRepo->shouldReceive('findNpcs')->once()->andReturn([$npc]);
        $this->mockLogger->shouldReceive('info')->byDefault();

        // Low Citizens (< 50)
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($npcId)->andReturn($this->createMockResource($npcId, 10)); // 10 citizens
        $this->mockStructureRepo->shouldReceive('findByUserId')->with($npcId)->andReturn($this->createMockStructure($npcId));

        // Expect 'population' upgrade
        $this->mockStructureService->shouldReceive('upgradeStructure')
            ->once()
            ->with($npcId, 'population')
            ->andReturn(ServiceResponse::success('Upgraded'));

        $this->mockArmoryService->shouldReceive('getArmoryData')->andReturn(['armoryConfig' => [], 'inventory' => [], 'loadouts' => []]);
        $this->mockStatsRepo->shouldReceive('findByUserId')->andReturn($this->createMockStats($npcId, 0)); // No attack turns

        $this->service->runNpcCycle();
        $this->assertTrue(true);
    }

    public function testManageEconomyDiversifiesWhenRich(): void
    {
        $npcId = 10;
        $npc = $this->createMockUser($npcId, 'NPC');
        $this->mockUserRepo->shouldReceive('findNpcs')->once()->andReturn([$npc]);
        $this->mockLogger->shouldReceive('info')->byDefault();

        // High Citizens, High Economy
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($npcId)->andReturn($this->createMockResource($npcId, 1000));
        $this->mockStructureRepo->shouldReceive('findByUserId')->with($npcId)->andReturn($this->createMockStructure($npcId, econLevel: 25));

        // Expect ANY upgrade (randomized, but definitely called)
        $this->mockStructureService->shouldReceive('upgradeStructure')
            ->once()
            ->with($npcId, Mockery::type('string'))
            ->andReturn(ServiceResponse::success('Upgraded'));

        $this->mockArmoryService->shouldReceive('getArmoryData')->andReturn(['armoryConfig' => [], 'inventory' => [], 'loadouts' => []]);
        $this->mockStatsRepo->shouldReceive('findByUserId')->andReturn($this->createMockStats($npcId, 0));

        $this->service->runNpcCycle();
        $this->assertTrue(true);
    }

    // --- Helpers ---

    private function createMockUser(int $id, string $name): User
    {
        return new User(
            id: $id,
            email: "{$name}@void.com",
            characterName: $name,
            bio: null,
            profile_picture_url: null,
            phone_number: null,
            alliance_id: null,
            alliance_role_id: null,
            passwordHash: 'hash',
            createdAt: '2024-01-01',
            is_npc: true
        );
    }

    private function createMockStructure(int $userId, int $econLevel = 10): UserStructure
    {
        return new UserStructure($userId, 10, 10, 10, 10, $econLevel, 10, 10, 0);
    }

    private function createMockResource(int $userId, int $citizens): UserResource
    {
        return new UserResource($userId, 1000000, 0, 0, 0.0, $citizens, 0, 0, 0, 0, 0);
    }

    private function createMockStats(int $userId, int $attackTurns): UserStats
    {
        return new UserStats($userId, 1, 0, 0, 0, 0, $attackTurns, 0, 0, 0, 0, 0, 0, 0, null);
    }
}