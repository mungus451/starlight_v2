<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\StructureService;
use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStructure;
use Mockery;
use PDO;

/**
 * Unit Tests for StructureService
 * 
 * Tests upgrade costs, resource validation, level calculations,
 * and structure upgrade logic without database dependencies.
 */
class StructureServiceTest extends TestCase
{
    private StructureService $service;
    private PDO|Mockery\MockInterface $mockDb;
    private Config|Mockery\MockInterface $mockConfig;
    private ResourceRepository|Mockery\MockInterface $mockResourceRepo;
    private StructureRepository|Mockery\MockInterface $mockStructureRepo;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks
        $this->mockDb = Mockery::mock(PDO::class);
        $this->mockDb->shouldReceive('beginTransaction')->byDefault();
        $this->mockDb->shouldReceive('commit')->byDefault();
        $this->mockDb->shouldReceive('rollBack')->byDefault();
        $this->mockDb->shouldReceive('inTransaction')->andReturn(true)->byDefault();
        $this->mockConfig = Mockery::mock(Config::class);
        $this->mockResourceRepo = Mockery::mock(ResourceRepository::class);
        $this->mockResourceRepo->shouldReceive('updateResources')->byDefault()->andReturn(true);
        $this->mockStructureRepo = Mockery::mock(StructureRepository::class);

        // Instantiate service
        $this->service = new StructureService(
            $this->mockDb,
            $this->mockConfig,
            $this->mockResourceRepo,
            $this->mockStructureRepo
        );
    }

    /**
     * Test: getStructureData returns correct structure with calculated costs
     */
    public function testGetStructureDataReturnsCorrectStructure(): void
    {
        $userId = 1;

        $mockResource = new UserResource(
            user_id: $userId,
            credits: 100000,
            banked_credits: 0,
            gemstones: 0,
            untrained_citizens: 50,
            workers: 10,
            soldiers: 100,
            guards: 50,
            spies: 10,
            sentries: 5
        );

        $mockStructure = new UserStructure(
            user_id: $userId,
            economy_upgrade_level: 8,
            population_level: 1,
            armory_level: 1
        );

        $structureConfig = [
            'housing' => ['base_cost' => 1000, 'multiplier' => 1.15, 'name' => 'Housing'],
            'training_camp' => ['base_cost' => 2000, 'multiplier' => 1.2, 'name' => 'Training Camp']
        ];

        $this->mockResourceRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockResource);

        $this->mockStructureRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockStructure);

        $this->mockConfig->shouldReceive('get')
            ->once()
            ->with('game_balance.structures', [])
            ->andReturn($structureConfig);

        $this->mockConfig->shouldReceive('get')
            ->once()
            ->with('game_balance.turn_processor', [])
            ->andReturn([]);

        $this->mockConfig->shouldReceive('get')
            ->once()
            ->with('game_balance.attack', [])
            ->andReturn([]);

        $this->mockConfig->shouldReceive('get')
            ->once()
            ->with('game_balance.spy', [])
            ->andReturn([]);

        // Act
        $result = $this->service->getStructureData($userId);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('resources', $result);
        $this->assertArrayHasKey('structures', $result);
        $this->assertArrayHasKey('costs', $result);
        $this->assertSame($mockResource, $result['resources']);
        $this->assertSame($mockStructure, $result['structures']);
        $this->assertIsArray($result['costs']);
    }

    /**
     * Test: upgradeStructure rejects invalid structure type
     */
    public function testUpgradeStructureRejectsInvalidType(): void
    {
        $this->mockConfig->shouldReceive('get')
            ->once()
            ->with('game_balance.structures.invalid_structure')
            ->andReturn(null);

        $response = $this->service->upgradeStructure(1, 'invalid_structure');

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('Invalid structure type.', $response->message);
    }

    /**
     * Test: upgradeStructure rejects when insufficient credits
     */
    public function testUpgradeStructureRejectsWhenInsufficientCredits(): void
    {
        $userId = 1;
        $structureKey = 'population';

        $mockResource = new UserResource(
            user_id: $userId,
            credits: 500, // Not enough
            banked_credits: 0,
            gemstones: 0,
            untrained_citizens: 0,
            workers: 0,
            soldiers: 0,
            guards: 0,
            spies: 0,
            sentries: 0
        );

        $mockStructure = new UserStructure(
            user_id: $userId,
            economy_upgrade_level: 0,
            population_level: 0,
            armory_level: 0
        );

        $structureConfig = [
            'base_cost' => 150000,
            'multiplier' => 1.6,
            'name' => 'Population'
        ];

        $this->mockConfig->shouldReceive('get')
            ->once()
            ->with('game_balance.structures.population')
            ->andReturn($structureConfig);

        $this->mockResourceRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockResource);

        $this->mockStructureRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockStructure);

        $response = $this->service->upgradeStructure($userId, $structureKey);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('Insufficient credits for upgrade.', $response->message);
    }

    /**
     * Test: upgradeStructure succeeds with valid resources and updates properly
     */
    public function testUpgradeStructureSucceedsWithValidResources(): void
    {
        $userId = 1;
        $structureKey = 'population';

        $mockResource = new UserResource(
            user_id: $userId,
            credits: 1000000,
            banked_credits: 0,
            gemstones: 0,
            untrained_citizens: 0,
            workers: 0,
            soldiers: 0,
            guards: 0,
            spies: 0,
            sentries: 0
        );

        $mockStructure = new UserStructure(
            user_id: $userId,
            economy_upgrade_level: 0,
            population_level: 0, // Level 0, so upgrade to level 1
            armory_level: 0
        );

        $structureConfig = [
            'base_cost' => 150000,
            'multiplier' => 1.6,
            'name' => 'Population'
        ];

        $this->mockConfig->shouldReceive('get')
            ->once()
            ->with('game_balance.structures.population')
            ->andReturn($structureConfig);

        $this->mockResourceRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockResource);

        $this->mockStructureRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockStructure);

        // Mock transaction methods
        $this->mockDb->shouldReceive('beginTransaction')
            ->once();

        $this->mockDb->shouldReceive('inTransaction')
            ->andReturn(false); // No active transaction initially

        $this->mockDb->shouldReceive('commit')
            ->once();

        // Cost calculation for level 0: base_cost = 150000
        $expectedCost = 150000;

        $this->mockStructureRepo->shouldReceive('updateStructureLevel')
            ->once()
            ->with($userId, 'population_level', 1)
            ->andReturn(true);

        $response = $this->service->upgradeStructure($userId, $structureKey);

        $this->assertTrue($response->isSuccess(), 'Response failed: ' . $response->message);
        $this->assertStringContainsString('Population upgraded to Level 1', $response->message);
    }

    /**
     * Test: cost calculation for level 0 returns base cost
     */
    public function testCostCalculationForLevel0ReturnsBaseCost(): void
    {
        $userId = 1;

        $mockResource = new UserResource(
            user_id: $userId,
            credits: 100000,
            banked_credits: 0,
            gemstones: 0,
            untrained_citizens: 0,
            workers: 0,
            soldiers: 0,
            guards: 0,
            spies: 0,
            sentries: 0
        );

        $mockStructure = new UserStructure(
            user_id: $userId,
            economy_upgrade_level: 0,
            population_level: 0,
            armory_level: 0
        );

        $structureConfig = [
            'housing' => ['base_cost' => 1000, 'multiplier' => 1.15, 'name' => 'Housing']
        ];

        $this->mockResourceRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockResource);

        $this->mockStructureRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockStructure);

        $this->mockConfig->shouldReceive('get')
            ->once()
            ->with('game_balance.structures', [])
            ->andReturn($structureConfig);

        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.turn_processor', [])
            ->andReturn([]);

        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.attack', [])
            ->andReturn([]);

        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.spy', [])
            ->andReturn([]);

        $result = $this->service->getStructureData($userId);

        // For level 0, cost should be base_cost
        $this->assertEquals(1000, $result['costs']['housing']['credits']);
    }

    // --- Helper ---
    private function createMockResource(int $userId, int $credits, int $gemstones = 0): UserResource
    {
        return new UserResource(
            user_id: $userId,
            credits: $credits,
            banked_credits: 0,
            gemstones: $gemstones,
            untrained_citizens: 0,
            workers: 0,
            soldiers: 0,
            guards: 0,
            spies: 0,
            sentries: 0
        );
    }

    private function createMockStructure(int $userId): UserStructure
    {
        return new UserStructure(
            user_id: $userId,
            economy_upgrade_level: 0,
            population_level: 0,
            armory_level: 0
        );
    }
}
