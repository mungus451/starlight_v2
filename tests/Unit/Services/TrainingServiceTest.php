<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\TrainingService;
use App\Models\Services\GeneralService;
use App\Models\Repositories\ResourceRepository;
use App\Models\Entities\UserResource;
use App\Core\Config;
use App\Core\ServiceResponse;
use Mockery;

class TrainingServiceTest extends TestCase
{
    private TrainingService $service;
    private Config|Mockery\MockInterface $mockConfig;
    private ResourceRepository|Mockery\MockInterface $mockResourceRepo;
    private GeneralService|Mockery\MockInterface $mockGeneralService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConfig = Mockery::mock(Config::class);
        $this->mockResourceRepo = Mockery::mock(ResourceRepository::class);
        $this->mockGeneralService = Mockery::mock(GeneralService::class);

        $this->service = new TrainingService(
            $this->mockConfig,
            $this->mockResourceRepo,
            $this->mockGeneralService
        );
    }

    public function testGetTrainingDataReturnsResourcesAndCosts(): void
    {
        $userId = 1;
        $mockResource = $this->createMockResource($userId);
        $mockCosts = ['soldiers' => ['credits' => 15000, 'citizens' => 1]];

        $this->mockResourceRepo->shouldReceive('findByUserId')->once()->with($userId)->andReturn($mockResource);
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
        $this->mockConfig->shouldReceive('get')->with('game_balance.training.invalid_unit')->andReturn(null);
        $response = $this->service->trainUnits(1, 'invalid_unit', 10);
        $this->assertServiceFailure($response);
    }

    public function testTrainUnitsFailsWhenResourcesNotFound(): void
    {
        $this->mockConfig->shouldReceive('get')->andReturn(['credits' => 100, 'citizens' => 1]);
        $this->mockResourceRepo->shouldReceive('findByUserId')->andReturn(null);
        
        $response = $this->service->trainUnits(1, 'soldiers', 5);
        $this->assertServiceFailure($response);
    }

    public function testTrainUnitsFailsWhenInsufficientCredits(): void
    {
        $userId = 1;
        $mockResource = $this->createMockResource($userId, credits: 100);
        
        $this->mockConfig->shouldReceive('get')->andReturn(['credits' => 1000, 'citizens' => 1]);
        $this->mockResourceRepo->shouldReceive('findByUserId')->andReturn($mockResource);

        $response = $this->service->trainUnits($userId, 'soldiers', 1);
        $this->assertServiceFailure($response);
        $this->assertServiceMessageContains($response, 'not have enough credits');
    }

    public function testTrainUnitsFailsWhenInsufficientCitizens(): void
    {
        $userId = 1;
        $mockResource = $this->createMockResource($userId, credits: 10000, citizens: 0);
        
        $this->mockConfig->shouldReceive('get')->andReturn(['credits' => 100, 'citizens' => 1]);
        $this->mockResourceRepo->shouldReceive('findByUserId')->andReturn($mockResource);

        $response = $this->service->trainUnits($userId, 'soldiers', 1);
        $this->assertServiceFailure($response);
        $this->assertServiceMessageContains($response, 'untrained citizens');
    }

    public function testTrainUnitsFailsWhenArmyCapacityExceeded(): void
    {
        $userId = 1;
        $mockResource = $this->createMockResource($userId, credits: 100000, citizens: 10, soldiers: 10);
        
        $this->mockConfig->shouldReceive('get')->andReturn(['credits' => 100, 'citizens' => 1]);
        $this->mockResourceRepo->shouldReceive('findByUserId')->andReturn($mockResource);
        
        // Cap is 10. Trying to add 1. Total 11 > 10.
        $this->mockGeneralService->shouldReceive('getArmyCapacity')->with($userId)->andReturn(10);

        $response = $this->service->trainUnits($userId, 'soldiers', 1);
        $this->assertServiceFailure($response);
        $this->assertServiceMessageContains($response, 'Army Limit Reached');
    }

    public function testTrainUnitsSuccessfullyTrainsSoldiers(): void
    {
        $userId = 1;
        $mockResource = $this->createMockResource($userId, credits: 100000, citizens: 20, soldiers: 3);
        
        $this->mockConfig->shouldReceive('get')->with('game_balance.training.soldiers')->andReturn(['credits' => 1000, 'citizens' => 1]);
        $this->mockResourceRepo->shouldReceive('findByUserId')->andReturn($mockResource);
        
        // Cap is 100.
        $this->mockGeneralService->shouldReceive('getArmyCapacity')->with($userId)->andReturn(100);

        $this->mockResourceRepo->shouldReceive('updateTrainedUnits')->once()->andReturn(true);

        $response = $this->service->trainUnits($userId, 'soldiers', 5);
        $this->assertServiceSuccess($response);
    }

    public function testTrainUnitsSuccessfullyTrainsWorkers(): void
    {
        // Workers don't check army cap
        $userId = 1;
        $mockResource = $this->createMockResource($userId, credits: 100000, citizens: 20);
        
        $this->mockConfig->shouldReceive('get')->with('game_balance.training.workers')->andReturn(['credits' => 100, 'citizens' => 1]);
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
            naquadah_crystals: 0.0,
            untrained_citizens: $citizens,
            workers: 0,
            soldiers: $soldiers,
            guards: 0,
            spies: 0,
            sentries: 0,
            untraceable_chips: 0,
            research_data: 0,
            dark_matter: 0,
            protoform: 0.0
        );
    }
}