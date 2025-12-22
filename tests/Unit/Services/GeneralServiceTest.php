<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\GeneralService;
use App\Core\Config;
use App\Models\Repositories\GeneralRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Entities\UserResource;
use Mockery;
use PDO;

class GeneralServiceTest extends TestCase
{
    private GeneralService $service;
    private Config|Mockery\MockInterface $mockConfig;
    private GeneralRepository|Mockery\MockInterface $mockGeneralRepo;
    private ResourceRepository|Mockery\MockInterface $mockResourceRepo;
    private PDO|Mockery\MockInterface $mockDb;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConfig = Mockery::mock(Config::class);
        $this->mockGeneralRepo = Mockery::mock(GeneralRepository::class);
        $this->mockResourceRepo = Mockery::mock(ResourceRepository::class);
        $this->mockDb = Mockery::mock(PDO::class);

        // Default Config
        $this->mockConfig->shouldReceive('get')->with('game_balance.generals', [])
            ->andReturn(['base_capacity' => 500, 'capacity_per_general' => 10000]);
        $this->mockConfig->shouldReceive('get')->with('elite_weapons', [])
            ->andReturn([
                'warlord_blade' => [
                    'name' => 'Blade', 
                    'cost' => ['credits' => 1000, 'naquadah_crystals' => 10, 'dark_matter' => 1]
                ]
            ]);

        $this->service = new GeneralService(
            $this->mockConfig,
            $this->mockGeneralRepo,
            $this->mockResourceRepo,
            $this->mockDb
        );
    }

    private function createMockResource(int $userId, int $credits = 0, int $naquadah = 0, int $darkMatter = 0, float $protoform = 0.0): UserResource
    {
        return new UserResource(
            user_id: $userId,
            credits: $credits,
            banked_credits: 0,
            gemstones: 0,
            naquadah_crystals: (float)$naquadah,
            untrained_citizens: 0,
            workers: 0,
            soldiers: 0,
            guards: 0,
            spies: 0,
            sentries: 0,
            untraceable_chips: 0,
            research_data: 0,
            dark_matter: $darkMatter,
            protoform: $protoform
        );
    }

    public function testGetArmyCapacityCalculatesCorrectly(): void
    {
        $userId = 1;
        $this->mockGeneralRepo->shouldReceive('countByUserId')->with($userId)->andReturn(2);
        
        $capacity = $this->service->getArmyCapacity($userId);
        // 500 + (2 * 10000) = 20500
        $this->assertEquals(20500, $capacity);
    }

    public function testRecruitGeneralFailsIfInsufficientCredits(): void
    {
        $userId = 1;
        $this->mockGeneralRepo->shouldReceive('countByUserId')->with($userId)->andReturn(0);
        
        // Cost for 1st general: 1M Credits, 100 Protoform
        $resource = $this->createMockResource($userId, credits: 500, protoform: 1000.0);
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($userId)->andReturn($resource);

        $res = $this->service->recruitGeneral($userId, 'Gen 1');
        $this->assertFalse($res->isSuccess());
        $this->assertStringContainsString('Insufficient Credits', $res->message);
    }

    public function testRecruitGeneralSuccess(): void
    {
        $userId = 1;
        $this->mockGeneralRepo->shouldReceive('countByUserId')->with($userId)->andReturn(0);
        
        $resource = $this->createMockResource($userId, credits: 2000000, protoform: 200.0);
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($userId)->andReturn($resource);

        $this->mockDb->shouldReceive('beginTransaction');
        $this->mockDb->shouldReceive('commit');
        
        $this->mockResourceRepo->shouldReceive('updateResources')
            ->once()
            ->with($userId, -1000000, null, null, -100.0);
            
        $this->mockGeneralRepo->shouldReceive('create')->with($userId, 'Gen 1')->once();

        $res = $this->service->recruitGeneral($userId, 'Gen 1');
        $this->assertTrue($res->isSuccess());
    }

    public function testEquipWeaponFailsIfGeneralNotOwned(): void
    {
        $userId = 1;
        $generalId = 99;
        $this->mockGeneralRepo->shouldReceive('findById')->with($generalId)->andReturn(['user_id' => 2]); // Not owner

        $res = $this->service->equipWeapon($userId, $generalId, 'warlord_blade');
        $this->assertFalse($res->isSuccess());
        $this->assertEquals('General not found.', $res->message);
    }

    public function testEquipWeaponSuccess(): void
    {
        $userId = 1;
        $generalId = 10;
        $this->mockGeneralRepo->shouldReceive('findById')->with($generalId)->andReturn(['id' => 10, 'user_id' => $userId]);
        
        $resource = $this->createMockResource($userId, credits: 10000, naquadah: 100, darkMatter: 50);
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($userId)->andReturn($resource);

        $this->mockDb->shouldReceive('beginTransaction');
        $this->mockDb->shouldReceive('commit');

        // Cost: 1000 Cr, 10 Naq, 1 DM
        $this->mockResourceRepo->shouldReceive('updateResources')
            ->once()
            ->with($userId, -1000, -10, -1, 0);
            
        $this->mockGeneralRepo->shouldReceive('updateWeaponSlot')->with($generalId, 'warlord_blade')->once();

        $res = $this->service->equipWeapon($userId, $generalId, 'warlord_blade');
        $this->assertTrue($res->isSuccess());
    }

    public function testDecommissionGeneralSuccess(): void
    {
        $userId = 1;
        $generalId = 5;
        $this->mockGeneralRepo->shouldReceive('findById')->with($generalId)->andReturn(['id' => 5, 'user_id' => $userId, 'name' => 'Bob']);
        
        $this->mockDb->shouldReceive('beginTransaction');
        $this->mockDb->shouldReceive('commit');
        
        $this->mockGeneralRepo->shouldReceive('delete')->with($generalId)->once();

        $res = $this->service->decommissionGeneral($userId, $generalId);
        $this->assertTrue($res->isSuccess());
    }
}