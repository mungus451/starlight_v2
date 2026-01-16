<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\BankService;
use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Entities\User;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStats;
use App\Models\Entities\UserStructure;
use Mockery;
use PDO;

/**
 * Unit Tests for BankService
 * 
 * Tests deposit/withdrawal validation, interest calculations, 
 * charge limits, and transfer operations without database dependencies.
 */
class BankServiceTest extends TestCase
{
    private BankService $service;
    private PDO|Mockery\MockInterface $mockDb;
    private Config|Mockery\MockInterface $mockConfig;
    private ResourceRepository|Mockery\MockInterface $mockResourceRepo;
    private UserRepository|Mockery\MockInterface $mockUserRepo;
    private StatsRepository|Mockery\MockInterface $mockStatsRepo;
    private StructureRepository|Mockery\MockInterface $mockStructureRepo;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks
        $this->mockDb = Mockery::mock(PDO::class);
        $this->mockConfig = Mockery::mock(Config::class);
        $this->mockResourceRepo = Mockery::mock(ResourceRepository::class);
        $this->mockUserRepo = Mockery::mock(UserRepository::class);
        $this->mockStatsRepo = Mockery::mock(StatsRepository::class);
        $this->mockStructureRepo = Mockery::mock(StructureRepository::class);

        // Instantiate service
        $this->service = new BankService(
            $this->mockDb,
            $this->mockConfig,
            $this->mockResourceRepo,
            $this->mockUserRepo,
            $this->mockStatsRepo,
            $this->mockStructureRepo
        );
    }

    /**
     * Test: getBankData returns correct structure
     */
    public function testGetBankDataReturnsCorrectStructure(): void
    {
        $userId = 1;

        $mockStats = new UserStats(
            user_id: $userId,
            level: 5,
            experience: 1000,
            net_worth: 500000,
            war_prestige: 100,
            energy: 100,
            attack_turns: 50,
            level_up_points: 0,
            strength_points: 0,
            constitution_points: 0,
            wealth_points: 0,
            dexterity_points: 0,
            charisma_points: 0,
            deposit_charges: 5,
            last_deposit_at: null,
            battles_won: 0,
            battles_lost: 0,
            spy_successes: 0,
            spy_failures: 0
        );

        $mockResource = new UserResource(
            user_id: $userId,
            credits: 100000,
            banked_credits: 50000,
            gemstones: 0,
            naquadah_crystals: 0.0,
            untrained_citizens: 50,
            workers: 10,
            soldiers: 100,
            guards: 50,
            spies: 10,
            sentries: 5
        );

        $mockStructures = new UserStructure(
            user_id: $userId,
            fortification_level: 1,
            offense_upgrade_level: 1,
            defense_upgrade_level: 1,
            spy_upgrade_level: 1,
            economy_upgrade_level: 1,
            population_level: 1,
            armory_level: 1,
            accounting_firm_level: 1
        );

        $bankConfig = [
            'deposit_max_charges' => 5,
            'deposit_charge_regen_hours' => 24
        ];

        $this->mockStatsRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockStats);

        $this->mockStructureRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockStructures);

        $this->mockConfig->shouldReceive('get')
            ->once()
            ->with('bank')
            ->andReturn($bankConfig);

        $this->mockResourceRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockResource);

        // Act
        $result = $this->service->getBankData($userId);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('resources', $result);
        $this->assertArrayHasKey('stats', $result);
        $this->assertArrayHasKey('bankConfig', $result);
        $this->assertSame($mockResource, $result['resources']);
        $this->assertSame($mockStats, $result['stats']);
    }

    /**
     * Test: deposit rejects zero or negative amount
     */
    public function testDepositRejectsInvalidAmount(): void
    {
        $response = $this->service->deposit(1, 0);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('Amount to deposit must be a positive number.', $response->message);

        $response = $this->service->deposit(1, -100);
        $this->assertFalse($response->isSuccess());
    }

    /**
     * Test: deposit rejects when exceeding 80% limit
     */
    public function testDepositRejectsWhenExceeding80PercentLimit(): void
    {
        $userId = 1;
        $amount = 90000; // Trying to deposit 90% of 100000

        $mockResource = new UserResource(
            user_id: $userId,
            credits: 100000,
            banked_credits: 0,
            gemstones: 0,
            naquadah_crystals: 0.0,
            untrained_citizens: 0,
            workers: 0,
            soldiers: 0,
            guards: 0,
            spies: 0,
            sentries: 0
        );

        $mockStats = new UserStats(
            user_id: $userId,
            level: 1,
            experience: 0,
            net_worth: 0,
            war_prestige: 0,
            energy: 100,
            attack_turns: 50,
            level_up_points: 0,
            strength_points: 0,
            constitution_points: 0,
            wealth_points: 0,
            dexterity_points: 0,
            charisma_points: 0,
            deposit_charges: 5,
            last_deposit_at: null,
            battles_won: 0,
            battles_lost: 0,
            spy_successes: 0,
            spy_failures: 0
        );

        $this->mockResourceRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockResource);

        $this->mockStatsRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockStats);

        $this->mockConfig->shouldReceive('get')
            ->once()
            ->with('bank')
            ->andReturn(['deposit_percent_limit' => 0.8]);

        $response = $this->service->deposit($userId, $amount);

        $this->assertFalse($response->isSuccess());
        $this->assertStringContainsString('80%', $response->message);
    }

    /**
     * Test: deposit rejects when no deposit charges
     */
    public function testDepositRejectsWhenNoCharges(): void
    {
        $userId = 1;
        $amount = 50000;

        $mockResource = new UserResource(
            user_id: $userId,
            credits: 100000,
            banked_credits: 0,
            gemstones: 0,
            naquadah_crystals: 0.0,
            untrained_citizens: 0,
            workers: 0,
            soldiers: 0,
            guards: 0,
            spies: 0,
            sentries: 0
        );

        $mockStats = new UserStats(
            user_id: $userId,
            level: 1,
            experience: 0,
            net_worth: 0,
            war_prestige: 0,
            energy: 100,
            attack_turns: 50,
            level_up_points: 0,
            strength_points: 0,
            constitution_points: 0,
            wealth_points: 0,
            dexterity_points: 0,
            charisma_points: 0,
            deposit_charges: 0, // No charges
            last_deposit_at: null,
            battles_won: 0,
            battles_lost: 0,
            spy_successes: 0,
            spy_failures: 0
        );

        $this->mockResourceRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockResource);

        $this->mockStatsRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockStats);

        $this->mockConfig->shouldReceive('get')
            ->once()
            ->with('bank')
            ->andReturn([
                'deposit_percent_limit' => 0.8,
                'deposit_charge_regen_hours' => 24
            ]);

        $response = $this->service->deposit($userId, $amount);

        $this->assertFalse($response->isSuccess());
        $this->assertStringContainsString('no deposit charges', $response->message);
    }

    /**
     * Test: withdraw rejects zero or negative amount
     */
    public function testWithdrawRejectsInvalidAmount(): void
    {
        $response = $this->service->withdraw(1, 0);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('Amount to withdraw must be a positive number.', $response->message);
    }

    /**
     * Test: withdraw rejects when insufficient banked credits
     */
    public function testWithdrawRejectsWhenInsufficientBankedCredits(): void
    {
        $userId = 1;
        $amount = 100000;

        $mockResource = new UserResource(
            user_id: $userId,
            credits: 10000,
            banked_credits: 50000, // Less than requested
            gemstones: 0,
            naquadah_crystals: 0.0,
            untrained_citizens: 0,
            workers: 0,
            soldiers: 0,
            guards: 0,
            spies: 0,
            sentries: 0
        );

        $this->mockResourceRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockResource);

        $response = $this->service->withdraw($userId, $amount);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('You do not have enough banked credits to withdraw.', $response->message);
    }

    /**
     * Test: withdraw succeeds with valid amount
     */
    public function testWithdrawSucceedsWithValidAmount(): void
    {
        $userId = 1;
        $amount = 30000;

        $mockResource = new UserResource(
            user_id: $userId,
            credits: 10000,
            banked_credits: 50000,
            gemstones: 0,
            naquadah_crystals: 0.0,
            untrained_citizens: 0,
            workers: 0,
            soldiers: 0,
            guards: 0,
            spies: 0,
            sentries: 0
        );

        $this->mockResourceRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockResource);

        $this->mockResourceRepo->shouldReceive('updateBankingCredits')
            ->once()
            ->with($userId, 40000, 20000) // credits + amount, banked - amount
            ->andReturn(true);

        $response = $this->service->withdraw($userId, $amount);

        $this->assertTrue($response->isSuccess());
        $this->assertStringContainsString('successfully withdrew', $response->message);
    }

    /**
     * Test: transfer rejects zero or negative amount
     */
    public function testTransferRejectsInvalidAmount(): void
    {
        $response = $this->service->transfer(1, 'Recipient', 0);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('Amount to transfer must be a positive number.', $response->message);
    }

    /**
     * Test: transfer rejects empty recipient name
     */
    public function testTransferRejectsEmptyRecipient(): void
    {
        $response = $this->service->transfer(1, '', 1000);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('You must enter a recipient.', $response->message);
    }

    /**
     * Test: transfer rejects non-existent recipient
     */
    public function testTransferRejectsNonExistentRecipient(): void
    {
        $this->mockUserRepo->shouldReceive('findByCharacterName')
            ->once()
            ->with('NonExistent')
            ->andReturn(null);

        $response = $this->service->transfer(1, 'NonExistent', 1000);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals("Character 'NonExistent' not found.", $response->message);
    }

    /**
     * Test: transfer rejects self-transfer
     */
    public function testTransferRejectsSelfTransfer(): void
    {
        $user = new User(
            id: 1,
            email: 'test@test.com',
            characterName: 'TestUser',
            bio: null,
            profile_picture_url: null,
            phone_number: null,
            alliance_id: null,
            alliance_role_id: null,
            passwordHash: 'hash',
            createdAt: '2024-01-01',
            is_npc: false
        );

        $this->mockUserRepo->shouldReceive('findByCharacterName')
            ->once()
            ->with('TestUser')
            ->andReturn($user);

        $response = $this->service->transfer(1, 'TestUser', 1000);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('You cannot transfer credits to yourself.', $response->message);
    }

    /**
     * Test: transfer fails when insufficient credits
     */
    public function testTransferRejectsInsufficientCredits(): void
    {
        $senderId = 1;
        $recipientId = 2;
        $amount = 1000;

        $recipient = $this->createMockUser($recipientId, 'Recipient');
        $this->mockUserRepo->shouldReceive('findByCharacterName')->with('Recipient')->andReturn($recipient);

        // Transaction Start
        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction');
        // No commit expected on failure path

        $senderRes = $this->createMockResource($senderId, 500); // 500 < 1000
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($senderId)->andReturn($senderRes);
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($recipientId)->andReturn($this->createMockResource($recipientId, 0));

        $response = $this->service->transfer($senderId, 'Recipient', $amount);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('You do not have enough credits on hand to transfer.', $response->message);
    }

    /**
     * Test: transfer succeeds and updates both users
     */
    public function testTransferSucceedsAtomicUpdate(): void
    {
        $senderId = 1;
        $recipientId = 2;
        $amount = 1000;

        $recipient = $this->createMockUser($recipientId, 'Recipient');
        $this->mockUserRepo->shouldReceive('findByCharacterName')->with('Recipient')->andReturn($recipient);

        // Transaction
        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction')->once();
        $this->mockDb->shouldReceive('commit')->once();

        // Resources
        $senderRes = $this->createMockResource($senderId, 5000);
        $recipientRes = $this->createMockResource($recipientId, 500);

        $this->mockResourceRepo->shouldReceive('findByUserId')->with($senderId)->andReturn($senderRes);
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($recipientId)->andReturn($recipientRes);

        // Expectations
        $this->mockResourceRepo->shouldReceive('updateCredits')->once()->with($senderId, 4000); // 5000 - 1000
        $this->mockResourceRepo->shouldReceive('updateCredits')->once()->with($recipientId, 1500); // 500 + 1000

        $response = $this->service->transfer($senderId, 'Recipient', $amount);

        $this->assertTrue($response->isSuccess());
        $this->assertStringContainsString('successfully transferred', $response->message);
    }

    // --- Helpers ---

    private function createMockUser(int $id, string $name): User
    {
        return new User(
            id: $id,
            email: 'test@test.com',
            characterName: $name,
            bio: null,
            profile_picture_url: null,
            phone_number: null,
            alliance_id: null,
            alliance_role_id: null,
            passwordHash: 'hash',
            createdAt: '2024-01-01',
            is_npc: false
        );
    }

    private function createMockResource(int $userId, int $credits): UserResource
    {
        return new UserResource($userId, $credits, 0, 0, 0.0, 0, 0, 0, 0, 0, 0);
    }
}
