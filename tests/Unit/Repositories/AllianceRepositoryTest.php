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
        $amount = 500;

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$amount, $allianceId])
            ->andReturn(true);

        // Verify SQL contains atomic update
        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->with(Mockery::pattern('/bank_credits\s*=\s*GREATEST\(0,\s*CAST\(bank_credits AS SIGNED\)\s*\+\s*CAST\(\? AS SIGNED\)\)/s')) // /s for dotall (newlines)
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
        $this->assertTrue(true); // Asserting no exceptions were thrown
    }
}
