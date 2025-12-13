<?php

namespace Tests\Unit\Repositories;

use Tests\Unit\TestCase;
use App\Models\Repositories\SpyRepository;
use App\Models\Entities\SpyReport;
use Mockery;
use PDO;
use PDOStatement;

class SpyRepositoryTest extends TestCase
{
    private SpyRepository $repository;
    private PDO|Mockery\MockInterface $mockDb;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockDb = Mockery::mock(PDO::class);
        $this->repository = new SpyRepository($this->mockDb);
    }

    public function testCreateReportExecutesInsert(): void
    {
        $attackerId = 1;
        $defenderId = 2;
        $result = 'success';
        $sent = 10;
        $lostA = 0;
        $lostD = 0;
        $defSentries = 5;

        $newId = 100;

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')->once()->andReturn(true);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->with(Mockery::pattern('/INSERT INTO spy_reports/'))
            ->andReturn($mockStmt);

        $this->mockDb->shouldReceive('lastInsertId')->once()->andReturn((string)$newId);

        // Call with minimal intel (nulls)
        $id = $this->repository->createReport(
            $attackerId, $defenderId, $result, $sent, $lostA, $lostD, $defSentries,
            null, null, null, null, null, null, null,
            null, null, null, null, null, null, null
        );

        $this->assertEquals($newId, $id);
    }

    public function testFindReportByIdHydratesEntity(): void
    {
        $reportId = 50;
        $viewerId = 1;

        $row = [
            'id' => $reportId,
            'attacker_id' => 1,
            'defender_id' => 2,
            'created_at' => '2024-01-01 12:00:00',
            'operation_result' => 'success',
            'spies_sent' => 10,
            'spies_lost_attacker' => 0,
            'sentries_lost_defender' => 0,
            'defender_total_sentries' => 5,
            // Nullable columns can be omitted or null in fetch result
            'credits_seen' => 1000,
            'defender_name' => 'Defender',
            'attacker_name' => 'Attacker'
        ];

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$reportId, $viewerId, $viewerId])
            ->andReturn(true);
            
        $mockStmt->shouldReceive('fetch')
            ->once()
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($row);

        $this->mockDb->shouldReceive('prepare')->once()->andReturn($mockStmt);

        $report = $this->repository->findReportById($reportId, $viewerId);

        $this->assertInstanceOf(SpyReport::class, $report);
        $this->assertEquals($reportId, $report->id);
        $this->assertEquals(1000, $report->credits_seen);
        $this->assertEquals('Defender', $report->defender_name);
    }

    public function testFindReportsByAttackerIdReturnsArray(): void
    {
        $attackerId = 1;
        
        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$attackerId])
            ->andReturn(true);

        // Return one row then false
        $mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(
                [
                    'id' => 10,
                    'attacker_id' => $attackerId,
                    'defender_id' => 2,
                    'created_at' => '2024-01-01',
                    'operation_result' => 'success',
                    'spies_sent' => 5,
                    'spies_lost_attacker' => 0,
                    'sentries_lost_defender' => 0,
                    'defender_total_sentries' => 0,
                    'defender_name' => 'Def'
                ], 
                false
            );

        $this->mockDb->shouldReceive('prepare')->once()->andReturn($mockStmt);

        $reports = $this->repository->findReportsByAttackerId($attackerId);

        $this->assertIsArray($reports);
        $this->assertCount(1, $reports);
        $this->assertInstanceOf(SpyReport::class, $reports[0]);
    }
}
