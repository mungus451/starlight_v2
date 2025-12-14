<?php

namespace Tests\Unit;

use App\Core\Config;
use App\Models\Entities\UserStructure;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Services\StructureService;
use Mockery;
use PDO;
use PHPUnit\Framework\TestCase;

class StructureServiceTest extends TestCase
{
    private $db;
    private $config;
    private $resourceRepo;
    private $structureRepo;
    private $service;

    protected function setUp(): void
    {
        $this->db = Mockery::mock(PDO::class);
        $this->config = Mockery::mock(Config::class);
        $this->resourceRepo = Mockery::mock(ResourceRepository::class);
        $this->structureRepo = Mockery::mock(StructureRepository::class);

        $this->service = new StructureService(
            $this->db,
            $this->config,
            $this->resourceRepo,
            $this->structureRepo
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testGetStructureData()
    {
        $userId = 1;
        $mockResources = new \App\Models\Entities\UserResource(
            $userId, 1000, 0, 0, 0, 0, 0, 0, 0, 0, 0
        );
        $mockStructures = new UserStructure(
            1, 0, 0, 0, 0, 0, 0, 0, 0 // Defaults
        );

        $this->resourceRepo->shouldReceive('findByUserId')->with($userId)->andReturn($mockResources);
        $this->structureRepo->shouldReceive('findByUserId')->with($userId)->andReturn($mockStructures);

        $this->config->shouldReceive('get')->with('game_balance.structures', [])->andReturn([
            'fortification' => [
                'base_cost' => 100,
                'multiplier' => 1.5
            ]
        ]);
        $this->config->shouldReceive('get')->with('game_balance.turn_processor', [])->andReturn([]);
        $this->config->shouldReceive('get')->with('game_balance.attack', [])->andReturn([]);
        $this->config->shouldReceive('get')->with('game_balance.spy', [])->andReturn([]);

        $result = $this->service->getStructureData($userId);

        $this->assertEquals($mockResources, $result['resources']);
        $this->assertEquals($mockStructures, $result['structures']);
        $this->assertEquals(100, $result['costs']['fortification']['credits']);
    }

    public function testUpgradeStructureSuccess()
    {
        $userId = 1;
        $structureKey = 'fortification';
        $currentLevel = 5;
        $baseCost = 100;
        $multiplier = 1.5;
        // Cost = 100 * (1.5 ^ 5) = 100 * 7.59375 = 759
        $expectedCost = 759;

        // Use real instance instead of Mock
        $mockStructures = new UserStructure(
            $userId, 
            $currentLevel, // fortification_level
            0, 0, 0, 0, 0, 0, 0
        );

        $mockResources = new \App\Models\Entities\UserResource(
            $userId, 1000, 0, 0, 1000, 0, 0, 0, 0, 0, 0, 0, 0, 1000
        );

        $this->config->shouldReceive('get')->with('game_balance.structures.' . $structureKey)->andReturn([
            'name' => 'Fortification',
            'base_cost' => $baseCost,
            'multiplier' => $multiplier
        ]);

        $this->resourceRepo->shouldReceive('findByUserId')->with($userId)->andReturn($mockResources);
        $this->structureRepo->shouldReceive('findByUserId')->with($userId)->andReturn($mockStructures);

        $this->db->shouldReceive('inTransaction')->andReturn(false);
        $this->db->shouldReceive('beginTransaction');
        $this->db->shouldReceive('commit');

        $this->resourceRepo->shouldReceive('updateResources')
            ->with($userId, -$expectedCost, 0, 0)
            ->once();

        $this->structureRepo->shouldReceive('updateStructureLevel')
            ->with($userId, 'fortification_level', $currentLevel + 1)
            ->once();

        $response = $this->service->upgradeStructure($userId, $structureKey);

        $this->assertTrue($response->success);
        $this->assertEquals(6, $response->data['new_level']);
    }

    public function testUpgradeStructureInsufficientFunds()
    {
        $userId = 1;
        $structureKey = 'fortification';
        
        $mockStructures = new UserStructure(
            $userId, 0, 0, 0, 0, 0, 0, 0, 0
        );

        $mockResources = new \App\Models\Entities\UserResource(
            $userId, 50, 0, 0, 0, 0, 0, 0, 0, 0, 0
        );

        $this->config->shouldReceive('get')->with('game_balance.structures.' . $structureKey)->andReturn([
            'name' => 'Fortification',
            'base_cost' => 100,
            'multiplier' => 1.5
        ]);

        $this->resourceRepo->shouldReceive('findByUserId')->with($userId)->andReturn($mockResources);
        $this->structureRepo->shouldReceive('findByUserId')->with($userId)->andReturn($mockStructures);

        $response = $this->service->upgradeStructure($userId, $structureKey);

        $this->assertFalse($response->success);
        $this->assertEquals('Insufficient credits for upgrade.', $response->message);
    }
}
