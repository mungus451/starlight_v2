<?php

namespace Tests\Unit\Repositories;

use Tests\Unit\TestCase;
use App\Models\Repositories\AllianceRepository;
use App\Models\Entities\Alliance;
use Mockery;
use PDO;
use PDOStatement;

class AllianceRepositoryTest extends TestCase
{
    private AllianceRepository $repository;
    private PDO|Mockery\MockInterface $mockDb;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockDb = Mockery::mock(PDO::class);
        $this->repository = new AllianceRepository($this->mockDb);
    }

    public function testCreateAllianceExecutesCorrectSql(): void
    {
        $name = 'Test Alliance';
        $tag = 'TEST';
        $leaderId = 1;
        $newId = 10;

        // Expect Insert
        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$name, $tag, $leaderId])
            ->andReturn(true);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->with(Mockery::pattern('/INSERT INTO alliances/'))
            ->andReturn($mockStmt);

        $this->mockDb->shouldReceive('lastInsertId')
            ->once()
            ->andReturn((string)$newId);

        $result = $this->repository->create($name, $tag, $leaderId);

        $this->assertEquals($newId, $result);
    }

    public function testUpdateBankCreditsRelativeExecutesAtomicUpdate(): void
    {
        $allianceId = 1;
        $amount = 500.0;
        $cap = '1000000000000000000000000000000000000000000000000000000000000'; // 1.0e60 formatted as string
        $amountStr = '500';

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with(['cap' => $cap, 'amount' => $amountStr, 'id' => $allianceId])
            ->andReturn(true);

        // Verify SQL contains atomic update for positive amount with CAP
        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->with(Mockery::pattern('/UPDATE alliances SET bank_credits = LEAST\(:cap, bank_credits \+ :amount\) WHERE id = :id/'))
            ->andReturn($mockStmt);

        $this->repository->updateBankCreditsRelative($allianceId, $amount);
        $this->assertTrue(true);
    }

    public function testUpdateBankCreditsRelativeHandlesNegativeUpdate(): void
    {
        $allianceId = 1;
        $amount = -500.0;
        $amountStr = '500';

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with(['absAmount1' => $amountStr, 'absAmount2' => $amountStr, 'id' => $allianceId])
            ->andReturn(true);

        // Verify SQL contains logic for negative amount (no casting)
        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->with(Mockery::pattern('/UPDATE alliances SET bank_credits = IF\(bank_credits < :absAmount1, 0, bank_credits - :absAmount2\) WHERE id = :id/'))
            ->andReturn($mockStmt);

        $this->repository->updateBankCreditsRelative($allianceId, $amount);
        $this->assertTrue(true);
    }

    public function testUpdateLastCompoundAtUpdatesTimestamp(): void
    {
        $allianceId = 1;

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$allianceId])
            ->andReturn(true);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->with(Mockery::pattern('/UPDATE alliances SET last_compound_at = NOW\(\) WHERE id = \?/'))
            ->andReturn($mockStmt);

        $this->repository->updateLastCompoundAt($allianceId);
        $this->assertTrue(true);
    }

    public function testGetTotalCountReturnsInt(): void
    {
        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('fetch')
            ->once()
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 5]);

        $this->mockDb->shouldReceive('query')
            ->once()
            ->with('SELECT COUNT(id) as total FROM alliances')
            ->andReturn($mockStmt);

        $result = $this->repository->getTotalCount();
        $this->assertEquals(5, $result);
    }

    public function testGetPaginatedAlliancesReturnsEntities(): void
    {
        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('bindParam')->times(2);
        $mockStmt->shouldReceive('execute')->once();
        
        $row = [
            'id' => 1, 'name' => 'A', 'tag' => 'A', 'leader_id' => 1, 'net_worth' => 100, 
            'bank_credits' => 0, 'created_at' => '2024-01-01', 'is_joinable' => 1
        ];
        
        $mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($row, false);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->with(Mockery::pattern('/SELECT \* FROM alliances/'))
            ->andReturn($mockStmt);

        $result = $this->repository->getPaginatedAlliances(10, 0);
        
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Alliance::class, $result[0]);
    }

    public function testGetLeaderboardAlliancesReturnsArray(): void
    {
        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('bindParam')->times(2);
        $mockStmt->shouldReceive('execute')->once();
        
        $data = [
            ['id' => 1, 'name' => 'A', 'member_count' => 5]
        ];
        
        $mockStmt->shouldReceive('fetchAll')
            ->once()
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($data);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->with(Mockery::pattern('/SELECT.*member_count/s'))
            ->andReturn($mockStmt);

        $result = $this->repository->getLeaderboardAlliances(10, 0);
        
        $this->assertIsArray($result);
        $this->assertEquals(5, $result[0]['member_count']);
    }
}