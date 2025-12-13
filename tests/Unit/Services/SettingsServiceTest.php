<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\SettingsService;
use App\Core\ServiceResponse;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\SecurityRepository;
use App\Models\Entities\User;
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

    public function testUpdateProfileUpdatesDescriptionOnly(): void
    {
        $userId = 1;
        $user = $this->createMockUser($userId, 'OldPfp.png');

        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($user);
        
        // Expect update with SAME filename (no file uploaded, remove=false)
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
        
        // Expect update with NULL filename (remove=true)
        $this->mockUserRepo->shouldReceive('updateProfile')
            ->once()
            ->with($userId, 'New Bio', null, '555-5555')
            ->andReturn(true);

        // Note: Logic attempts to delete file from disk. In unit test env, file likely doesn't exist, 
        // so `file_exists` returns false and `unlink` isn't called, preventing errors.
        
        $response = $this->service->updateProfile($userId, 'New Bio', [], '555-5555', true);
        $this->assertTrue($response->isSuccess());
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
