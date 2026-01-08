<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\ViewContextService;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\WarRepository;
use App\Models\Repositories\AllianceBankLogRepository;
use App\Models\Repositories\TreatyRepository;
use App\Models\Repositories\WarBattleLogRepository;
use App\Models\Repositories\BattleRepository;
use App\Models\Repositories\SpyRepository;
use App\Models\Services\LevelCalculatorService;
use App\Models\Services\DashboardService;
use App\Models\Services\RealmNewsService;
use App\Models\Services\BattleService;
use App\Presenters\DashboardPresenter;
use App\Models\Entities\BattleReport;
use App\Models\Entities\SpyReport;
use Mockery;
use ReflectionMethod;

class DefconLogicTest extends TestCase
{
    private $service;
    private $battleRepo;
    private $spyRepo;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock all dependencies (ViewContextService has many)
        $this->battleRepo = Mockery::mock(BattleRepository::class);
        $this->spyRepo = Mockery::mock(SpyRepository::class);
        
        // Unused in these specific tests but required for constructor
        $statsRepo = Mockery::mock(StatsRepository::class);
        $levelCalc = Mockery::mock(LevelCalculatorService::class);
        $dashService = Mockery::mock(DashboardService::class);
        $dashPresenter = Mockery::mock(DashboardPresenter::class);
        $newsService = Mockery::mock(RealmNewsService::class);
        $battleService = Mockery::mock(BattleService::class);
        $userRepo = Mockery::mock(UserRepository::class);
        $allianceRepo = Mockery::mock(AllianceRepository::class);
        $warRepo = Mockery::mock(WarRepository::class);
        $bankLogRepo = Mockery::mock(AllianceBankLogRepository::class);
        $treatyRepo = Mockery::mock(TreatyRepository::class);
        $warLogRepo = Mockery::mock(WarBattleLogRepository::class);

        $this->service = new ViewContextService(
            $statsRepo, $levelCalc, $dashService, $dashPresenter,
            $newsService, $battleService, $userRepo, $allianceRepo,
            $warRepo, $bankLogRepo, $treatyRepo, $warLogRepo,
            $this->battleRepo, $this->spyRepo
        );
    }

    /**
     * Helper to call the private calculateDefcon method
     */
    private function callCalculateDefcon(int $allianceId): int
    {
        $reflection = new ReflectionMethod(ViewContextService::class, 'calculateDefcon');
        $reflection->setAccessible(true);
        return $reflection->invoke($this->service, $allianceId);
    }

    /**
     * Helper to create a Mock Battle Report Array
     */
    private function createMockBattle(string $result, int $secondsAgo)
    {
        return [
            'id' => 1,
            'attack_result' => $result,
            'seconds_ago' => $secondsAgo
        ];
    }

    /**
     * Helper to create a Mock Spy Report Array
     */
    private function createMockSpy(string $result, int $secondsAgo)
    {
        return [
            'id' => 1,
            'operation_result' => $result,
            'seconds_ago' => $secondsAgo
        ];
    }

    public function testDefcon5WhenNoIncidents()
    {
        $this->battleRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn(null);
        $this->spyRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn(null);

        $result = $this->callCalculateDefcon(1);
        $this->assertEquals(5, $result);
    }

    public function testDefcon1SuccessfulBattleWithin2Hours()
    {
        $battle = $this->createMockBattle('victory', 3600); // 1 hour ago

        $this->battleRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn($battle);
        $this->spyRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn(null);

        $this->assertEquals(1, $this->callCalculateDefcon(1));
    }

    public function testDefcon2SuccessfulBattleWithin4Hours()
    {
        $battle = $this->createMockBattle('victory', 3 * 3600); // 3 hours ago

        $this->battleRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn($battle);
        $this->spyRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn(null);

        $this->assertEquals(2, $this->callCalculateDefcon(1));
    }

    public function testDefcon3FailedBattleWithin2Hours()
    {
        $battle = $this->createMockBattle('defeat', 3600); // Failure, 1 hour ago

        $this->battleRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn($battle);
        $this->spyRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn(null);

        $this->assertEquals(3, $this->callCalculateDefcon(1));
    }

    public function testDefcon4FailedBattleWithin4Hours()
    {
        $battle = $this->createMockBattle('defeat', 3 * 3600); // 3 hours ago

        $this->battleRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn($battle);
        $this->spyRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn(null);

        $this->assertEquals(4, $this->callCalculateDefcon(1));
    }

    public function testDefcon5After8HoursFromSuccessfulBattle()
    {
        $battle = $this->createMockBattle('victory', 9 * 3600); // 9 hours ago

        $this->battleRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn($battle);
        $this->spyRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn(null);

        $this->assertEquals(5, $this->callCalculateDefcon(1));
    }

    public function testDefcon3WhenSuccessfulBattleIsOldAndFailedSpyIsNew()
    {
        // Successful battle 10 hours ago (Decayed to 5)
        $battle = $this->createMockBattle('victory', 10 * 3600);

        // Failed spy 30 mins ago (Base 3)
        $spy = $this->createMockSpy('failure', 1800);

        $this->battleRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn($battle);
        $this->spyRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn($spy);

        // The Spy is more recent, so its base (3) + recovery (0) = 3
        $this->assertEquals(3, $this->callCalculateDefcon(1));
    }

    public function testDefcon4After6HoursFromSuccessfulBattle()
    {
        $battle = $this->createMockBattle('victory', 7 * 3600); // 7 hours ago

        $this->battleRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn($battle);
        $this->spyRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn(null);

        $this->assertEquals(4, $this->callCalculateDefcon(1));
    }

    public function testDefcon5After4HoursFromFailedBattle()
    {
        $battle = $this->createMockBattle('defeat', 5 * 3600); // 5 hours ago

        $this->battleRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn($battle);
        $this->spyRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn(null);

        $this->assertEquals(5, $this->callCalculateDefcon(1));
    }

    public function testDefcon1WhenSuccessfulSpyIsNew()
    {
        $spy = $this->createMockSpy('success', 3600); // 1 hour ago

        $this->battleRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn(null);
        $this->spyRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn($spy);

        $this->assertEquals(1, $this->callCalculateDefcon(1));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
