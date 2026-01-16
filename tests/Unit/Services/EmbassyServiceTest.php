<?php

namespace Tests\Unit\Services;

use App\Models\Services\EmbassyService;
use App\Models\Repositories\EdictRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Entities\EdictDefinition;
use App\Models\Entities\UserStructure;
use App\Models\Entities\UserEdict;
use Mockery;
use PHPUnit\Framework\TestCase;

class EmbassyServiceTest extends TestCase
{
    private $edictRepo;
    private $structureRepo;
    private $service;

    protected function setUp(): void
    {
        $this->edictRepo = Mockery::mock(EdictRepository::class);
        $this->structureRepo = Mockery::mock(StructureRepository::class);
        $this->service = new EmbassyService($this->edictRepo, $this->structureRepo);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testGetEmbassyDataReturnsCorrectStructure()
    {
        $userId = 1;
        
        // Use real instance instead of mock for readonly class
        $structure = new UserStructure(
            user_id: $userId,
            fortification_level: 0,
            offense_upgrade_level: 0,
            defense_upgrade_level: 0,
            spy_upgrade_level: 0,
            economy_upgrade_level: 0,
            population_level: 0,
            armory_level: 0,
            accounting_firm_level: 0,
            embassy_level: 5 // Key value for this test
        );

        $this->structureRepo->shouldReceive('findByUserId')
            ->with($userId)
            ->andReturn($structure);

        $this->edictRepo->shouldReceive('findActiveByUserId')
            ->with($userId)
            ->andReturn([]); // No active edicts

        $this->edictRepo->shouldReceive('getAllDefinitions')
            ->andReturn([]);

        $data = $this->service->getEmbassyData($userId);

        $this->assertEquals(5, $data['embassy_level']);
        // Level 5 Logic: <5=1, <10=2. So Level 5 is not <5. It is <10. So 2 slots.
        $this->assertEquals(2, $data['max_slots']); 
        $this->assertEquals(0, $data['slots_used']);
    }

    public function testActivateEdictFailsWithoutEmbassy()
    {
        $userId = 1;
        $edictKey = 'test_edict';

        $definition = new EdictDefinition(
            $edictKey, 'Test', 'Desc', 'Lore', 'economic', [], 0, 'credits'
        );
        $this->edictRepo->shouldReceive('getDefinition')->with($edictKey)->andReturn($definition);

        $structure = new UserStructure(
            user_id: $userId,
            fortification_level: 0,
            offense_upgrade_level: 0,
            defense_upgrade_level: 0,
            spy_upgrade_level: 0,
            economy_upgrade_level: 0,
            population_level: 0,
            armory_level: 0,
            accounting_firm_level: 0,
            embassy_level: 0 // Key value for this test (No embassy)
        );

        $this->structureRepo->shouldReceive('findByUserId')
            ->with($userId)
            ->andReturn($structure);

        $response = $this->service->activateEdict($userId, $edictKey);

        $this->assertFalse($response->success);
        $this->assertStringContainsString('must build an Embassy', $response->message);
    }

    public function testActivateEdictSuccess()
    {
        $userId = 1;
        $edictKey = 'test_edict';

        $definition = new EdictDefinition(
            $edictKey, 'Test', 'Desc', 'Lore', 'economic', [], 0, 'credits'
        );
        $this->edictRepo->shouldReceive('getDefinition')->with($edictKey)->andReturn($definition);

        $structure = new UserStructure(
            user_id: $userId,
            fortification_level: 0,
            offense_upgrade_level: 0,
            defense_upgrade_level: 0,
            spy_upgrade_level: 0,
            economy_upgrade_level: 0,
            population_level: 0,
            armory_level: 0,
            accounting_firm_level: 0,
            embassy_level: 10 // Key value for this test
        );

        $this->structureRepo->shouldReceive('findByUserId')->with($userId)->andReturn($structure);
        $this->edictRepo->shouldReceive('findActiveByUserId')->with($userId)->andReturn([]);
        $this->edictRepo->shouldReceive('activate')->with($userId, $edictKey)->andReturn(true);

        $response = $this->service->activateEdict($userId, $edictKey);

        $this->assertTrue($response->success);
    }
}