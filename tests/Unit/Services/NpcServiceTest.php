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
use App\Models\Services\CurrencyConverterService;
use App\Models\Services\SpyService;
use App\Models\Services\PowerCalculatorService;
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
    private CurrencyConverterService|Mockery\MockInterface $mockCurrencyService;
    private SpyService|Mockery\MockInterface $mockSpyService;
    private PowerCalculatorService|Mockery\MockInterface $mockPowerCalcService;
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
        $this->mockCurrencyService = Mockery::mock(CurrencyConverterService::class);
        $this->mockSpyService = Mockery::mock(SpyService::class);
        $this->mockPowerCalcService = Mockery::mock(PowerCalculatorService::class);
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
            $this->mockCurrencyService,
            $this->mockSpyService,
            $this->mockPowerCalcService,
            $this->mockLogger
        );

        // Relaxed Defaults
        $this->mockTrainingService->shouldReceive('trainUnits')->byDefault()->andReturn(ServiceResponse::success('Default'));
        $this->mockArmoryService->shouldReceive('getArmoryData')->byDefault()->andReturn(['armoryConfig' => [], 'inventory' => [], 'loadouts' => []]);
        $this->mockStatsRepo->shouldReceive('findByUserId')->byDefault()->andReturn($this->createMockStats(0, 0));
        $this->mockStatsRepo->shouldReceive('getTotalTargetCount')->byDefault()->andReturn(0);
        $this->mockAllianceRepo->shouldReceive('findById')->byDefault()->andReturn(null);
        
        // Structure Service Default for Economy Logic
        $this->mockStructureService->shouldReceive('getStructureData')->byDefault()->andReturn([
            'structures' => new UserStructure(0,0,0,0,0,0,0,0,0),
            'resources' => new UserResource(0,0,0,0,0,0,0,0,0,0,0),
            'costs' => []
        ]);
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

        // 1. Setup Data Objects
        $resources = $this->createMockResource($npcId, 10); // Low Citizens
        $structures = $this->createMockStructure($npcId);
        $costs = ['population' => ['credits' => 100, 'crystals' => 0]];

        // 2. Mock StructureService::getStructureData (Used by manageEconomy)
        $this->mockStructureService->shouldReceive('getStructureData')
            ->once()
            ->with($npcId)
            ->andReturn([
                'resources' => $resources,
                'structures' => $structures,
                'costs' => $costs
            ]);

        // 3. Mock Repos (Used by manageTraining/Armory etc)
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($npcId)->andReturn($resources);
        $this->mockStructureRepo->shouldReceive('findByUserId')->with($npcId)->andReturn($structures);

        // 4. Expect 'population' upgrade
        $this->mockStructureService->shouldReceive('upgradeStructure')
            ->once()
            ->with($npcId, 'population')
            ->andReturn(ServiceResponse::success('Upgraded'));

        // Defaults for other managers
        $this->mockArmoryService->shouldReceive('getArmoryData')->andReturn(['armoryConfig' => [], 'inventory' => [], 'loadouts' => []]);
        $this->mockStatsRepo->shouldReceive('findByUserId')->andReturn($this->createMockStats($npcId, 0));

        $this->service->runNpcCycle();
        $this->assertTrue(true);
    }

    public function testManageEconomyDiversifiesWhenRich(): void
    {
        $npcId = 10;
        $npc = $this->createMockUser($npcId, 'NPC');
        $this->mockUserRepo->shouldReceive('findNpcs')->once()->andReturn([$npc]);
        $this->mockLogger->shouldReceive('info')->byDefault();

        // 1. Setup Data Objects
        $resources = $this->createMockResource($npcId, 1000); // Rich
        $structures = $this->createMockStructure($npcId, econLevel: 25);
        $costs = [
            'economy_upgrade' => ['credits' => 100, 'crystals' => 0],
            'population' => ['credits' => 100, 'crystals' => 0],
            'offense_upgrade' => ['credits' => 100, 'crystals' => 0],
            'fortification' => ['credits' => 100, 'crystals' => 0],
            'defense_upgrade' => ['credits' => 100, 'crystals' => 0],
            'accounting_firm' => ['credits' => 100, 'crystals' => 0],
            'spy_upgrade' => ['credits' => 100, 'crystals' => 0],
            'armory' => ['credits' => 100, 'crystals' => 0]
        ];

        // 2. Mock StructureService::getStructureData
        $this->mockStructureService->shouldReceive('getStructureData')
            ->once()
            ->with($npcId)
            ->andReturn([
                'resources' => $resources,
                'structures' => $structures,
                'costs' => $costs
            ]);

        // 3. Mock Repos
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($npcId)->andReturn($resources);
        $this->mockStructureRepo->shouldReceive('findByUserId')->with($npcId)->andReturn($structures);

        // 4. Expect ANY upgrade
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
        return new UserStats($userId, 1, 0, 0, 0, 0, $attackTurns, 0, 0, 0, 0, 0, 0, 0, null, 0, 0, 0, 0);
    }
}