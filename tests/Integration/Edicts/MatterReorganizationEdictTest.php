<?php

namespace Tests\Integration\Edicts;

use Tests\Unit\TestCase;
use App\Models\Services\StructureService;
use App\Models\Services\PowerCalculatorService;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\EdictRepository;
use App\Models\Repositories\GeneralRepository;
use App\Models\Repositories\AllianceStructureRepository;
use App\Models\Repositories\AllianceStructureDefinitionRepository;
use App\Models\Services\ArmoryService;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStructure;
use App\Models\Entities\UserStats;
use App\Models\Entities\UserEdict;
use App\Models\Entities\EdictDefinition;
use App\Core\Config;
use Mockery;

class MatterReorganizationEdictTest extends TestCase
{
    /**
     * Test that the Matter Reorganization edict correctly reduces structure costs.
     */
    public function testMatterReorganizationLowersStructureCost(): void
    {
        // 1. Arrange
        $mockDb = $this->createMockPDO();
        $mockConfig = Mockery::mock(Config::class);
        $mockResourceRepo = Mockery::mock(ResourceRepository::class);
        $mockStructureRepo = Mockery::mock(StructureRepository::class);
        $mockEdictRepo = Mockery::mock(EdictRepository::class);
        $mockEmbassyService = Mockery::mock(EmbassyService::class);

        $service = new StructureService(
            $mockDb,
            $mockConfig,
            $mockResourceRepo,
            $mockStructureRepo,
            $mockEdictRepo
        );

        $userId = 1;
        $structureName = 'armory';
        $baseCost = 100000;
        $costReduction = 0.25; // 25%
        $expectedCost = $baseCost * (1 - $costReduction); // 75,000

        // Mock Config
        $mockConfig->shouldReceive('get')
            ->with("game_balance.structures.{$structureName}")
            ->andReturn([
                'name' => 'Armory',
                'base_cost' => $baseCost,
                'multiplier' => 2.0
            ]);

        // Mock Active Edict
        $activeEdict = new UserEdict(1, $userId, 'matter_reorganization', '2024-01-01');
        $edictDefinition = new EdictDefinition(
            'matter_reorganization', '', '', '', '',
            ['structure_cost_modifier' => $costReduction], 0, ''
        );
        $mockEdictRepo->shouldReceive('findActiveByUserId')->with($userId)->andReturn([$activeEdict]);
        $mockEdictRepo->shouldReceive('getDefinition')->with('matter_reorganization')->andReturn($edictDefinition);

        // Mock Resources & Structures
        $mockResourceRepo->shouldReceive('findByUserId')->andReturn(new UserResource($userId, 200000, 0,0,0,0,0,0,0,0,0));
        $mockStructureRepo->shouldReceive('findByUserId')->andReturn(new UserStructure($userId, 0,0,0,0,0,0,0,0,0));

        // Mock Transaction
        $mockDb->shouldReceive('inTransaction')->andReturn(false);
        $mockDb->shouldReceive('beginTransaction')->once();
        $mockDb->shouldReceive('commit')->once();

        // 2. Expect
        $mockResourceRepo->shouldReceive('updateResources')
            ->once()
            ->with($userId, -$expectedCost, 0, 0); // Asserting the reduced cost is deducted

        $mockStructureRepo->shouldReceive('updateStructureLevel')->once();

        // 3. Act
        $response = $service->upgradeStructure($userId, $structureName);

        // 4. Assert
        $this->assertServiceSuccess($response);
    }

    /**
     * Test that the Matter Reorganization edict correctly halves citizen generation.
     */
    public function testMatterReorganizationHalvesCitizenGeneration(): void
    {
        // 1. Arrange
        $config = new Config(__DIR__ . '/../../../config'); // Use real config
        $mockEdictRepo = Mockery::mock(EdictRepository::class);
        $mockGeneralRepo = Mockery::mock(GeneralRepository::class);

        $service = new PowerCalculatorService(
            $config,
            $this->createMock(ArmoryService::class),
            $this->createMock(AllianceStructureRepository::class),
            $this->createMock(AllianceStructureDefinitionRepository::class),
            $mockEdictRepo,
            $mockGeneralRepo
        );

        $userId = 1;
        $citizenGrowthConfig = $config->get('game_balance.turn_processor');
        $baseCitizenGrowth = $citizenGrowthConfig['citizen_growth_per_pop_level'] * 10; // 10 pop levels
        $expectedCitizenGrowth = $baseCitizenGrowth / 2;

        // Mock Active Edict
        $activeEdict = new UserEdict(1, $userId, 'matter_reorganization', '2024-01-01');
        $edictDefinition = new EdictDefinition(
            'matter_reorganization', '', '', '', '',
            ['citizen_generation_modifier' => 0.5], 0, '' // 50% reduction
        );
        $mockEdictRepo->shouldReceive('findActiveByUserId')->with($userId)->andReturn([$activeEdict]);
        $mockEdictRepo->shouldReceive('getDefinition')->with('matter_reorganization')->andReturn($edictDefinition);
        $mockGeneralRepo->shouldReceive('findByUserId')->andReturn([]);
        $mockGeneralRepo->shouldReceive('countByUserId')->andReturn(0);

        // Mock User Data
        $resources = new UserResource($userId, 0,0,0,0,0,0,0,0,0,0);
        $stats = new UserStats($userId, 1,0,0,0,0,0,0,0,0,0,0,0,0,null);
        $structures = new UserStructure($userId, 0,0,0,0,0,10,0,0,0); // Population Level 10

        // 2. Act
        $result = $service->calculateIncomePerTurn($userId, $resources, $stats, $structures, null);

        // 3. Assert
        $this->assertEquals($expectedCitizenGrowth, $result['total_citizens']);
    }
}
