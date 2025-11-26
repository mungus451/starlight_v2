<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\TrainingService;
use App\Models\Repositories\ResourceRepository;
use App\Models\Entities\UserResource;
use App\Core\Config;
use App\Core\ServiceResponse;
use Mockery;

/**
 * Unit Tests for TrainingService
 * 
 * Tests business logic for training units without database dependencies.
 * All dependencies (Config, ResourceRepository) are mocked.
 */
class TrainingServiceTest extends TestCase
{
    private TrainingService $service;
    private Config|Mockery\MockInterface $mockConfig;
    private ResourceRepository|Mockery\MockInterface $mockResourceRepo;

    /**
     * Set up test dependencies before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks
        $this->mockConfig = Mockery::mock(Config::class);
        $this->mockResourceRepo = Mockery::mock(ResourceRepository::class);

        // Instantiate service with mocked dependencies
        $this->service = new TrainingService(
            $this->mockConfig,
            $this->mockResourceRepo
        );
    }

    /**
     * Test: getTrainingData returns resources and costs
     */
    public function testGetTrainingDataReturnsResourcesAndCosts(): void
    {
        // Arrange
        $userId = 1;
        $mockResource = new UserResource(
            user_id: $userId,
            credits: 100000,
            banked_credits: 0,
            gemstones: 0,
            naquadah_crystals: 0.0,
            untrained_citizens: 50,
            workers: 10,
            soldiers: 5,
            guards: 3,
            spies: 2,
            sentries: 1
        );

        $mockCosts = [
            'soldiers' => ['credits' => 15000, 'citizens' => 1],
            'guards' => ['credits' => 25000, 'citizens' => 1],
            'workers' => ['credits' => 5000, 'citizens' => 1]
        ];

        // Set up expectations
        $this->mockResourceRepo
            ->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockResource);

        $this->mockConfig
            ->shouldReceive('get')
            ->once()
            ->with('game_balance.training', [])
            ->andReturn($mockCosts);

        // Act
        $result = $this->service->getTrainingData($userId);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('resources', $result);
        $this->assertArrayHasKey('costs', $result);
        $this->assertSame($mockResource, $result['resources']);
        $this->assertSame($mockCosts, $result['costs']);
    }

    /**
     * Test: trainUnits rejects zero amount
     */
    public function testTrainUnitsRejectsZeroAmount(): void
    {
        // Act
        $response = $this->service->trainUnits(1, 'soldiers', 0);

        // Assert
        $this->assertServiceFailure($response);
        $this->assertServiceMessageContains($response, 'must be a positive number');
    }

    /**
     * Test: trainUnits rejects negative amount
     */
    public function testTrainUnitsRejectsNegativeAmount(): void
    {
        // Act
        $response = $this->service->trainUnits(1, 'soldiers', -5);

        // Assert
        $this->assertServiceFailure($response);
        $this->assertServiceMessageContains($response, 'must be a positive number');
    }

    /**
     * Test: trainUnits rejects invalid unit type
     */
    public function testTrainUnitsRejectsInvalidUnitType(): void
    {
        // Arrange
        $this->mockConfig
            ->shouldReceive('get')
            ->once()
            ->with('game_balance.training.invalid_unit')
            ->andReturn(null);

        // Act
        $response = $this->service->trainUnits(1, 'invalid_unit', 10);

        // Assert
        $this->assertServiceFailure($response);
        $this->assertServiceMessageContains($response, 'Invalid unit type');
    }

    /**
     * Test: trainUnits fails when user resources not found
     */
    public function testTrainUnitsFailsWhenResourcesNotFound(): void
    {
        // Arrange
        $unitCost = ['credits' => 15000, 'citizens' => 1];

        $this->mockConfig
            ->shouldReceive('get')
            ->once()
            ->with('game_balance.training.soldiers')
            ->andReturn($unitCost);

        $this->mockResourceRepo
            ->shouldReceive('findByUserId')
            ->once()
            ->with(1)
            ->andReturn(null);

        // Act
        $response = $this->service->trainUnits(1, 'soldiers', 5);

        // Assert
        $this->assertServiceFailure($response);
        $this->assertServiceMessageContains($response, 'Could not find your resource data');
    }

    /**
     * Test: trainUnits fails when insufficient credits
     */
    public function testTrainUnitsFailsWhenInsufficientCredits(): void
    {
        // Arrange
        $userId = 1;
        $unitCost = ['credits' => 15000, 'citizens' => 1];
        $amount = 10; // Needs 150,000 credits

        $mockResource = new UserResource(
            user_id: $userId,
            credits: 100000, // Only has 100,000
            banked_credits: 0,
            gemstones: 0,
            naquadah_crystals: 0.0,
            untrained_citizens: 50,
            workers: 0,
            soldiers: 0,
            guards: 0,
            spies: 0,
            sentries: 0
        );

        $this->mockConfig
            ->shouldReceive('get')
            ->once()
            ->with('game_balance.training.soldiers')
            ->andReturn($unitCost);

        $this->mockResourceRepo
            ->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockResource);

        // Act
        $response = $this->service->trainUnits($userId, 'soldiers', $amount);

        // Assert
        $this->assertServiceFailure($response);
        $this->assertServiceMessageContains($response, 'not have enough credits');
    }

    /**
     * Test: trainUnits fails when insufficient untrained citizens
     */
    public function testTrainUnitsFailsWhenInsufficientCitizens(): void
    {
        // Arrange
        $userId = 1;
        $unitCost = ['credits' => 15000, 'citizens' => 1];
        $amount = 10; // Needs 10 citizens

        $mockResource = new UserResource(
            user_id: $userId,
            credits: 200000, // Enough credits
            banked_credits: 0,
            gemstones: 0,
            naquadah_crystals: 0.0,
            untrained_citizens: 5, // Only 5 citizens
            workers: 0,
            soldiers: 0,
            guards: 0,
            spies: 0,
            sentries: 0
        );

        $this->mockConfig
            ->shouldReceive('get')
            ->once()
            ->with('game_balance.training.soldiers')
            ->andReturn($unitCost);

        $this->mockResourceRepo
            ->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockResource);

        // Act
        $response = $this->service->trainUnits($userId, 'soldiers', $amount);

        // Assert
        $this->assertServiceFailure($response);
        $this->assertServiceMessageContains($response, 'not have enough untrained citizens');
    }

    /**
     * Test: trainUnits successfully trains soldiers
     */
    public function testTrainUnitsSuccessfullyTrainsSoldiers(): void
    {
        // Arrange
        $userId = 1;
        $unitCost = ['credits' => 15000, 'citizens' => 1];
        $amount = 5;

        $mockResource = new UserResource(
            user_id: $userId,
            credits: 100000,
            banked_credits: 0,
            gemstones: 0,
            naquadah_crystals: 0.0,
            untrained_citizens: 20,
            workers: 10,
            soldiers: 3,
            guards: 2,
            spies: 1,
            sentries: 1
        );

        $this->mockConfig
            ->shouldReceive('get')
            ->once()
            ->with('game_balance.training.soldiers')
            ->andReturn($unitCost);

        $this->mockResourceRepo
            ->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockResource);

        // Expect updateTrainedUnits to be called with correct values
        $expectedCredits = 100000 - (15000 * 5); // 25,000
        $expectedCitizens = 20 - 5; // 15
        $expectedSoldiers = 3 + 5; // 8

        $this->mockResourceRepo
            ->shouldReceive('updateTrainedUnits')
            ->once()
            ->with(
                $userId,
                $expectedCredits,
                $expectedCitizens,
                10, // workers unchanged
                $expectedSoldiers,
                2,  // guards unchanged
                1,  // spies unchanged
                1   // sentries unchanged
            )
            ->andReturn(true);

        // Act
        $response = $this->service->trainUnits($userId, 'soldiers', $amount);

        // Assert
        $this->assertServiceSuccess($response);
        $this->assertServiceMessageContains($response, 'Training complete');
        $this->assertServiceMessageContains($response, '5');
        $this->assertServiceMessageContains($response, 'Soldiers');
    }

    /**
     * Test: trainUnits successfully trains workers
     */
    public function testTrainUnitsSuccessfullyTrainsWorkers(): void
    {
        // Arrange
        $userId = 2;
        $unitCost = ['credits' => 5000, 'citizens' => 1];
        $amount = 10;

        $mockResource = new UserResource(
            user_id: $userId,
            credits: 100000,
            banked_credits: 0,
            gemstones: 0,
            naquadah_crystals: 0.0,
            untrained_citizens: 20,
            workers: 5,
            soldiers: 0,
            guards: 0,
            spies: 0,
            sentries: 0
        );

        $this->mockConfig
            ->shouldReceive('get')
            ->once()
            ->with('game_balance.training.workers')
            ->andReturn($unitCost);

        $this->mockResourceRepo
            ->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockResource);

        $expectedCredits = 100000 - (5000 * 10); // 50,000
        $expectedCitizens = 20 - 10; // 10
        $expectedWorkers = 5 + 10; // 15

        $this->mockResourceRepo
            ->shouldReceive('updateTrainedUnits')
            ->once()
            ->with(
                $userId,
                $expectedCredits,
                $expectedCitizens,
                $expectedWorkers,
                0, // soldiers unchanged
                0, // guards unchanged
                0, // spies unchanged
                0  // sentries unchanged
            )
            ->andReturn(true);

        // Act
        $response = $this->service->trainUnits($userId, 'workers', $amount);

        // Assert
        $this->assertServiceSuccess($response);
        $this->assertServiceMessageContains($response, 'Training complete');
    }

    /**
     * Test: trainUnits fails when database update fails
     */
    public function testTrainUnitsFailsWhenDatabaseUpdateFails(): void
    {
        // Arrange
        $userId = 1;
        $unitCost = ['credits' => 15000, 'citizens' => 1];
        $amount = 2;

        $mockResource = new UserResource(
            user_id: $userId,
            credits: 50000,
            banked_credits: 0,
            gemstones: 0,
            naquadah_crystals: 0.0,
            untrained_citizens: 10,
            workers: 0,
            soldiers: 0,
            guards: 0,
            spies: 0,
            sentries: 0
        );

        $this->mockConfig
            ->shouldReceive('get')
            ->once()
            ->with('game_balance.training.soldiers')
            ->andReturn($unitCost);

        $this->mockResourceRepo
            ->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockResource);

        $this->mockResourceRepo
            ->shouldReceive('updateTrainedUnits')
            ->once()
            ->andReturn(false); // Database update fails

        // Act
        $response = $this->service->trainUnits($userId, 'soldiers', $amount);

        // Assert
        $this->assertServiceFailure($response);
        $this->assertServiceMessageContains($response, 'database error');
    }

    /**
     * Test: trainUnits correctly trains guards
     */
    public function testTrainUnitsSuccessfullyTrainsGuards(): void
    {
        // Arrange
        $userId = 1;
        $unitCost = ['credits' => 25000, 'citizens' => 1];
        $amount = 3;

        $mockResource = new UserResource(
            user_id: $userId,
            credits: 100000,
            banked_credits: 0,
            gemstones: 0,
            naquadah_crystals: 0.0,
            untrained_citizens: 10,
            workers: 0,
            soldiers: 0,
            guards: 5,
            spies: 0,
            sentries: 0
        );

        $this->mockConfig
            ->shouldReceive('get')
            ->once()
            ->with('game_balance.training.guards')
            ->andReturn($unitCost);

        $this->mockResourceRepo
            ->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockResource);

        $expectedCredits = 100000 - (25000 * 3); // 25,000
        $expectedCitizens = 10 - 3; // 7
        $expectedGuards = 5 + 3; // 8

        $this->mockResourceRepo
            ->shouldReceive('updateTrainedUnits')
            ->once()
            ->with(
                $userId,
                $expectedCredits,
                $expectedCitizens,
                0, // workers unchanged
                0, // soldiers unchanged
                $expectedGuards,
                0, // spies unchanged
                0  // sentries unchanged
            )
            ->andReturn(true);

        // Act
        $response = $this->service->trainUnits($userId, 'guards', $amount);

        // Assert
        $this->assertServiceSuccess($response);
    }
}
