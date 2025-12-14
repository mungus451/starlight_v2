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
        $this->mockConfig = Mockery::mock(Config::class);
        $this->mockResourceRepo = Mockery::mock(ResourceRepository::class);
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
            naquadah_crystals: 0.0,
            untrained_citizens: 50,
            workers: 10,
            soldiers: 100,
            guards: 50,
            spies: 10,
            sentries: 5
        );

        $mockStructure = new UserStructure(
            user_id: $userId,
            fortification_level: 10,
            offense_upgrade_level: 5,
            defense_upgrade_level: 3,
            spy_upgrade_level: 2,
            economy_upgrade_level: 8,
            population_level: 1,
            armory_level: 1,
            accounting_firm_level: 0
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
            naquadah_crystals: 0.0,
            untrained_citizens: 0,
            workers: 0,
            soldiers: 0,
            guards: 0,
            spies: 0,
            sentries: 0
        );

        $mockStructure = new UserStructure(
            user_id: $userId,
            fortification_level: 0,
            offense_upgrade_level: 0,
            defense_upgrade_level: 0,
            spy_upgrade_level: 0,
            economy_upgrade_level: 0,
            population_level: 0,
            armory_level: 0,
            accounting_firm_level: 0
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
            naquadah_crystals: 0.0,
            untrained_citizens: 0,
            workers: 0,
            soldiers: 0,
            guards: 0,
            spies: 0,
            sentries: 0
        );

        $mockStructure = new UserStructure(
            user_id: $userId,
            fortification_level: 0,
            offense_upgrade_level: 0,
            defense_upgrade_level: 0,
            spy_upgrade_level: 0,
            economy_upgrade_level: 0,
            population_level: 0, // Level 0, so upgrade to level 1
            armory_level: 0,
            accounting_firm_level: 0
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

        $this->mockResourceRepo->shouldReceive('updateResources')
            ->once()
            ->with($userId, -150000, 0, 0) // Expecting negative values for deduction
            ->andReturn(true);

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
            naquadah_crystals: 0.0,
            untrained_citizens: 0,
            workers: 0,
            soldiers: 0,
            guards: 0,
            spies: 0,
            sentries: 0
        );

        $mockStructure = new UserStructure(
            user_id: $userId,
            fortification_level: 0, // Level 0
            offense_upgrade_level: 0,
            defense_upgrade_level: 0,
            spy_upgrade_level: 0,
            economy_upgrade_level: 0,
            population_level: 0,
            armory_level: 0,
            accounting_firm_level: 0
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

    public function testGetStructureDataCalculatesCrystalCosts(): void
    {
        $userId = 1;
        $mockResource = $this->createMockResource($userId, 1000, 1000); // 1000 crystals
        $mockStructure = $this->createMockStructure($userId); // Lvl 0

        $structureConfig = [
            'portal' => [
                'base_cost' => 1000, 
                'base_crystal_cost' => 50, // Crystal cost!
                'multiplier' => 1.5, 
                'name' => 'Portal'
            ]
        ];

        $this->mockResourceRepo->shouldReceive('findByUserId')->with($userId)->andReturn($mockResource);
        $this->mockStructureRepo->shouldReceive('findByUserId')->with($userId)->andReturn($mockStructure);
        
        $this->mockConfig->shouldReceive('get')->with('game_balance.structures', [])->andReturn($structureConfig);
        $this->mockConfig->shouldReceive('get')->byDefault()->andReturn([]);

        $result = $this->service->getStructureData($userId);

        $this->assertEquals(1000, $result['costs']['portal']['credits']);
        $this->assertEquals(50, $result['costs']['portal']['crystals']);
    }

    public function testUpgradeStructureRejectsInsufficientCrystals(): void
    {
        $userId = 1;
        $structureKey = 'portal';

        // Plenty of credits, but 0 crystals
        $mockResource = $this->createMockResource($userId, 100000, 0); 
        $mockStructure = $this->createMockStructure($userId);

        $structureConfig = [
            'base_cost' => 1000,
            'base_crystal_cost' => 50,
            'multiplier' => 1.5,
            'name' => 'Portal'
        ];

        $this->mockConfig->shouldReceive('get')->with('game_balance.structures.portal')->andReturn($structureConfig);
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($userId)->andReturn($mockResource);
        $this->mockStructureRepo->shouldReceive('findByUserId')->with($userId)->andReturn($mockStructure);

        $response = $this->service->upgradeStructure($userId, $structureKey);

        $this->assertFalse($response->isSuccess());
        $this->assertStringContainsString('Insufficient naquadah crystals', $response->message);
    }

    public function testUpgradeStructureDeductsCrystals(): void
    {
        $userId = 1;
        $structureKey = 'portal';

        $mockResource = $this->createMockResource($userId, 100000, 100); // 100 crystals
        $mockStructure = $this->createMockStructure($userId);

        $structureConfig = [
            'base_cost' => 1000,
            'base_crystal_cost' => 50,
            'multiplier' => 1.5,
            'name' => 'Portal'
        ];

        $this->mockConfig->shouldReceive('get')->with('game_balance.structures.portal')->andReturn($structureConfig);
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($userId)->andReturn($mockResource);
        $this->mockStructureRepo->shouldReceive('findByUserId')->with($userId)->andReturn($mockStructure);

        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction');
        $this->mockDb->shouldReceive('commit');

        // Expect update with CRYSTAL deduction
        $this->mockResourceRepo->shouldReceive('updateResources')
            ->once()
            ->with($userId, -1000, -50, 0)
            ->andReturn(true);

        $this->mockStructureRepo->shouldReceive('updateStructureLevel')->once();

        $response = $this->service->upgradeStructure($userId, $structureKey);

        $this->assertTrue($response->isSuccess());
    }

    // --- Helper ---
    private function createMockResource(int $userId, int $credits, int $crystals): UserResource
    {
        return new UserResource(
            $userId, 
            $credits, 
            0, 
            0, 
            (float)$crystals, 
            0, 0, 0, 0, 0, 0
        );
    }

    private function createMockStructure(int $userId): UserStructure
    {
        return new UserStructure($userId, 0, 0, 0, 0, 0, 0, 0, 0);
    }
}
