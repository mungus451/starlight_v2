<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStructure;
use App\Core\Config;
use App\Core\ServiceResponse;
use Mockery;

class TrainingServiceTest extends TestCase
{
    private TrainingService $service;
    private Config|Mockery\MockInterface $mockConfig;
    private ResourceRepository|Mockery\MockInterface $mockResourceRepo;
    private StructureRepository|Mockery\MockInterface $mockStructureRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConfig = Mockery::mock(Config::class);
        $this->mockConfig->shouldReceive('get')->with('game_balance.attack.power_per_soldier', 1)->andReturn(1)->byDefault();
        $this->mockConfig->shouldReceive('get')->with('game_balance.attack.power_per_guard', 1)->andReturn(1)->byDefault();
        $this->mockConfig->shouldReceive('get')->with('game_balance.spy.base_power_per_spy', 1)->andReturn(1)->byDefault();
        $this->mockConfig->shouldReceive('get')->with('game_balance.spy.base_power_per_sentry', 1)->andReturn(1)->byDefault();
        
        $this->mockResourceRepo = Mockery::mock(ResourceRepository::class);
        $this->mockStructureRepo = Mockery::mock(StructureRepository::class);

        $this->service = new TrainingService(
            $this->mockConfig,
            $this->mockResourceRepo,
            $this->mockStructureRepo
        );
    }

    public function testGetTrainingDataReturnsResourcesAndCosts(): void
    {
        $userId = 1;
        $mockResource = $this->createMockResource($userId);
        $mockCosts = ['soldiers' => ['credits' => 15000, 'citizens' => 1]];
        $mockStructures = new UserStructure(
            user_id: $userId,
            economy_upgrade_level: 0,
            population_level: 0,
            armory_level: 0
        );

        $this->mockResourceRepo->shouldReceive('findByUserId')->once()->with($userId)->andReturn($mockResource);
        $this->mockStructureRepo->shouldReceive('findByUserId')->once()->with($userId)->andReturn($mockStructures);
        $this->mockConfig->shouldReceive('get')->once()->with('game_balance.training', [])->andReturn($mockCosts);

        $result = $this->service->getTrainingData($userId);

        $this->assertIsArray($result);
        $this->assertSame($mockResource, $result['resources']);
        $this->assertSame($mockCosts, $result['costs']);
    }

    public function testTrainUnitsRejectsZeroAmount(): void
    {
        $response = $this->service->trainUnits(1, 'soldiers', 0);
        $this->assertServiceFailure($response);
        $this->assertServiceMessageContains($response, 'must be a positive number');
    }

    public function testTrainUnitsRejectsNegativeAmount(): void
    {
        $response = $this->service->trainUnits(1, 'soldiers', -5);
        $this->assertServiceFailure($response);
    }

    public function testTrainUnitsRejectsInvalidUnitType(): void
    {
        $userId = 1;
        $mockStructures = new UserStructure(
            user_id: $userId,
            economy_upgrade_level: 0,
            population_level: 0,
            armory_level: 0
        );
        $this->mockStructureRepo->shouldReceive('findByUserId')->once()->with($userId)->andReturn($mockStructures);
        $this->mockConfig->shouldReceive('get')->with('game_balance.training', [])->andReturn([]);
        $response = $this->service->trainUnits($userId, 'invalid_unit', 10);
        $this->assertServiceFailure($response);
    }

    public function testTrainUnitsFailsWhenResourcesNotFound(): void
    {
        $userId = 1;
        $mockStructures = new UserStructure(
            user_id: $userId,
            economy_upgrade_level: 0,
            population_level: 0,
            armory_level: 0
        );
        $this->mockStructureRepo->shouldReceive('findByUserId')->once()->with($userId)->andReturn($mockStructures);
        $this->mockConfig->shouldReceive('get')->with('game_balance.training', [])->andReturn(['soldiers' => ['credits' => 100, 'citizens' => 1]]);
        $this->mockResourceRepo->shouldReceive('findByUserId')->andReturn(null);
        
        $response = $this->service->trainUnits(1, 'soldiers', 5);
        $this->assertServiceFailure($response);
    }

    public function testTrainUnitsFailsWhenInsufficientCredits(): void
    {
        $userId = 1;
        $mockResource = $this->createMockResource($userId, credits: 100);
        $mockStructures = new UserStructure(
            user_id: $userId,
            economy_upgrade_level: 0,
            population_level: 0,
            armory_level: 0
        );
        $this->mockStructureRepo->shouldReceive('findByUserId')->once()->with($userId)->andReturn($mockStructures);
        
        $this->mockConfig->shouldReceive('get')->with('game_balance.training', [])->andReturn(['soldiers' => ['credits' => 1000, 'citizens' => 1]]);
        $this->mockResourceRepo->shouldReceive('findByUserId')->andReturn($mockResource);

        $response = $this->service->trainUnits($userId, 'soldiers', 1);
        $this->assertServiceFailure($response);
        $this->assertServiceMessageContains($response, 'not have enough credits');
    }

    public function testTrainUnitsFailsWhenInsufficientCitizens(): void
    {
        $userId = 1;
        $mockResource = $this->createMockResource($userId, credits: 10000, citizens: 0);
        $mockStructures = new UserStructure(
            user_id: $userId,
            economy_upgrade_level: 0,
            population_level: 0,
            armory_level: 0
        );
        $this->mockStructureRepo->shouldReceive('findByUserId')->once()->with($userId)->andReturn($mockStructures);
        
        $this->mockConfig->shouldReceive('get')->with('game_balance.training', [])->andReturn(['soldiers' => ['credits' => 100, 'citizens' => 1]]);
        $this->mockResourceRepo->shouldReceive('findByUserId')->andReturn($mockResource);

        $response = $this->service->trainUnits($userId, 'soldiers', 1);
        $this->assertServiceFailure($response);
        $this->assertServiceMessageContains($response, 'untrained citizens');
    }



    public function testTrainUnitsSuccessfullyTrainsSoldiers(): void
    {
        $userId = 1;
        $mockResource = $this->createMockResource($userId, credits: 100000, citizens: 20, soldiers: 3);
        $mockStructures = new UserStructure(
            user_id: $userId,
            economy_upgrade_level: 0,
            population_level: 0,
            armory_level: 0
        );
        $this->mockStructureRepo->shouldReceive('findByUserId')->once()->with($userId)->andReturn($mockStructures);
        
        $this->mockConfig->shouldReceive('get')->with('game_balance.training', [])->andReturn(['soldiers' => ['credits' => 1000, 'citizens' => 1]]);
        $this->mockResourceRepo->shouldReceive('findByUserId')->andReturn($mockResource);
        


        $this->mockResourceRepo->shouldReceive('updateTrainedUnits')->once()->andReturn(true);

        $response = $this->service->trainUnits($userId, 'soldiers', 5);
        $this->assertServiceSuccess($response);
    }

    public function testTrainUnitsSuccessfullyTrainsWorkers(): void
    {
        // Workers don't check army cap
        $userId = 1;
        $mockResource = $this->createMockResource($userId, credits: 100000, citizens: 20);
        $mockStructures = new UserStructure(
            user_id: $userId,
            economy_upgrade_level: 0,
            population_level: 0,
            armory_level: 0
        );
        $this->mockStructureRepo->shouldReceive('findByUserId')->once()->with($userId)->andReturn($mockStructures);
        
        $this->mockConfig->shouldReceive('get')->with('game_balance.training', [])->andReturn(['workers' => ['credits' => 100, 'citizens' => 1]]);
        $this->mockResourceRepo->shouldReceive('findByUserId')->andReturn($mockResource);
        $this->mockResourceRepo->shouldReceive('updateTrainedUnits')->once()->andReturn(true);

        $response = $this->service->trainUnits($userId, 'workers', 10);
        $this->assertServiceSuccess($response);
    }

    private function createMockResource(int $userId, int $credits = 100000, int $citizens = 50, int $soldiers = 0): UserResource
    {
        return new UserResource(
            user_id: $userId,
            credits: $credits,
            banked_credits: 0,
            gemstones: 0,
            untrained_citizens: $citizens,
            workers: 0,
            soldiers: $soldiers,
            guards: 0,
            spies: 0,
            sentries: 0
        );
    }
}