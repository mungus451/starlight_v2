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
     * Helper to create a Mock Battle Report
     */
    private function createMockBattle(string $result, string $time)
    {
        return new BattleReport(
            id: 1, attacker_id: 1, defender_id: 2,
            created_at: $time,
            attack_type: 'plunder',
            attack_result: $result,
            soldiers_sent: 100, attacker_soldiers_lost: 10, defender_guards_lost: 10,
            credits_plundered: 1000, experience_gained: 100, war_prestige_gained: 1,
            net_worth_stolen: 100, attacker_offense_power: 1000, defender_defense_power: 1000,
            defender_total_guards: 1000
        );
    }

    /**
     * Helper to create a Mock Spy Report
     */
    private function createMockSpy(string $result, string $time)
    {
        return new SpyReport(
            id: 1, attacker_id: 1, defender_id: 2,
            created_at: $time,
            operation_result: $result,
            spies_sent: 10, spies_lost_attacker: 0, sentries_lost_defender: 0,
            defender_total_sentries: 100,
            credits_seen: 1000, gemstones_seen: 0, workers_seen: 0,
            soldiers_seen: 0, guards_seen: 0, spies_seen: 0, sentries_seen: 0,
            fortification_level_seen: 0, offense_upgrade_level_seen: 0,
            defense_upgrade_level_seen: 0, spy_upgrade_level_seen: 0,
            economy_upgrade_level_seen: 0, population_level_seen: 0,
            armory_level_seen: 0
        );
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
        $time = date('Y-m-d H:i:s', time() - (1 * 3600)); // 1 hour ago
        $battle = $this->createMockBattle('victory', $time);

        $this->battleRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn($battle);
        $this->spyRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn(null);

        $this->assertEquals(1, $this->callCalculateDefcon(1));
    }

    public function testDefcon2SuccessfulBattleWithin4Hours()
    {
        $time = date('Y-m-d H:i:s', time() - (3 * 3600)); // 3 hours ago
        $battle = $this->createMockBattle('victory', $time);

        $this->battleRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn($battle);
        $this->spyRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn(null);

        $this->assertEquals(2, $this->callCalculateDefcon(1));
    }

    public function testDefcon3FailedBattleWithin2Hours()
    {
        $time = date('Y-m-d H:i:s', time() - (1 * 3600)); // 1 hour ago
        $battle = $this->createMockBattle('defeat', $time); // Failure

        $this->battleRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn($battle);
        $this->spyRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn(null);

        $this->assertEquals(3, $this->callCalculateDefcon(1));
    }

    public function testDefcon4FailedBattleWithin4Hours()
    {
        $time = date('Y-m-d H:i:s', time() - (3 * 3600)); // 3 hours ago
        $battle = $this->createMockBattle('defeat', $time);

        $this->battleRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn($battle);
        $this->spyRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn(null);

        $this->assertEquals(4, $this->callCalculateDefcon(1));
    }

    public function testDefcon5After8HoursFromSuccessfulBattle()
    {
        $time = date('Y-m-d H:i:s', time() - (9 * 3600)); // 9 hours ago
        $battle = $this->createMockBattle('victory', $time);

        $this->battleRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn($battle);
        $this->spyRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn(null);

        $this->assertEquals(5, $this->callCalculateDefcon(1));
    }

    public function testDefcon3WhenSuccessfulBattleIsOldAndFailedSpyIsNew()
    {
        // Successful battle 10 hours ago (Decayed to 5)
        $battleTime = date('Y-m-d H:i:s', time() - (10 * 3600));
        $battle = $this->createMockBattle('victory', $battleTime);

        // Failed spy 30 mins ago (Base 3)
        $spyTime = date('Y-m-d H:i:s', time() - (1800));
        $spy = $this->createMockSpy('failure', $spyTime);

        $this->battleRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn($battle);
        $this->spyRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn($spy);

        // The Spy is more recent, so its base (3) + recovery (0) = 3
        $this->assertEquals(3, $this->callCalculateDefcon(1));
    }

    public function testDefcon4After6HoursFromSuccessfulBattle()
    {
        $time = date('Y-m-d H:i:s', time() - (7 * 3600)); // 7 hours ago
        $battle = $this->createMockBattle('victory', $time);

        $this->battleRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn($battle);
        $this->spyRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn(null);

        $this->assertEquals(4, $this->callCalculateDefcon(1));
    }

    public function testDefcon5After4HoursFromFailedBattle()
    {
        $time = date('Y-m-d H:i:s', time() - (5 * 3600)); // 5 hours ago
        $battle = $this->createMockBattle('defeat', $time);

        $this->battleRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn($battle);
        $this->spyRepo->shouldReceive('findLatestDefenseByAlliance')->andReturn(null);

        $this->assertEquals(5, $this->callCalculateDefcon(1));
    }

    public function testDefcon1WhenSuccessfulSpyIsNew()
    {
        $time = date('Y-m-d H:i:s', time() - (1 * 3600)); // 1 hour ago
        $spy = $this->createMockSpy('success', $time);

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
