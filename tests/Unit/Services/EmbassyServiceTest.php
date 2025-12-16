<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\EmbassyService;
use App\Models\Repositories\EdictRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Entities\UserStructure;
use App\Models\Entities\EdictDefinition;
use App\Models\Entities\UserEdict;
use Mockery;

class EmbassyServiceTest extends TestCase
{
    private EmbassyService $service;
    private EdictRepository|Mockery\MockInterface $mockEdictRepo;
    private StructureRepository|Mockery\MockInterface $mockStructureRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockEdictRepo = Mockery::mock(EdictRepository::class);
        $this->mockStructureRepo = Mockery::mock(StructureRepository::class);

        $this->service = new EmbassyService(
            $this->mockEdictRepo,
            $this->mockStructureRepo
        );
    }

    // --- getEmbassyData Tests ---

    public function testGetEmbassyData_ReturnsCorrectStructure(): void
    {
        // Arrange
        $userId = 1;
        $mockStructures = new UserStructure(
            user_id: $userId,
            fortification_level: 0,
            offense_upgrade_level: 0,
            defense_upgrade_level: 0,
            spy_upgrade_level: 0,
            economy_upgrade_level: 0,
            population_level: 0,
            armory_level: 0,
            accounting_firm_level: 0,
            embassy_level: 5 // Should give 1 slot
        );

        $this->mockStructureRepo
            ->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockStructures);

        // Mock active edicts
        $activeEdict = new UserEdict(1, $userId, 'test_edict', '2025-01-01');
        $this->mockEdictRepo
            ->shouldReceive('findActiveByUserId')
            ->once()
            ->with($userId)
            ->andReturn([$activeEdict]);

        // Mock definitions
        $def = new EdictDefinition('test_edict', 'Test', 'Desc', 'Lore', 'economic', [], 0, 'credits');
        $allDefs = ['test_edict' => $def];
        
        $this->mockEdictRepo
            ->shouldReceive('getAllDefinitions')
            ->once()
            ->andReturn($allDefs);

        // Act
        $result = $this->service->getEmbassyData($userId);

        // Assert
        $this->assertEquals(5, $result['embassy_level']);
        $this->assertEquals(2, $result['max_slots']);
        $this->assertEquals(1, $result['slots_used']);
        $this->assertCount(1, $result['active_edicts']);
        $this->assertEquals('test_edict', $result['active_edicts'][0]->key);
        $this->assertEquals(['test_edict'], $result['active_keys']);
        $this->assertEquals($allDefs, $result['available_edicts']);
    }

    public function testGetEmbassyData_WithLevelZero_ReturnsZeroSlots(): void
    {
        // Arrange
        $userId = 1;
        $mockStructures = new UserStructure(
            user_id: $userId,
            fortification_level: 0,
            offense_upgrade_level: 0,
            defense_upgrade_level: 0,
            spy_upgrade_level: 0,
            economy_upgrade_level: 0,
            population_level: 0,
            armory_level: 0,
            accounting_firm_level: 0,
            embassy_level: 0 // No embassy
        );

        $this->mockStructureRepo
            ->shouldReceive('findByUserId')
            ->andReturn($mockStructures);

        $this->mockEdictRepo
            ->shouldReceive('findActiveByUserId')
            ->andReturn([]);

        $this->mockEdictRepo
            ->shouldReceive('getAllDefinitions')
            ->andReturn([]);

        // Act
        $result = $this->service->getEmbassyData($userId);

        // Assert
        $this->assertEquals(0, $result['embassy_level']);
        $this->assertEquals(0, $result['max_slots']);
    }

    // --- activateEdict Tests ---

    public function testActivateEdict_WithInvalidKey_ReturnsError(): void
    {
        // Arrange
        $this->mockEdictRepo
            ->shouldReceive('getDefinition')
            ->with('bad_key')
            ->andReturnNull();

        // Act
        $response = $this->service->activateEdict(1, 'bad_key');

        // Assert
        $this->assertServiceFailure($response);
        $this->assertServiceMessageContains($response, 'Invalid edict');
    }

    public function testActivateEdict_WithoutEmbassy_ReturnsError(): void
    {
        // Arrange
        $userId = 1;
        $key = 'test_edict';
        $def = new EdictDefinition($key, 'Test', 'Desc', 'Lore', 'economic', [], 0, 'credits');

        $this->mockEdictRepo->shouldReceive('getDefinition')->with($key)->andReturn($def);

        $mockStructures = new UserStructure(
            user_id: $userId,
            embassy_level: 0, // No embassy
            fortification_level:0, offense_upgrade_level:0, defense_upgrade_level:0,
            spy_upgrade_level:0, economy_upgrade_level:0, population_level:0, armory_level:0, accounting_firm_level: 0
        );
        $this->mockStructureRepo->shouldReceive('findByUserId')->with($userId)->andReturn($mockStructures);

        // Act
        $response = $this->service->activateEdict($userId, $key);

        // Assert
        $this->assertServiceFailure($response);
        $this->assertServiceMessageContains($response, 'build an Embassy first');
    }

    public function testActivateEdict_WhenSlotsFull_ReturnsError(): void
    {
        // Arrange
        $userId = 1;
        $key = 'new_edict';
        $def = new EdictDefinition($key, 'Test', 'Desc', 'Lore', 'economic', [], 0, 'credits');

        $this->mockEdictRepo->shouldReceive('getDefinition')->with($key)->andReturn($def);

        // Level 1 = 1 Slot
        $mockStructures = new UserStructure(
            user_id: $userId,
            embassy_level: 1,
            fortification_level:0, offense_upgrade_level:0, defense_upgrade_level:0,
            spy_upgrade_level:0, economy_upgrade_level:0, population_level:0, armory_level:0, accounting_firm_level: 0
        );
        $this->mockStructureRepo->shouldReceive('findByUserId')->with($userId)->andReturn($mockStructures);

        // 1 Active Edict (Slots Full)
        $activeEdict = new UserEdict(1, $userId, 'existing_edict', '2025-01-01');
        $this->mockEdictRepo
            ->shouldReceive('findActiveByUserId')
            ->with($userId)
            ->andReturn([$activeEdict]);

        // Act
        $response = $this->service->activateEdict($userId, $key);

        // Assert
        $this->assertServiceFailure($response);
        $this->assertServiceMessageContains($response, 'slots full');
    }

    public function testActivateEdict_WhenAlreadyActive_ReturnsError(): void
    {
        // Arrange
        $userId = 1;
        $key = 'test_edict';
        $def = new EdictDefinition($key, 'Test', 'Desc', 'Lore', 'economic', [], 0, 'credits');

        $this->mockEdictRepo->shouldReceive('getDefinition')->with($key)->andReturn($def);

        // Level 10 = 2 Slots
        $mockStructures = new UserStructure(
            user_id: $userId,
            embassy_level: 10,
            fortification_level:0, offense_upgrade_level:0, defense_upgrade_level:0,
            spy_upgrade_level:0, economy_upgrade_level:0, population_level:0, armory_level:0, accounting_firm_level: 0
        );
        $this->mockStructureRepo->shouldReceive('findByUserId')->with($userId)->andReturn($mockStructures);

        // Edict is already in the active list
        $activeEdict = new UserEdict(1, $userId, $key, '2025-01-01');
        $this->mockEdictRepo
            ->shouldReceive('findActiveByUserId')
            ->with($userId)
            ->andReturn([$activeEdict]);

        // Act
        $response = $this->service->activateEdict($userId, $key);

        // Assert
        $this->assertServiceFailure($response);
        $this->assertServiceMessageContains($response, 'already active');
    }

    public function testActivateEdict_Success(): void
    {
        // Arrange
        $userId = 1;
        $key = 'test_edict';
        $def = new EdictDefinition($key, 'Test Edict', 'Desc', 'Lore', 'economic', [], 0, 'credits');

        $this->mockEdictRepo->shouldReceive('getDefinition')->with($key)->andReturn($def);

        // Level 5 = 1 Slot
        $mockStructures = new UserStructure(
            user_id: $userId,
            embassy_level: 5,
            fortification_level:0, offense_upgrade_level:0, defense_upgrade_level:0,
            spy_upgrade_level:0, economy_upgrade_level:0, population_level:0, armory_level:0, accounting_firm_level: 0
        );
        $this->mockStructureRepo->shouldReceive('findByUserId')->with($userId)->andReturn($mockStructures);

        // No active edicts
        $this->mockEdictRepo
            ->shouldReceive('findActiveByUserId')
            ->with($userId)
            ->andReturn([]);

        // Activation succeeds
        $this->mockEdictRepo
            ->shouldReceive('activate')
            ->once()
            ->with($userId, $key)
            ->andReturn(true);

        // Act
        $response = $this->service->activateEdict($userId, $key);

        // Assert
        $this->assertServiceSuccess($response);
        $this->assertServiceMessageContains($response, 'Test Edict enacted successfully');
    }

    public function testActivateEdict_DatabaseFailure_ReturnsError(): void
    {
        // Arrange
        $userId = 1;
        $key = 'test_edict';
        $def = new EdictDefinition($key, 'Test', 'Desc', 'Lore', 'economic', [], 0, 'credits');

        $this->mockEdictRepo->shouldReceive('getDefinition')->andReturn($def);
        
        $mockStructures = new UserStructure(
            user_id: $userId,
            embassy_level: 5,
            fortification_level:0, offense_upgrade_level:0, defense_upgrade_level:0,
            spy_upgrade_level:0, economy_upgrade_level:0, population_level:0, armory_level:0, accounting_firm_level: 0
        );
        $this->mockStructureRepo->shouldReceive('findByUserId')->andReturn($mockStructures);
        
        $this->mockEdictRepo->shouldReceive('findActiveByUserId')->andReturn([]);

        // Repository returns false
        $this->mockEdictRepo
            ->shouldReceive('activate')
            ->andReturn(false);

        // Act
        $response = $this->service->activateEdict($userId, $key);

        // Assert
        $this->assertServiceFailure($response);
        $this->assertServiceMessageContains($response, 'Failed to enact edict');
    }

    // --- revokeEdict Tests ---

    public function testRevokeEdict_Success(): void
    {
        // Arrange
        $userId = 1;
        $key = 'test_edict';

        $this->mockEdictRepo
            ->shouldReceive('deactivate')
            ->once()
            ->with($userId, $key)
            ->andReturn(true);

        // Act
        $response = $this->service->revokeEdict($userId, $key);

        // Assert
        $this->assertServiceSuccess($response);
        $this->assertServiceMessageContains($response, 'Edict revoked');
    }

    public function testRevokeEdict_NotActive_ReturnsError(): void
    {
        // Arrange
        $userId = 1;
        $key = 'test_edict';

        $this->mockEdictRepo
            ->shouldReceive('deactivate')
            ->once()
            ->with($userId, $key)
            ->andReturn(false);

        // Act
        $response = $this->service->revokeEdict($userId, $key);

        // Assert
        $this->assertServiceFailure($response);
        $this->assertServiceMessageContains($response, 'Edict was not active');
    }
}