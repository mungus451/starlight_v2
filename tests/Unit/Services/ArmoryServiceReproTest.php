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
use Mockery;
use PDO;

class ArmoryServiceReproTest extends TestCase
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

        $this->mockDb = Mockery::mock(PDO::class);
        $this->mockConfig = Mockery::mock(Config::class);
        $this->mockArmoryRepo = Mockery::mock(ArmoryRepository::class);
        $this->mockResourceRepo = Mockery::mock(ResourceRepository::class);
        $this->mockStructureRepo = Mockery::mock(StructureRepository::class);
        $this->mockStatsRepo = Mockery::mock(StatsRepository::class);

        $this->mockConfig->shouldReceive('get')
            ->with('armory_items', [])
            ->andReturn($this->getMockArmoryConfig())
            ->byDefault();

        $this->service = new ArmoryService(
            $this->mockDb,
            $this->mockConfig,
            $this->mockArmoryRepo,
            $this->mockResourceRepo,
            $this->mockStructureRepo,
            $this->mockStatsRepo
        );
    }

    public function testEquipItemWithValidKey(): void
    {
        $userId = 1;
        $unitKey = 'soldiers';
        $categoryKey = 'weapon';
        $itemKey = 'soldier_weapon_tier1';

        $this->mockArmoryRepo->shouldReceive('setLoadout')
            ->once()
            ->with($userId, $unitKey, $categoryKey, $itemKey);

        $response = $this->service->equipItem($userId, $unitKey, $categoryKey, $itemKey);

        $this->assertTrue($response->isSuccess());
        $this->assertStringContainsString('is now the standard issue', $response->message);
    }

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
                                'cost_credits' => 1000,
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
