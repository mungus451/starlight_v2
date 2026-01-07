<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\LevelUpService;
use App\Models\Services\LevelCalculatorService;
use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\StatsRepository;
use App\Models\Entities\UserStats;
use Mockery;
use PDO;

class LevelUpServiceTest extends TestCase
{
    private LevelUpService $service;
    private PDO|Mockery\MockInterface $mockDb;
    private Config|Mockery\MockInterface $mockConfig;
    private StatsRepository|Mockery\MockInterface $mockStatsRepo;
    private LevelCalculatorService|Mockery\MockInterface $mockLevelCalculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockDb = Mockery::mock(PDO::class);
        $this->mockConfig = Mockery::mock(Config::class);
        $this->mockStatsRepo = Mockery::mock(StatsRepository::class);
        $this->mockLevelCalculator = Mockery::mock(LevelCalculatorService::class);

        $this->service = new LevelUpService(
            $this->mockDb,
            $this->mockConfig,
            $this->mockStatsRepo,
            $this->mockLevelCalculator
        );
    }

    public function testGrantExperienceHandlesLevelUp(): void
    {
        $userId = 1;
        $xpGain = 500;
        
        $currentStats = $this->createMockStats($userId, currentXp: 1000, currentLevel: 5, sp: 2);
        
        $this->mockStatsRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($currentStats);

        // Level Calculator Logic
        $this->mockLevelCalculator->shouldReceive('calculateLevelFromXp')
            ->once()
            ->with(1500) // 1000 + 500
            ->andReturn(6); // Leveled up!

        // Expect Update
        $this->mockStatsRepo->shouldReceive('updateLevelProgress')
            ->once()
            ->with($userId, 1500, 6, 3) // 2 existing SP + 1 gained
            ->andReturn(true);

        $response = $this->service->grantExperience($userId, $xpGain);

        $this->assertTrue($response->isSuccess());
        $this->assertStringContainsString('Level Up!', $response->message);
        $this->assertEquals(6, $response->data['new_level']);
    }

    public function testGrantExperienceHandlesNoLevelUp(): void
    {
        $userId = 1;
        $xpGain = 50;
        
        $currentStats = $this->createMockStats($userId, currentXp: 1000, currentLevel: 5, sp: 2);
        
        $this->mockStatsRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($currentStats);

        $this->mockLevelCalculator->shouldReceive('calculateLevelFromXp')
            ->once()
            ->with(1050)
            ->andReturn(5); // Still level 5

        $this->mockStatsRepo->shouldReceive('updateLevelProgress')
            ->once()
            ->with($userId, 1050, 5, 2) // No SP change
            ->andReturn(true);

        $response = $this->service->grantExperience($userId, $xpGain);

        $this->assertTrue($response->isSuccess());
        $this->assertStringNotContainsString('Level Up!', $response->message);
    }

    public function testSpendPointsUpdatesStatsAndDeductsCost(): void
    {
        $userId = 1;
        
        $currentStats = $this->createMockStats($userId, sp: 10, strength: 5);
        
        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.level_up.cost_per_point', 1)
            ->andReturn(1); // Cost 1 per point

        $this->mockStatsRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($currentStats);

        // Transaction
        $this->mockDb->shouldReceive('beginTransaction')->once();
        $this->mockDb->shouldReceive('commit')->once();

        // Update Expectation
        // Spending 5 Strength points.
        // New SP = 10 - 5 = 5.
        // New Strength = 5 + 5 = 10.
        $this->mockStatsRepo->shouldReceive('updateBaseStats')
            ->once()
            ->with($userId, 5, 10, 0, 0, 0, 0)
            ->andReturn(true);

        $response = $this->service->spendPoints($userId, 5, 0, 0, 0, 0);

        $this->assertTrue($response->isSuccess());
    }

    public function testSpendPointsRejectsInsufficientPoints(): void
    {
        $userId = 1;
        
        $currentStats = $this->createMockStats($userId, sp: 2); // Only 2 points
        
        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.level_up.cost_per_point', 1)
            ->andReturn(1);

        $this->mockStatsRepo->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($currentStats);

        // Try to spend 5 points
        $response = $this->service->spendPoints($userId, 5, 0, 0, 0, 0);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('You do not have enough level up points to make this change.', $response->message);
    }

    public function testSpendPointsRejectsNegativeValues(): void
    {
        $response = $this->service->spendPoints(1, -5, 0, 0, 0, 0);
        
        $this->assertFalse($response->isSuccess());
        $this->assertEquals('You cannot spend a negative number of points.', $response->message);
    }

    // --- Helpers ---

    private function createMockStats(int $userId, int $currentXp = 0, int $currentLevel = 1, int $sp = 0, int $strength = 0): UserStats
    {
        return new UserStats(
            user_id: $userId,
            level: $currentLevel,
            experience: $currentXp,
            net_worth: 0,
            war_prestige: 0,
            energy: 10,
            attack_turns: 10,
            level_up_points: $sp,
            strength_points: $strength,
            constitution_points: 0,
            wealth_points: 0,
            dexterity_points: 0,
            charisma_points: 0,
            deposit_charges: 0,
            last_deposit_at: null
        );
    }
}
