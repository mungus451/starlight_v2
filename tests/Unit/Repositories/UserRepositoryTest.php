<?php

namespace Tests\Unit\Repositories;

use Tests\Unit\TestCase;
use App\Models\Repositories\UserRepository;
use App\Models\Entities\User;
use Mockery;
use PDO;
use PDOStatement;

/**
 * Unit Tests for UserRepository
 * 
 * Tests CRUD operations, character name lookups, and alliance associations
 * using mocked PDO (no actual database required).
 */
class UserRepositoryTest extends TestCase
{
    private UserRepository $repository;
    private PDO|Mockery\MockInterface $mockDb;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockDb = Mockery::mock(PDO::class);
        $this->repository = new UserRepository($this->mockDb);
    }

    /**
     * Test: findByEmail returns User when found
     */
    public function testFindByEmailReturnsUserWhenFound(): void
    {
        $email = 'test@example.com';
        $mockData = [
            'id' => 1,
            'email' => $email,
            'character_name' => 'TestUser',
            'bio' => null,
            'profile_picture_url' => null,
            'phone_number' => null,
            'alliance_id' => null,
            'alliance_role_id' => null,
            'password_hash' => 'hash',
            'created_at' => '2024-01-01 00:00:00',
            'is_npc' => false
        ];

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$email])
            ->andReturn(true);
        $mockStmt->shouldReceive('fetch')
            ->once()
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($mockData);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->with("SELECT * FROM users WHERE email = ?")
            ->andReturn($mockStmt);

        $result = $this->repository->findByEmail($email);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals($email, $result->email);
        $this->assertEquals('TestUser', $result->characterName);
    }

    /**
     * Test: findByEmail returns null when not found
     */
    public function testFindByEmailReturnsNullWhenNotFound(): void
    {
        $email = 'nonexistent@example.com';

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$email])
            ->andReturn(true);
        $mockStmt->shouldReceive('fetch')
            ->once()
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(false);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->with("SELECT * FROM users WHERE email = ?")
            ->andReturn($mockStmt);

        $result = $this->repository->findByEmail($email);

        $this->assertNull($result);
    }

    /**
     * Test: findByCharacterName returns User when found
     */
    public function testFindByCharacterNameReturnsUserWhenFound(): void
    {
        $charName = 'TestWarrior';
        $mockData = [
            'id' => 2,
            'email' => 'warrior@example.com',
            'character_name' => $charName,
            'bio' => 'Bio text',
            'profile_picture_url' => null,
            'phone_number' => null,
            'alliance_id' => 5,
            'alliance_role_id' => 10,
            'password_hash' => 'hash',
            'created_at' => '2024-01-01 00:00:00',
            'is_npc' => false
        ];

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$charName])
            ->andReturn(true);
        $mockStmt->shouldReceive('fetch')
            ->once()
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($mockData);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->with("SELECT * FROM users WHERE character_name = ?")
            ->andReturn($mockStmt);

        $result = $this->repository->findByCharacterName($charName);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(2, $result->id);
        $this->assertEquals($charName, $result->characterName);
        $this->assertEquals(5, $result->alliance_id);
    }

    /**
     * Test: findByCharacterName returns null when not found
     */
    public function testFindByCharacterNameReturnsNullWhenNotFound(): void
    {
        $charName = 'NonExistent';

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$charName])
            ->andReturn(true);
        $mockStmt->shouldReceive('fetch')
            ->once()
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(false);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->with("SELECT * FROM users WHERE character_name = ?")
            ->andReturn($mockStmt);

        $result = $this->repository->findByCharacterName($charName);

        $this->assertNull($result);
    }

    /**
     * Test: findById returns User when found
     */
    public function testFindByIdReturnsUserWhenFound(): void
    {
        $userId = 42;
        $mockData = [
            'id' => $userId,
            'email' => 'user42@example.com',
            'character_name' => 'User42',
            'bio' => null,
            'profile_picture_url' => null,
            'phone_number' => null,
            'alliance_id' => null,
            'alliance_role_id' => null,
            'password_hash' => 'hash',
            'created_at' => '2024-01-01 00:00:00',
            'is_npc' => false
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
            ->with("SELECT * FROM users WHERE id = ?")
            ->andReturn($mockStmt);

        $result = $this->repository->findById($userId);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($userId, $result->id);
    }

    /**
     * Test: findById returns null when not found
     */
    public function testFindByIdReturnsNullWhenNotFound(): void
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
            ->with("SELECT * FROM users WHERE id = ?")
            ->andReturn($mockStmt);

        $result = $this->repository->findById($userId);

        $this->assertNull($result);
    }

    /**
     * Test: createUser inserts and returns new user ID
     */
    public function testCreateUserInsertsAndReturnsId(): void
    {
        $email = 'new@example.com';
        $charName = 'NewUser';
        $passwordHash = 'hashed_password';
        $newId = 123;

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$email, $charName, $passwordHash])
            ->andReturn(true);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->with("INSERT INTO users (email, character_name, password_hash) VALUES (?, ?, ?)")
            ->andReturn($mockStmt);

        $this->mockDb->shouldReceive('lastInsertId')
            ->once()
            ->andReturn((string)$newId);

        $result = $this->repository->createUser($email, $charName, $passwordHash);

        $this->assertEquals($newId, $result);
    }

    /**
     * Test: updateProfile updates bio, profile picture, and phone
     */
    public function testUpdateProfileUpdatesFields(): void
    {
        $userId = 1;
        $bio = 'New bio';
        $pfpUrl = 'https://example.com/pic.jpg';
        $phone = '+1234567890';

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$bio, $pfpUrl, $phone, $userId])
            ->andReturn(true);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->with("UPDATE users SET bio = ?, profile_picture_url = ?, phone_number = ? WHERE id = ?")
            ->andReturn($mockStmt);

        $result = $this->repository->updateProfile($userId, $bio, $pfpUrl, $phone);

        $this->assertTrue($result);
    }

    /**
     * Test: updateEmail updates user email
     */
    public function testUpdateEmailUpdatesEmail(): void
    {
        $userId = 1;
        $newEmail = 'newemail@example.com';

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$newEmail, $userId])
            ->andReturn(true);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->with("UPDATE users SET email = ? WHERE id = ?")
            ->andReturn($mockStmt);

        $result = $this->repository->updateEmail($userId, $newEmail);

        $this->assertTrue($result);
    }

    /**
     * Test: updatePassword updates password hash
     */
    public function testUpdatePasswordUpdatesHash(): void
    {
        $userId = 1;
        $newHash = 'new_hashed_password';

        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')
            ->once()
            ->with([$newHash, $userId])
            ->andReturn(true);

        $this->mockDb->shouldReceive('prepare')
            ->once()
            ->with("UPDATE users SET password_hash = ? WHERE id = ?")
            ->andReturn($mockStmt);

        $result = $this->repository->updatePassword($userId, $newHash);

        $this->assertTrue($result);
    }
}
