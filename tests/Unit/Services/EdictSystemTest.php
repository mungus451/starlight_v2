<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\EmbassyService;
use App\Models\Repositories\EdictRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Entities\EdictDefinition;
use App\Models\Entities\UserEdict;
use App\Models\Entities\UserStructure;
use App\Core\ServiceResponse;
use Mockery;

class EdictSystemTest extends TestCase
{
    private EmbassyService $service;
    private EdictRepository|Mockery\MockInterface $mockEdictRepo;
    private StructureRepository|Mockery\MockInterface $mockStructureRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockEdictRepo = Mockery::mock(EdictRepository::class);
        $this->mockStructureRepo = Mockery::mock(StructureRepository::class);
        $this->service = new EmbassyService($this->mockEdictRepo, $this->mockStructureRepo);
    }

    // --- getEmbassyData Tests ---

    public function testGetEmbassyDataReturnsCorrectStructure(): void
    {
        $userId = 1;
        
        // Mock Structures
        $structs = new UserStructure($userId, 0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,5); // Level 5 = 2 Slots
        $this->mockStructureRepo->shouldReceive('findByUserId')->with($userId)->andReturn($structs);

        // Mock Definitions
        $def1 = new EdictDefinition('key1', 'Name1', 'Desc1', 'Lore', 'eco', [], 0, 'credits');
        $def2 = new EdictDefinition('key2', 'Name2', 'Desc2', 'Lore', 'mil', [], 0, 'credits');
        $allDefs = ['key1' => $def1, 'key2' => $def2];
        $this->mockEdictRepo->shouldReceive('getAllDefinitions')->andReturn($allDefs);

        // Mock Active Edicts
        $activeEdict = new UserEdict(1, $userId, 'key1', '2024-01-01');
        $this->mockEdictRepo->shouldReceive('findActiveByUserId')->with($userId)->andReturn([$activeEdict]);

        $result = $this->service->getEmbassyData($userId);

        $this->assertEquals(5, $result['embassy_level']);
        $this->assertEquals(2, $result['max_slots']);
        $this->assertEquals(1, $result['slots_used']);
        $this->assertCount(1, $result['active_edicts']);
        $this->assertEquals('Name1', $result['active_edicts'][0]->name);
        $this->assertEquals(['key1'], $result['active_keys']);
        $this->assertEquals($allDefs, $result['available_edicts']);
    }

    // --- activateEdict Tests ---

    public function testActivateEdictSuccess(): void
    {
        $userId = 1;
        $edictKey = 'ferengi_principle';

        // 1. Mock Definition
        $def = new EdictDefinition($edictKey, 'Test', 'Desc', 'Lore', 'eco', [], 0, 'credits');
        $this->mockEdictRepo->shouldReceive('getDefinition')->with($edictKey)->andReturn($def);

        // 2. Mock Structure Level (Level 5 = 2 Slots)
        $structs = new UserStructure($userId, 0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,5); 
        $this->mockStructureRepo->shouldReceive('findByUserId')->with($userId)->andReturn($structs);

        // 3. Mock Active Edicts (0 active)
        $this->mockEdictRepo->shouldReceive('findActiveByUserId')->with($userId)->andReturn([]);

        // 4. Expect Activation
        $this->mockEdictRepo->shouldReceive('activate')->with($userId, $edictKey)->andReturn(true);

        $response = $this->service->activateEdict($userId, $edictKey);

        $this->assertTrue($response->success);
        $this->assertStringContainsString('enacted successfully', $response->message);
    }

    public function testActivateEdictFailsInvalidKey(): void
    {
        $userId = 1;
        $edictKey = 'bad_key';

        $this->mockEdictRepo->shouldReceive('getDefinition')->with($edictKey)->andReturn(null);

        $response = $this->service->activateEdict($userId, $edictKey);

        $this->assertFalse($response->success);
        $this->assertEquals('Invalid edict.', $response->message);
    }

    public function testActivateEdictFailsNoEmbassy(): void
    {
        $userId = 1;
        $edictKey = 'key1';

        $def = new EdictDefinition($edictKey, 'Test', 'Desc', 'Lore', 'eco', [], 0, 'credits');
        $this->mockEdictRepo->shouldReceive('getDefinition')->andReturn($def);

        // Level 0
        $structs = new UserStructure($userId, 0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0); 
        $this->mockStructureRepo->shouldReceive('findByUserId')->andReturn($structs);

        $response = $this->service->activateEdict($userId, $edictKey);

        $this->assertFalse($response->success);
        $this->assertStringContainsString('build an Embassy', $response->message);
    }

    public function testActivateEdictFailsWhenSlotsFull(): void
    {
        $userId = 1;
        $edictKey = 'ferengi_principle';

        $def = new EdictDefinition($edictKey, 'Test', 'Desc', 'Lore', 'eco', [], 0, 'credits');
        $this->mockEdictRepo->shouldReceive('getDefinition')->andReturn($def);

        // Level 1 = 1 Slot
        $structs = new UserStructure($userId, 0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1); 
        $this->mockStructureRepo->shouldReceive('findByUserId')->andReturn($structs);

        // 1 Active Edict (Slot taken)
        $active = new UserEdict(1, $userId, 'other_edict', '2024-01-01');
        $this->mockEdictRepo->shouldReceive('findActiveByUserId')->andReturn([$active]);

        $response = $this->service->activateEdict($userId, $edictKey);

        $this->assertFalse($response->success);
        $this->assertStringContainsString('slots full', $response->message);
    }

    public function testActivateEdictFailsWhenAlreadyActive(): void
    {
        $userId = 1;
        $edictKey = 'ferengi_principle';

        $def = new EdictDefinition($edictKey, 'Test', 'Desc', 'Lore', 'eco', [], 0, 'credits');
        $this->mockEdictRepo->shouldReceive('getDefinition')->andReturn($def);

        // Level 5 = 2 Slots
        $structs = new UserStructure($userId, 0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,5); 
        $this->mockStructureRepo->shouldReceive('findByUserId')->andReturn($structs);

        // Already Active
        $active = new UserEdict(1, $userId, $edictKey, '2024-01-01');
        $this->mockEdictRepo->shouldReceive('findActiveByUserId')->andReturn([$active]);

        $response = $this->service->activateEdict($userId, $edictKey);

        $this->assertFalse($response->success);
        $this->assertStringContainsString('already active', $response->message);
    }

    public function testActivateEdictFailsDbError(): void
    {
        $userId = 1;
        $edictKey = 'key1';

        $def = new EdictDefinition($edictKey, 'Test', 'Desc', 'Lore', 'eco', [], 0, 'credits');
        $this->mockEdictRepo->shouldReceive('getDefinition')->andReturn($def);

        $structs = new UserStructure($userId, 0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,5); 
        $this->mockStructureRepo->shouldReceive('findByUserId')->andReturn($structs);
        $this->mockEdictRepo->shouldReceive('findActiveByUserId')->andReturn([]);

        // DB Failure
        $this->mockEdictRepo->shouldReceive('activate')->with($userId, $edictKey)->andReturn(false);

        $response = $this->service->activateEdict($userId, $edictKey);

        $this->assertFalse($response->success);
        $this->assertEquals('Failed to enact edict.', $response->message);
    }

    // --- revokeEdict Tests ---

    public function testRevokeEdictSuccess(): void
    {
        $userId = 1;
        $edictKey = 'ferengi_principle';

        $this->mockEdictRepo->shouldReceive('deactivate')->with($userId, $edictKey)->andReturn(true);

        $response = $this->service->revokeEdict($userId, $edictKey);

        $this->assertTrue($response->success);
        $this->assertEquals('Edict revoked.', $response->message);
    }

    public function testRevokeEdictFailsIfNotActive(): void
    {
        $userId = 1;
        $edictKey = 'ferengi_principle';

        $this->mockEdictRepo->shouldReceive('deactivate')->with($userId, $edictKey)->andReturn(false);

        $response = $this->service->revokeEdict($userId, $edictKey);

        $this->assertFalse($response->success);
        $this->assertEquals('Edict was not active.', $response->message);
    }

    // --- calculateMaxSlots Boundary Tests ---

    /**
     * @dataProvider slotBoundaryProvider
     */
    public function testSlotBoundaries(int $level, int $expectedSlots): void
    {
        $userId = 1;
        $edictKey = 'key';

        $def = new EdictDefinition($edictKey, 'Test', 'Desc', 'Lore', 'eco', [], 0, 'credits');
        $this->mockEdictRepo->shouldReceive('getDefinition')->andReturn($def);

        // We use activateEdict to implicitly test calculateMaxSlots logic via the "Slots Full" check
        $structs = new UserStructure($userId, 0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,$level); 
        $this->mockStructureRepo->shouldReceive('findByUserId')->andReturn($structs);

        // Fill slots exactly to the expected limit
        $activeEdicts = [];
        for ($i = 0; $i < $expectedSlots; $i++) {
            $activeEdicts[] = new UserEdict($i, $userId, "active_$i", 'date');
        }
        $this->mockEdictRepo->shouldReceive('findActiveByUserId')->andReturn($activeEdicts);

        $response = $this->service->activateEdict($userId, $edictKey);

        if ($level === 0) {
             $this->assertFalse($response->success);
             $this->assertStringContainsString('build an Embassy', $response->message);
        } else {
            // Should fail because slots are full
            $this->assertFalse($response->success);
            $this->assertStringContainsString("slots full ($expectedSlots/$expectedSlots)", $response->message);
        }
    }

    public static function slotBoundaryProvider(): array
    {
        return [
            [0, 0],  // No Embassy
            [1, 1],  // Level 1 = 1
            [4, 1],  // Level 4 = 1
            [5, 2],  // Level 5 = 2
            [9, 2],  // Level 9 = 2
            [10, 3], // Level 10 = 3
            [14, 3], // Level 14 = 3
            [15, 4], // Level 15 = 4
            [20, 4]  // Level 20 = 4 (Cap)
        ];
    }
}