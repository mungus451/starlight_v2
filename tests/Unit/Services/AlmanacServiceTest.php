<?php

namespace Tests\Unit\Services;

use App\Models\Entities\Alliance;
use App\Models\Entities\User;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AlmanacRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Services\AlmanacService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class AlmanacServiceTest extends MockeryTestCase
{
    private $almanacRepo;
    private $userRepo;
    private $allianceRepo;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->almanacRepo = Mockery::mock(AlmanacRepository::class);
        $this->userRepo = Mockery::mock(UserRepository::class);
        $this->allianceRepo = Mockery::mock(AllianceRepository::class);

        $this->service = new AlmanacService(
            $this->almanacRepo,
            $this->userRepo,
            $this->allianceRepo
        );
    }

    private function makeUser(int $id, string $name): User
    {
        return new User(
            $id,
            'test@test.com',
            $name,
            'Bio',
            'url',
            null,
            null,
            null,
            'hash',
            '2025-01-01'
        );
    }

    private function makeAlliance(int $id, string $name): Alliance
    {
        return new Alliance(
            $id,
            $name,
            'TAG',
            'Desc',
            'url',
            false,
            1,
            1000,
            0,
            null,
            '2025-01-01'
        );
    }

    public function testGetPlayerDossierSuccess()
    {
        $playerId = 1;
        $user = $this->makeUser($playerId, 'Hero');
        
        $this->userRepo->shouldReceive('findById')->with($playerId)->andReturn($user);

        $stats = [
            'battles_won' => 50,
            'battles_lost' => 10,
            'total_battles' => 60,
            'units_killed' => 1000,
            'units_lost' => 200,
            'largest_plunder' => 50000,
            'deadliest_attack' => 150
        ];

        $this->almanacRepo->shouldReceive('getPlayerDossier')->with($playerId)->andReturn($stats);

        $result = $this->service->getPlayerDossier($playerId);

        $this->assertNotNull($result);
        $this->assertEquals($user, $result['player']);
        $this->assertEquals(50, $result['stats']['battles_won']);
        // Check Chart Data Structure
        $this->assertEquals([50, 10], $result['charts']['win_loss']['datasets'][0]['data']);
    }

    public function testGetPlayerDossierNotFound()
    {
        $this->userRepo->shouldReceive('findById')->andReturn(null);
        $result = $this->service->getPlayerDossier(999);
        $this->assertNull($result);
    }

    public function testGetAllianceDossierSuccess()
    {
        $allianceId = 10;
        $alliance = $this->makeAlliance($allianceId, 'Rebels');
        
        $this->allianceRepo->shouldReceive('findById')->with($allianceId)->andReturn($alliance);
        
        $stats = [
            'member_count' => 5,
            'total_wins' => 100,
            'total_losses' => 20,
            'total_plundered' => 1000000,
            'wars_participated' => 3
        ];
        
        $this->almanacRepo->shouldReceive('getAllianceDossier')->with($allianceId)->andReturn($stats);
        
        $members = [['id' => 1, 'character_name' => 'Member1']];
        $this->userRepo->shouldReceive('findAllByAllianceId')->with($allianceId)->andReturn($members);

        $result = $this->service->getAllianceDossier($allianceId);

        $this->assertNotNull($result);
        $this->assertEquals($alliance, $result['alliance']);
        $this->assertEquals(100, $result['stats']['total_wins']);
        $this->assertCount(1, $result['members']);
    }

    public function testSearchPlayers()
    {
        $this->userRepo->shouldReceive('searchByCharacterName')->with('Her', 10)->andReturn([
            ['id' => 1, 'character_name' => 'Hero']
        ]);
        $results = $this->service->searchPlayers('Her');
        $this->assertCount(1, $results);
    }
}