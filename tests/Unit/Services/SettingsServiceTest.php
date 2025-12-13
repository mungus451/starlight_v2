<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\SettingsService;
use App\Core\ServiceResponse;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\SecurityRepository;
use App\Models\Entities\User;
use App\Models\Entities\UserSecurity;
use Mockery;
use PDO;

class SettingsServiceTest extends TestCase
{
    private SettingsService $service;
    private PDO|Mockery\MockInterface $mockDb;
    private UserRepository|Mockery\MockInterface $mockUserRepo;
    private SecurityRepository|Mockery\MockInterface $mockSecurityRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockDb = Mockery::mock(PDO::class);
        $this->mockUserRepo = Mockery::mock(UserRepository::class);
        $this->mockSecurityRepo = Mockery::mock(SecurityRepository::class);

        $this->service = new SettingsService(
            $this->mockDb,
            $this->mockUserRepo,
            $this->mockSecurityRepo
        );
    }

    public function testGetSettingsDataReturnsCorrectStructure(): void
    {
        $userId = 1;
        $user = $this->createMockUser($userId);
        
        // Mock Security Repo logic (returns null or object)
        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($user);
        $this->mockSecurityRepo->shouldReceive('findByUserId')->with($userId)->andReturn(null);

        $result = $this->service->getSettingsData($userId);

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('security', $result);
        $this->assertSame($user, $result['user']);
        $this->assertNull($result['security']);
    }

    public function testUpdateProfileUpdatesDescriptionOnly(): void
    {
        $userId = 1;
        $user = $this->createMockUser($userId, 'OldPfp.png');

        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($user);
        
        $this->mockUserRepo->shouldReceive('updateProfile')
            ->once()
            ->with($userId, 'New Bio', 'OldPfp.png', '555-5555')
            ->andReturn(true);

        $response = $this->service->updateProfile($userId, 'New Bio', [], '555-5555', false);
        $this->assertTrue($response->isSuccess());
    }

    public function testUpdateProfileRemovesPhoto(): void
    {
        $userId = 1;
        $user = $this->createMockUser($userId, 'OldPfp.png');

        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($user);
        
        $this->mockUserRepo->shouldReceive('updateProfile')
            ->once()
            ->with($userId, 'New Bio', null, '555-5555')
            ->andReturn(true);

        $response = $this->service->updateProfile($userId, 'New Bio', [], '555-5555', true);
        $this->assertTrue($response->isSuccess());
    }

    public function testUpdateProfileFailsLongBio(): void
    {
        $userId = 1;
        $longBio = str_repeat('A', 501);
        $response = $this->service->updateProfile($userId, $longBio, [], '', false);
        
        $this->assertFalse($response->isSuccess());
        $this->assertStringContainsString('500 characters', $response->message);
    }

    public function testUpdateEmailSucceeds(): void
    {
        $userId = 1;
        $password = 'password123';
        $newEmail = 'new@test.com';
        
        $user = $this->createMockUser($userId, 'pfp', password_hash($password, PASSWORD_DEFAULT));

        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($user);
        $this->mockUserRepo->shouldReceive('findByEmail')->with($newEmail)->andReturn(null);
        
        $this->mockUserRepo->shouldReceive('updateEmail')
            ->once()
            ->with($userId, $newEmail)
            ->andReturn(true);

        $response = $this->service->updateEmail($userId, $newEmail, $password);
        $this->assertTrue($response->isSuccess());
    }

    public function testUpdateEmailFailsBadPassword(): void
    {
        $userId = 1;
        $user = $this->createMockUser($userId, 'pfp', password_hash('correct', PASSWORD_DEFAULT));

        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($user);

        $response = $this->service->updateEmail($userId, 'new@test.com', 'wrong');
        $this->assertFalse($response->isSuccess());
        $this->assertStringContainsString('Incorrect current password', $response->message);
    }

    public function testUpdatePasswordSucceeds(): void
    {
        $userId = 1;
        $oldPass = 'oldpass';
        $newPass = 'newpass';
        
        $user = $this->createMockUser($userId, 'pfp', password_hash($oldPass, PASSWORD_DEFAULT));

        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($user);
        
        $this->mockUserRepo->shouldReceive('updatePassword')
            ->once()
            ->with($userId, Mockery::on(function($hash) use ($newPass) {
                return password_verify($newPass, $hash);
            }))
            ->andReturn(true);

        $response = $this->service->updatePassword($userId, $oldPass, $newPass, $newPass);
        $this->assertTrue($response->isSuccess());
    }

    public function testUpdatePasswordFailsMismatch(): void
    {
        $userId = 1;
        $user = $this->createMockUser($userId, 'pfp', password_hash('old', PASSWORD_DEFAULT));
        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($user);

        $response = $this->service->updatePassword($userId, 'old', 'new', 'mismatch');
        $this->assertFalse($response->isSuccess());
        $this->assertStringContainsString('passwords do not match', $response->message);
    }

    public function testUpdatePasswordFailsShort(): void
    {
        $userId = 1;
        $user = $this->createMockUser($userId, 'pfp', password_hash('old', PASSWORD_DEFAULT));
        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($user);

        $response = $this->service->updatePassword($userId, 'old', 'hi', 'hi');
        $this->assertFalse($response->isSuccess());
        $this->assertStringContainsString('at least 3 characters', $response->message);
    }

    public function testUpdateSecurityQuestionsSucceeds(): void
    {
        $userId = 1;
        $pass = 'password';
        $user = $this->createMockUser($userId, 'pfp', password_hash($pass, PASSWORD_DEFAULT));

        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($user);

        $this->mockSecurityRepo->shouldReceive('createOrUpdate')
            ->once()
            ->with($userId, 'Q1', Mockery::type('string'), 'Q2', Mockery::type('string'))
            ->andReturn(true);

        $response = $this->service->updateSecurityQuestions($userId, 'Q1', 'A1', 'Q2', 'A2', $pass);
        $this->assertTrue($response->isSuccess());
    }

    public function testUpdateSecurityQuestionsFailsInvalidPassword(): void
    {
        $userId = 1;
        $user = $this->createMockUser($userId, 'pfp', password_hash('correct', PASSWORD_DEFAULT));
        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($user);

        $response = $this->service->updateSecurityQuestions($userId, 'Q', 'A', 'Q', 'A', 'wrong');
        $this->assertFalse($response->isSuccess());
    }

    // --- Helpers ---

    private function createMockUser(int $id, ?string $pfp = null, string $hash = 'hash'): User
    {
        return new User(
            id: $id,
            email: 'test@test.com',
            characterName: 'TestUser',
            bio: null,
            profile_picture_url: $pfp,
            phone_number: null,
            alliance_id: null,
            alliance_role_id: null,
            passwordHash: $hash,
            createdAt: '2024-01-01',
            is_npc: false
        );
    }
}