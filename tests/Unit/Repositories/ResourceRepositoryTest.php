<?php

namespace Tests\Unit\Repositories;

use Tests\Unit\TestCase;
use App\Models\Repositories\ResourceRepository;
use App\Models\Entities\UserResource;
use Mockery;
use PDO;
use PDOStatement;

/**
 * Unit Tests for ResourceRepository
 * 
 * Tests resource updates, transaction safety, and balance queries
 * using mocked PDO (no actual database required).
 */
class ResourceRepositoryTest extends TestCase
{
    private ResourceRepository $repository;
    private PDO|Mockery\MockInterface $mockDb;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockDb = Mockery::mock(PDO::class);
        $this->repository = new ResourceRepository($this->mockDb);
    }

    /**
     * Test: findByUserId returns UserResource when found
     */
    public function testFindByUserIdReturnsResourceWhenFound(): void
    {
        $userId = 1;
        $mockData = [
            'user_id' => $userId,
            'credits' => 100000,
            'banked_credits' => 50000,
            'gemstones' => 10,
            'naquadah_crystals' => 5.5,
            'untrained_citizens' => 50,
            'workers' => 100,
            'soldiers' => 200,
            'guards' => 50,
            'spies' => 20,
            'sentries' => 10
        ];

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$userId])
            ->andReturn(true);
        $mockStmt->shouldReceive('fetch')
            ->once()
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($mockData);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->andReturn($mockStmt);

        $result = $this->repository->findByUserId($userId);

        $this->assertInstanceOf(UserResource::class, $result);
        $this->assertEquals($userId, $result->user_id);
        $this->assertEquals(100000, $result->credits);
        $this->assertEquals(50000, $result->banked_credits);
        $this->assertEquals(200, $result->soldiers);
    }

    /**
     * Test: findByUserId returns null when not found
     */
    public function testFindByUserIdReturnsNullWhenNotFound(): void
    {
        $userId = 999;

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$userId])
            ->andReturn(true);
        $mockStmt->shouldReceive('fetch')
            ->once()
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(false);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->andReturn($mockStmt);

        $result = $this->repository->findByUserId($userId);

        $this->assertNull($result);
    }

    /**
     * Test: createDefaults inserts default resource row
     */
    public function testCreateDefaultsInsertsRow(): void
    {
        $userId = 1;

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$userId])
            ->andReturn(true);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->with("INSERT INTO user_resources (user_id) VALUES (?)")
            ->andReturn($mockStmt);

        $this->repository->createDefaults($userId);

        // If we get here without exception, test passes
        $this->assertTrue(true);
    }

    /**
     * Test: updateBankingCredits updates credits and banked credits
     */
    public function testUpdateBankingCreditsUpdatesValues(): void
    {
        $userId = 1;
        $newCredits = 75000;
        $newBankedCredits = 25000;

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$newCredits, $newBankedCredits, $userId])
            ->andReturn(true);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->with("UPDATE user_resources SET credits = ?, banked_credits = ? WHERE user_id = ?")
            ->andReturn($mockStmt);

        $result = $this->repository->updateBankingCredits($userId, $newCredits, $newBankedCredits);

        $this->assertTrue($result);
    }

    /**
     * Test: updateCredits updates only on-hand credits
     */
    public function testUpdateCreditsUpdatesOnlyCredits(): void
    {
        $userId = 1;
        $newCredits = 50000;

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$newCredits, $userId])
            ->andReturn(true);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->with("UPDATE user_resources SET credits = ? WHERE user_id = ?")
            ->andReturn($mockStmt);

        $result = $this->repository->updateCredits($userId, $newCredits);

        $this->assertTrue($result);
    }

    /**
     * Test: updateBattleAttacker updates credits and soldiers
     */
    public function testUpdateBattleAttackerUpdatesCreditsAndSoldiers(): void
    {
        $userId = 1;
        $newCredits = 120000;
        $newSoldiers = 180;

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$newCredits, $newSoldiers, $userId])
            ->andReturn(true);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->andReturn($mockStmt);

        $result = $this->repository->updateBattleAttacker($userId, $newCredits, $newSoldiers);

        $this->assertTrue($result);
    }

    /**
     * Test: updateBattleDefender updates credits and guards
     */
    public function testUpdateBattleDefenderUpdatesCreditsAndGuards(): void
    {
        $userId = 1;
        $newCredits = 80000;
        $newGuards = 40;

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$newCredits, $newGuards, $userId])
            ->andReturn(true);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->andReturn($mockStmt);

        $result = $this->repository->updateBattleDefender($userId, $newCredits, $newGuards);

        $this->assertTrue($result);
    }

    /**
     * Test: updateSpyAttacker updates credits and spies
     */
    public function testUpdateSpyAttackerUpdatesCreditsAndSpies(): void
    {
        $userId = 1;
        $newCredits = 95000;
        $newSpies = 15;

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$newCredits, $newSpies, $userId])
            ->andReturn(true);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->andReturn($mockStmt);

        $result = $this->repository->updateSpyAttacker($userId, $newCredits, $newSpies);

        $this->assertTrue($result);
    }

    /**
     * Test: updateSpyDefender updates sentries
     */
    public function testUpdateSpyDefenderUpdatesSentries(): void
    {
        $userId = 1;
        $newSentries = 8;

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$newSentries, $userId])
            ->andReturn(true);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->with("UPDATE user_resources SET sentries = ? WHERE user_id = ?")
            ->andReturn($mockStmt);

        $result = $this->repository->updateSpyDefender($userId, $newSentries);

        $this->assertTrue($result);
    }
}
