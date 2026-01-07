<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\ArmoryService;
use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\ArmoryRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStructure;
use App\Models\Entities\UserStats;
use Mockery;
use PDO;

/**
 * Unit Tests for ArmoryService
 * 
 * Tests equipment manufacturing, loadout assignment, stat bonuses,
 * and charisma discount calculations without database dependencies.
 */
class ArmoryServiceTest extends TestCase
{
    private ArmoryService $service;
    private PDO|Mockery\MockInterface $mockDb;
    private Config|Mockery\MockInterface $mockConfig;
    private ArmoryRepository|Mockery\MockInterface $mockArmoryRepo;
    private ResourceRepository|Mockery\MockInterface $mockResourceRepo;
    private StructureRepository|Mockery\MockInterface $mockStructureRepo;
    private StatsRepository|Mockery\MockInterface $mockStatsRepo;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks
        $this->mockDb = Mockery::mock(PDO::class);
        $this->mockConfig = Mockery::mock(Config::class);
        $this->mockArmoryRepo = Mockery::mock(ArmoryRepository::class);
        $this->mockResourceRepo = Mockery::mock(ResourceRepository::class);
        $this->mockStructureRepo = Mockery::mock(StructureRepository::class);
        $this->mockStatsRepo = Mockery::mock(StatsRepository::class);

        // Mock armory config
        $this->mockConfig->shouldReceive('get')
            ->with('armory_items', [])
            ->andReturn($this->getMockArmoryConfig())
            ->byDefault();

        // Instantiate service
        $this->service = new ArmoryService(
            $this->mockDb,
            $this->mockConfig,
            $this->mockArmoryRepo,
            $this->mockResourceRepo,
            $this->mockStructureRepo,
            $this->mockStatsRepo
        );
    }

    /**
     * Test: getArmoryData returns correct structure
     */
    public function testGetArmoryDataReturnsCorrectStructure(): void
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

        $mockStats = new UserStats(
            user_id: $userId,
            level: 5,
            experience: 1000,
            net_worth: 500000,
            war_prestige: 100,
            energy: 100,
            attack_turns: 50,
            level_up_points: 0,
            strength_points: 0,
            constitution_points: 0,
            wealth_points: 0,
            dexterity_points: 0,
            charisma_points: 0,
            deposit_charges: 5,
            last_deposit_at: null,
            battles_won: 0,
            battles_lost: 0,
            spy_successes: 0,
            spy_failures: 0
        );

        $this->mockResourceRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockResource);

        $this->mockStructureRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockStructure);

        $this->mockStatsRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockStats);

        $this->mockArmoryRepo->shouldReceive('getInventory')
            ->once()
            ->with($userId)
            ->andReturn([]);

        $this->mockArmoryRepo->shouldReceive('getUnitLoadouts')
            ->once()
            ->with($userId)
            ->andReturn([]);

        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.armory', [])
            ->andReturn([
                'discount_per_charisma' => 0.01,
                'max_discount' => 0.75
            ]);

        // Act
        $result = $this->service->getArmoryData($userId);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('userResources', $result);
        $this->assertArrayHasKey('userStructures', $result);
        $this->assertArrayHasKey('userStats', $result);
        $this->assertArrayHasKey('manufacturingData', $result);
        $this->assertArrayHasKey('inventory', $result);
        $this->assertArrayHasKey('loadouts', $result);
    }

    /**
     * Test: manufactureItem rejects zero or negative quantity
     */
    public function testManufactureItemRejectsInvalidQuantity(): void
    {
        $response = $this->service->manufactureItem(1, 'test_item', 0);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('Quantity must be a positive number.', $response->message);
    }

    /**
     * Test: manufactureItem rejects invalid item key
     */
    public function testManufactureItemRejectsInvalidItemKey(): void
    {
        $response = $this->service->manufactureItem(1, 'nonexistent_item', 1);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('Invalid item selected.', $response->message);
    }

    /**
     * Test: manufactureItem rejects when armory level too low
     */
    public function testManufactureItemRejectsWhenArmoryLevelTooLow(): void
    {
        $userId = 1;
        $itemKey = 'soldier_weapon_tier1';

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
            fortification_level: 0,
            offense_upgrade_level: 0,
            defense_upgrade_level: 0,
            spy_upgrade_level: 0,
            economy_upgrade_level: 0,
            population_level: 0,
            armory_level: 0, // Armory level 0
            accounting_firm_level: 0
        );

        $mockStats = new UserStats(
            user_id: $userId,
            level: 1,
            experience: 0,
            net_worth: 0,
            war_prestige: 0,
            energy: 100,
            attack_turns: 50,
            level_up_points: 0,
            strength_points: 0,
            constitution_points: 0,
            wealth_points: 0,
            dexterity_points: 0,
            charisma_points: 0,
            deposit_charges: 5,
            last_deposit_at: null,
            battles_won: 0,
            battles_lost: 0,
            spy_successes: 0,
            spy_failures: 0
        );

        $this->mockResourceRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockResource);

        $this->mockStructureRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockStructure);

        $this->mockStatsRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockStats);

        $this->mockArmoryRepo->shouldReceive('getInventory')
            ->once()
            ->with($userId)
            ->andReturn([]);

        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.armory', [])
            ->andReturn([]);

        $response = $this->service->manufactureItem($userId, $itemKey, 1);

        $this->assertFalse($response->isSuccess());
        $this->assertStringContainsString('must have an Armory', $response->message);
    }

    /**
     * Test: manufactureItem rejects when insufficient credits
     */
    public function testManufactureItemRejectsWhenInsufficientCredits(): void
    {
        $userId = 1;
        $itemKey = 'soldier_weapon_tier1';

        $mockResource = new UserResource(
            user_id: $userId,
            credits: 100, // Not enough
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
            armory_level: 1, // Armory level 1
            accounting_firm_level: 0
        );

        $mockStats = new UserStats(
            user_id: $userId,
            level: 1,
            experience: 0,
            net_worth: 0,
            war_prestige: 0,
            energy: 100,
            attack_turns: 50,
            level_up_points: 0,
            strength_points: 0,
            constitution_points: 0,
            wealth_points: 0,
            dexterity_points: 0,
            charisma_points: 0,
            deposit_charges: 5,
            last_deposit_at: null,
            battles_won: 0,
            battles_lost: 0,
            spy_successes: 0,
            spy_failures: 0
        );

        $this->mockResourceRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockResource);

        $this->mockStructureRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockStructure);

        $this->mockStatsRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockStats);

        $this->mockArmoryRepo->shouldReceive('getInventory')
            ->once()
            ->with($userId)
            ->andReturn([]);

        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.armory', [])
            ->andReturn([
                'discount_per_charisma' => 0.01,
                'max_discount' => 0.75
            ]);

        $response = $this->service->manufactureItem($userId, $itemKey, 1);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('You do not have enough credits.', $response->message);
    }

    /**
     * Test: equipItem rejects empty unit or category key
     */
    public function testEquipItemRejectsInvalidData(): void
    {
        $response = $this->service->equipItem(1, '', 'weapon', 'item_key');

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('Invalid loadout data provided.', $response->message);
    }

    /**
     * Test: equipItem allows unequipping (empty item key)
     */
    public function testEquipItemAllowsUnequipping(): void
    {
        $userId = 1;

        $this->mockArmoryRepo->shouldReceive('clearLoadoutSlot')
            ->once()
            ->with($userId, 'soldiers', 'weapon');

        $response = $this->service->equipItem($userId, 'soldiers', 'weapon', '');

        $this->assertTrue($response->isSuccess());
        $this->assertStringContainsString('cleared', $response->message);
    }

    // Helper methods

    private function getMockArmoryConfig(): array
    {
        return [
            'soldiers' => [
                'title' => 'Soldiers',
                'categories' => [
                    'weapon' => [
                        'title' => 'Weapon',
                        'items' => [
                            'soldier_weapon_tier1' => [
                                'name' => 'Basic Rifle',
                                'cost' => 1000,
                                'armory_level_req' => 1,
                                'tier' => 1,
                                'offense_bonus' => 10
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
