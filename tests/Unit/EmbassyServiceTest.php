<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Services\EmbassyService;
use App\Models\Repositories\EdictRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Entities\UserStructure;
use App\Models\Entities\EdictDefinition;
use App\Models\Entities\UserEdict;
use App\Core\ServiceResponse;

class EmbassyServiceTest extends TestCase
{
    private $edictRepo;
    private $structureRepo;
    private $embassyService;

    protected function setUp(): void
    {
        $this->edictRepo = $this->createMock(EdictRepository::class);
        $this->structureRepo = $this->createMock(StructureRepository::class);
        $this->embassyService = new EmbassyService($this->edictRepo, $this->structureRepo);
    }

    private function createMockUserStructure(int $embassyLevel): UserStructure
    {
        return new UserStructure(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, $embassyLevel);
    }

    private function createMockEdictDefinition(string $key, string $name = 'Test Edict'): EdictDefinition
    {
        return new EdictDefinition($key, $name, '', '', 'test', [], 0, 'credits');
    }

    public function testGetEmbassyData()
    {
        $userId = 1;
        $structures = $this->createMockUserStructure(5);
        $activeEdicts = [new UserEdict(1, $userId, 'ferengi_principle', '2023-01-01')];
        $def = $this->createMockEdictDefinition('ferengi_principle');
        $allDefinitions = ['ferengi_principle' => $def];

        $this->structureRepo->method('findByUserId')->with($userId)->willReturn($structures);
        $this->edictRepo->method('findActiveByUserId')->with($userId)->willReturn($activeEdicts);
        $this->edictRepo->method('getAllDefinitions')->willReturn($allDefinitions);

        $data = $this->embassyService->getEmbassyData($userId);

        $this->assertEquals(5, $data['embassy_level']);
        $this->assertEquals(2, $data['max_slots']);
        $this->assertEquals(1, $data['slots_used']);
        $this->assertCount(1, $data['active_edicts']);
        $this->assertEquals('Test Edict', $data['active_edicts'][0]->name);
    }

    public function testActivateEdictSuccess()
    {
        $userId = 1;
        $edictKey = 'ferengi_principle';
        $structures = $this->createMockUserStructure(1);
        $definition = $this->createMockEdictDefinition($edictKey, 'The Ferengi Principle');

        $this->edictRepo->method('getDefinition')->with($edictKey)->willReturn($definition);
        $this->structureRepo->method('findByUserId')->with($userId)->willReturn($structures);
        $this->edictRepo->method('findActiveByUserId')->with($userId)->willReturn([]);
        $this->edictRepo->method('activate')->with($userId, $edictKey)->willReturn(true);

        $response = $this->embassyService->activateEdict($userId, $edictKey);

        $this->assertTrue($response->success);
        $this->assertEquals('The Ferengi Principle enacted successfully.', $response->message);
    }

    public function testActivateEdictInvalidKey()
    {
        $this->edictRepo->method('getDefinition')->willReturn(null);
        $response = $this->embassyService->activateEdict(1, 'invalid_key');
        $this->assertFalse($response->success);
        $this->assertEquals('Invalid edict.', $response->message);
    }

    public function testActivateEdictNoEmbassy()
    {
        $this->edictRepo->method('getDefinition')->willReturn($this->createMockEdictDefinition('test'));
        $this->structureRepo->method('findByUserId')->willReturn($this->createMockUserStructure(0));
        $response = $this->embassyService->activateEdict(1, 'test_edict');
        $this->assertFalse($response->success);
        $this->assertEquals('You must build an Embassy first.', $response->message);
    }

    public function testActivateEdictSlotsFull()
    {
        $this->edictRepo->method('getDefinition')->willReturn($this->createMockEdictDefinition('test'));
        $this->structureRepo->method('findByUserId')->willReturn($this->createMockUserStructure(1)); // 1 slot
        $activeEdicts = [new UserEdict(1, 1, 'test1', '2023-01-01')];
        $this->edictRepo->method('findActiveByUserId')->willReturn($activeEdicts); // 1 active
        $response = $this->embassyService->activateEdict(1, 'test_edict');
        $this->assertFalse($response->success);
        $this->assertEquals('Edict slots full (1/1). Revoke an existing edict first.', $response->message);
    }

    public function testActivateEdictAlreadyActive()
    {
        $this->edictRepo->method('getDefinition')->willReturn($this->createMockEdictDefinition('test'));
        $this->structureRepo->method('findByUserId')->willReturn($this->createMockUserStructure(5)); // 2 slots
        $activeEdicts = [new UserEdict(1, 1, 'test_edict', '2023-01-01')];
        $this->edictRepo->method('findActiveByUserId')->willReturn($activeEdicts);
        $response = $this->embassyService->activateEdict(1, 'test_edict');
        $this->assertFalse($response->success);
        $this->assertEquals('Edict is already active.', $response->message);
    }

    public function testRevokeEdictSuccess()
    {
        $this->edictRepo->method('deactivate')->with(1, 'test_edict')->willReturn(true);
        $response = $this->embassyService->revokeEdict(1, 'test_edict');
        $this->assertTrue($response->success);
        $this->assertEquals('Edict revoked.', $response->message);
    }

    public function testRevokeEdictNotActive()
    {
        $this->edictRepo->method('deactivate')->with(1, 'test_edict')->willReturn(false);
        $response = $this->embassyService->revokeEdict(1, 'test_edict');
        $this->assertFalse($response->success);
        $this->assertEquals('Edict was not active.', $response->message);
    }
}
