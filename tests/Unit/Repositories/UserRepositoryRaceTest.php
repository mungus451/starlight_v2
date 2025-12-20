<?php

namespace Tests\Unit\Repositories;

use Tests\Unit\TestCase;
use App\Models\Repositories\UserRepository;
use Mockery;

/**
 * Tests for race-related functionality in UserRepository
 */
class UserRepositoryRaceTest extends TestCase
{
    /**
     * Test that updateRace method calls the database with correct parameters
     */
    public function testUpdateRaceExecutesCorrectQuery(): void
    {
        // Arrange
        $mockDb = $this->createMockPDO();
        $mockStmt = $this->createMockStatement();
        
        $userId = 123;
        $race = 'human';
        
        // Expect PDO::prepare to be called with the update query
        $mockDb->shouldReceive('prepare')
            ->once()
            ->with('UPDATE users SET race = ? WHERE id = ?')
            ->andReturn($mockStmt);
        
        // Expect execute to be called with correct parameters
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$race, $userId])
            ->andReturn(true);
        
        $repository = new UserRepository($mockDb);
        
        // Act
        $result = $repository->updateRace($userId, $race);
        
        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test that updateRace returns false on database failure
     */
    public function testUpdateRaceReturnsFalseOnFailure(): void
    {
        // Arrange
        $mockDb = $this->createMockPDO();
        $mockStmt = $this->createMockStatement();
        
        $userId = 123;
        $race = 'cyborg';
        
        $mockDb->shouldReceive('prepare')
            ->once()
            ->andReturn($mockStmt);
        
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$race, $userId])
            ->andReturn(false);
        
        $repository = new UserRepository($mockDb);
        
        // Act
        $result = $repository->updateRace($userId, $race);
        
        // Assert
        $this->assertFalse($result);
    }
}
