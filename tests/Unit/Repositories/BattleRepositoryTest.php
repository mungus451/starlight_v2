<?php

namespace Tests\Unit\Repositories;

use Tests\Unit\TestCase;
use App\Models\Repositories\BattleRepository;
use App\Models\Entities\BattleReport;
use Mockery;
use PDO;
use PDOStatement;

/**
 * Unit Tests for BattleRepository
 * 
 * Tests report creation, offensive/defensive report retrieval
 * using mocked PDO (no actual database required).
 */
class BattleRepositoryTest extends TestCase
{
    private BattleRepository $repository;
    private PDO|Mockery\MockInterface $mockDb;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockDb = Mockery::mock(PDO::class);
        $this->repository = new BattleRepository($this->mockDb);
    }

    /**
     * Test: createReport inserts and returns report ID
     */
    public function testCreateReportInsertsAndReturnsId(): void
    {
        $attackerId = 1;
        $defenderId = 2;
        $attackType = 'plunder';
        $attackResult = 'victory';
        $soldiersSent = 100;
        $attackerSoldiersLost = 20;
        $defenderGuardsLost = 30;
        $creditsPlundered = 50000;
        $experienceGained = 250;
        $warPrestigeGained = 10;
        $netWorthStolen = 1000;
        $attackerOffensePower = 5000;
        $defenderDefensePower = 4000;
        $newReportId = 456;

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->andReturn(true);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->andReturn($mockStmt);

        $this->mockDb->shouldReceive('lastInsertId')
            ->once()
            ->andReturn((string)$newReportId);

        $result = $this->repository->createReport(
            $attackerId,
            $defenderId,
            $attackType,
            $attackResult,
            $soldiersSent,
            $attackerSoldiersLost,
            $defenderGuardsLost,
            $creditsPlundered,
            $experienceGained,
            $warPrestigeGained,
            $netWorthStolen,
            $attackerOffensePower,
            $defenderDefensePower,
            0, // defenderTotalGuards
            false // isHidden
        );

        $this->assertEquals($newReportId, $result);
    }

    /**
     * Test: findReportsByAttackerId returns array of reports
     */
    public function testFindReportsByAttackerIdReturnsReports(): void
    {
        $attackerId = 1;
        $mockData = [
            'id' => 10,
            'attacker_id' => $attackerId,
            'defender_id' => 2,
            'attack_type' => 'plunder',
            'attack_result' => 'victory',
            'soldiers_sent' => 100,
            'attacker_soldiers_lost' => 20,
            'defender_guards_lost' => 30,
            'credits_plundered' => 50000,
            'experience_gained' => 250,
            'war_prestige_gained' => 10,
            'net_worth_stolen' => 1000,
            'attacker_offense_power' => 5000,
            'defender_defense_power' => 4000,
            'created_at' => '2024-01-01 10:00:00',
            'defender_name' => 'Defender1',
            'attacker_name' => 'Attacker1'
        ];

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$attackerId])
            ->andReturn(true);
        // Repository uses while loop with fetch() - mock first fetch returns data, second returns false
        $mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($mockData, false);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->andReturn($mockStmt);

        $result = $this->repository->findReportsByAttackerId($attackerId);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(BattleReport::class, $result[0]);
        $this->assertEquals(10, $result[0]->id);
        $this->assertEquals($attackerId, $result[0]->attacker_id);
    }

    /**
     * Test: findReportsByDefenderId returns array of reports
     */
    public function testFindReportsByDefenderIdReturnsReports(): void
    {
        $defenderId = 2;
        $mockData = [
            'id' => 11,
            'attacker_id' => 1,
            'defender_id' => $defenderId,
            'attack_type' => 'plunder',
            'attack_result' => 'defeat',
            'soldiers_sent' => 50,
            'attacker_soldiers_lost' => 40,
            'defender_guards_lost' => 10,
            'credits_plundered' => 0,
            'experience_gained' => 50,
            'war_prestige_gained' => 0,
            'net_worth_stolen' => 0,
            'attacker_offense_power' => 3000,
            'defender_defense_power' => 4000,
            'created_at' => '2024-01-01 11:00:00',
            'defender_name' => 'Defender2',
            'attacker_name' => 'Attacker2'
        ];

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$defenderId])
            ->andReturn(true);
        // Repository uses while loop with fetch() - mock first fetch returns data, second returns false
        $mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($mockData, false);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->andReturn($mockStmt);

        $result = $this->repository->findReportsByDefenderId($defenderId);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(BattleReport::class, $result[0]);
        $this->assertEquals(11, $result[0]->id);
        $this->assertEquals($defenderId, $result[0]->defender_id);
    }

    /**
     * Test: findReportById returns BattleReport when found and authorized
     */
    public function testFindReportByIdReturnsReportWhenAuthorized(): void
    {
        $reportId = 10;
        $viewerId = 1;
        $mockData = [
            'id' => $reportId,
            'attacker_id' => $viewerId,
            'defender_id' => 2,
            'attack_type' => 'plunder',
            'attack_result' => 'victory',
            'soldiers_sent' => 100,
            'attacker_soldiers_lost' => 20,
            'defender_guards_lost' => 30,
            'credits_plundered' => 50000,
            'experience_gained' => 250,
            'war_prestige_gained' => 10,
            'net_worth_stolen' => 1000,
            'attacker_offense_power' => 5000,
            'defender_defense_power' => 4000,
            'created_at' => '2024-01-01 10:00:00',
            'defender_name' => 'Defender1',
            'attacker_name' => 'Attacker1'
        ];

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$reportId, $viewerId, $viewerId])
            ->andReturn(true);
        $mockStmt->shouldReceive('fetch')
            ->once()
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($mockData);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->andReturn($mockStmt);

        $result = $this->repository->findReportById($reportId, $viewerId);

        $this->assertInstanceOf(BattleReport::class, $result);
        $this->assertEquals($reportId, $result->id);
        $this->assertEquals($viewerId, $result->attacker_id);
    }

    /**
     * Test: findReportById returns null when not found
     */
    public function testFindReportByIdReturnsNullWhenNotFound(): void
    {
        $reportId = 999;
        $viewerId = 1;

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$reportId, $viewerId, $viewerId])
            ->andReturn(true);
        $mockStmt->shouldReceive('fetch')
            ->once()
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(false);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->andReturn($mockStmt);

        $result = $this->repository->findReportById($reportId, $viewerId);

        $this->assertNull($result);
    }

    /**
     * Test: findReportsByAttackerId returns empty array when no reports
     */
    public function testFindReportsByAttackerIdReturnsEmptyArrayWhenNoReports(): void
    {
        $attackerId = 1;

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$attackerId])
            ->andReturn(true);
        // Repository uses while loop with fetch() - return false immediately for empty
        $mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(false);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->andReturn($mockStmt);

        $result = $this->repository->findReportsByAttackerId($attackerId);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
