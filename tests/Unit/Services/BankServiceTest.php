<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\BankService;
use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Entities\User;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStats;
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

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks
        $this->mockDb = Mockery::mock(PDO::class);
        $this->mockConfig = Mockery::mock(Config::class);
        $this->mockResourceRepo = Mockery::mock(ResourceRepository::class);
        $this->mockUserRepo = Mockery::mock(UserRepository::class);
        $this->mockStatsRepo = Mockery::mock(StatsRepository::class);

        // Instantiate service
        $this->service = new BankService(
            $this->mockDb,
            $this->mockConfig,
            $this->mockResourceRepo,
            $this->mockUserRepo,
            $this->mockStatsRepo
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
            last_deposit_at: null
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

        $bankConfig = [
            'deposit_max_charges' => 5,
            'deposit_charge_regen_hours' => 24
        ];

        $this->mockStatsRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockStats);

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
            last_deposit_at: null
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
            last_deposit_at: null
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
}
