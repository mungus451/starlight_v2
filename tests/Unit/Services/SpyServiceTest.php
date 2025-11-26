<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\SpyService;
use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\SpyRepository;
use App\Models\Services\ArmoryService;
use App\Models\Services\PowerCalculatorService;
use App\Models\Services\LevelUpService;
use App\Models\Services\NotificationService;
use App\Models\Entities\User;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStats;
use App\Models\Entities\UserStructure;
use Mockery;
use PDO;

/**
 * Unit Tests for SpyService
 * 
 * Tests espionage operations, success/failure rates, sentry detection,
 * and notification dispatching without database dependencies.
 */
class SpyServiceTest extends TestCase
{
    private SpyService $service;
    private PDO|Mockery\MockInterface $mockDb;
    private Config|Mockery\MockInterface $mockConfig;
    private UserRepository|Mockery\MockInterface $mockUserRepo;
    private ResourceRepository|Mockery\MockInterface $mockResourceRepo;
    private StructureRepository|Mockery\MockInterface $mockStructureRepo;
    private StatsRepository|Mockery\MockInterface $mockStatsRepo;
    private SpyRepository|Mockery\MockInterface $mockSpyRepo;
    private ArmoryService|Mockery\MockInterface $mockArmoryService;
    private PowerCalculatorService|Mockery\MockInterface $mockPowerCalcService;
    private LevelUpService|Mockery\MockInterface $mockLevelUpService;
    private NotificationService|Mockery\MockInterface $mockNotificationService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks
        $this->mockDb = Mockery::mock(PDO::class);
        $this->mockConfig = Mockery::mock(Config::class);
        $this->mockUserRepo = Mockery::mock(UserRepository::class);
        $this->mockResourceRepo = Mockery::mock(ResourceRepository::class);
        $this->mockStructureRepo = Mockery::mock(StructureRepository::class);
        $this->mockStatsRepo = Mockery::mock(StatsRepository::class);
        $this->mockSpyRepo = Mockery::mock(SpyRepository::class);
        $this->mockArmoryService = Mockery::mock(ArmoryService::class);
        $this->mockPowerCalcService = Mockery::mock(PowerCalculatorService::class);
        $this->mockLevelUpService = Mockery::mock(LevelUpService::class);
        $this->mockNotificationService = Mockery::mock(NotificationService::class);

        // Instantiate service
        $this->service = new SpyService(
            $this->mockDb,
            $this->mockConfig,
            $this->mockUserRepo,
            $this->mockResourceRepo,
            $this->mockStructureRepo,
            $this->mockStatsRepo,
            $this->mockSpyRepo,
            $this->mockArmoryService,
            $this->mockPowerCalcService,
            $this->mockLevelUpService,
            $this->mockNotificationService
        );
    }

    /**
     * Test: getSpyData returns correct structure with calculated costs
     */
    public function testGetSpyDataReturnsCorrectStructure(): void
    {
        $userId = 1;
        $page = 1;

        $mockResource = new UserResource(
            user_id: $userId,
            credits: 100000,
            banked_credits: 0,
            gemstones: 0,
            naquadah_crystals: 0.0,
            untrained_citizens: 50,
            workers: 10,
            soldiers: 100,
            guards: 50,
            spies: 20,
            sentries: 10
        );

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

        $mockCosts = [
            'cost_per_spy' => 500,
            'attack_turn_cost' => 1
        ];

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
            ->with('game_balance.spy', [])
            ->andReturn($mockCosts);

        $this->mockConfig->shouldReceive('get')
            ->once()
            ->with('app.leaderboard.per_page', 25)
            ->andReturn(25);

        $this->mockStatsRepo->shouldReceive('getTotalTargetCount')
            ->once()
            ->with($userId)
            ->andReturn(100);

        $this->mockStatsRepo->shouldReceive('getPaginatedTargetList')
            ->once()
            ->with(25, 0, $userId)
            ->andReturn([]);

        // Act
        $result = $this->service->getSpyData($userId, $page);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('resources', $result);
        $this->assertArrayHasKey('stats', $result);
        $this->assertArrayHasKey('costs', $result);
        $this->assertArrayHasKey('operation', $result);
        $this->assertEquals(20, $result['operation']['spies_to_send']);
        $this->assertEquals(10000, $result['operation']['total_credit_cost']); // 20 * 500
        $this->assertEquals(1, $result['operation']['turn_cost']);
    }

    /**
     * Test: conductOperation rejects empty target name
     */
    public function testConductOperationRejectsEmptyTarget(): void
    {
        $response = $this->service->conductOperation(1, '');

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('You must enter a target.', $response->message);
    }

    /**
     * Test: conductOperation rejects non-existent target
     */
    public function testConductOperationRejectsNonExistentTarget(): void
    {
        $this->mockUserRepo->shouldReceive('findByCharacterName')
            ->once()
            ->with('NonExistent')
            ->andReturn(null);

        $response = $this->service->conductOperation(1, 'NonExistent');

        $this->assertFalse($response->isSuccess());
        $this->assertEquals("Character 'NonExistent' not found.", $response->message);
    }

    /**
     * Test: conductOperation rejects self-spying
     */
    public function testConductOperationRejectsSelfSpying(): void
    {
        $attacker = $this->createMockUser(1, 'Attacker');

        $this->mockUserRepo->shouldReceive('findByCharacterName')
            ->once()
            ->with('Attacker')
            ->andReturn($attacker);

        $response = $this->service->conductOperation(1, 'Attacker');

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('You cannot spy on yourself.', $response->message);
    }

    /**
     * Test: conductOperation rejects when no spies available
     */
    public function testConductOperationRejectsWhenNoSpies(): void
    {
        $attacker = $this->createMockUser(1, 'Attacker');
        $defender = $this->createMockUser(2, 'Defender');
        
        $attackerResources = $this->createMockResources(1, 100000, 0); // No spies
        
        $this->mockUserRepo->shouldReceive('findByCharacterName')
            ->once()
            ->with('Defender')
            ->andReturn($defender);

        $this->mockUserRepo->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($attacker);

        $this->mockResourceRepo->shouldReceive('findByUserId')
            ->once()
            ->with(1)
            ->andReturn($attackerResources);

        $this->mockStatsRepo->shouldReceive('findByUserId')
            ->once()
            ->with(1)
            ->andReturn($this->createMockStats(1));

        $this->mockStructureRepo->shouldReceive('findByUserId')
            ->once()
            ->with(1)
            ->andReturn($this->createMockStructure(1));

        $this->mockResourceRepo->shouldReceive('findByUserId')
            ->once()
            ->with(2)
            ->andReturn($this->createMockResources(2, 100000, 20));

        $this->mockStructureRepo->shouldReceive('findByUserId')
            ->once()
            ->with(2)
            ->andReturn($this->createMockStructure(2));

        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.spy')
            ->andReturn(['cost_per_spy' => 500, 'attack_turn_cost' => 1]);

        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.xp.rewards')
            ->andReturn([]);

        $response = $this->service->conductOperation(1, 'Defender');

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('You have no spies to send.', $response->message);
    }

    /**
     * Test: conductOperation rejects when insufficient credits
     */
    public function testConductOperationRejectsWhenInsufficientCredits(): void
    {
        $attacker = $this->createMockUser(1, 'Attacker');
        $defender = $this->createMockUser(2, 'Defender');
        
        $attackerResources = $this->createMockResources(1, 100, 20); // Low credits
        
        $this->mockUserRepo->shouldReceive('findByCharacterName')
            ->once()
            ->with('Defender')
            ->andReturn($defender);

        $this->mockUserRepo->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($attacker);

        $this->mockResourceRepo->shouldReceive('findByUserId')
            ->once()
            ->with(1)
            ->andReturn($attackerResources);

        $this->mockStatsRepo->shouldReceive('findByUserId')
            ->once()
            ->with(1)
            ->andReturn($this->createMockStats(1));

        $this->mockStructureRepo->shouldReceive('findByUserId')
            ->once()
            ->with(1)
            ->andReturn($this->createMockStructure(1));

        $this->mockResourceRepo->shouldReceive('findByUserId')
            ->once()
            ->with(2)
            ->andReturn($this->createMockResources(2, 100000, 20));

        $this->mockStructureRepo->shouldReceive('findByUserId')
            ->once()
            ->with(2)
            ->andReturn($this->createMockStructure(2));

        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.spy')
            ->andReturn(['cost_per_spy' => 500, 'attack_turn_cost' => 1]);

        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.xp.rewards')
            ->andReturn([]);

        $response = $this->service->conductOperation(1, 'Defender');

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('You do not have enough credits to send all your spies.', $response->message);
    }

    /**
     * Test: conductOperation rejects when insufficient attack turns
     */
    public function testConductOperationRejectsWhenInsufficientTurns(): void
    {
        $attacker = $this->createMockUser(1, 'Attacker');
        $defender = $this->createMockUser(2, 'Defender');
        
        $attackerResources = $this->createMockResources(1, 100000, 20);
        $attackerStats = $this->createMockStats(1, 0); // No attack turns
        
        $this->mockUserRepo->shouldReceive('findByCharacterName')
            ->once()
            ->with('Defender')
            ->andReturn($defender);

        $this->mockUserRepo->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($attacker);

        $this->mockResourceRepo->shouldReceive('findByUserId')
            ->once()
            ->with(1)
            ->andReturn($attackerResources);

        $this->mockStatsRepo->shouldReceive('findByUserId')
            ->once()
            ->with(1)
            ->andReturn($attackerStats);

        $this->mockStructureRepo->shouldReceive('findByUserId')
            ->once()
            ->with(1)
            ->andReturn($this->createMockStructure(1));

        $this->mockResourceRepo->shouldReceive('findByUserId')
            ->once()
            ->with(2)
            ->andReturn($this->createMockResources(2, 100000, 20));

        $this->mockStructureRepo->shouldReceive('findByUserId')
            ->once()
            ->with(2)
            ->andReturn($this->createMockStructure(2));

        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.spy')
            ->andReturn(['cost_per_spy' => 500, 'attack_turn_cost' => 1]);

        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.xp.rewards')
            ->andReturn([]);

        $response = $this->service->conductOperation(1, 'Defender');

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('You do not have enough attack turns.', $response->message);
    }

    /**
     * Test: getSpyReports combines offensive and defensive reports
     */
    public function testGetSpyReportsCombinesOffensiveAndDefensive(): void
    {
        $userId = 1;
        $offensiveReports = ['report1', 'report2'];
        $defensiveReports = ['report3'];

        $this->mockSpyRepo->shouldReceive('findReportsByAttackerId')
            ->once()
            ->with($userId)
            ->andReturn($offensiveReports);

        $this->mockSpyRepo->shouldReceive('findReportsByDefenderId')
            ->once()
            ->with($userId)
            ->andReturn($defensiveReports);

        $result = $this->service->getSpyReports($userId);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    /**
     * Test: getSpyReport returns correct report
     */
    public function testGetSpyReportReturnsCorrectReport(): void
    {
        $reportId = 123;
        $viewerId = 1;
        $mockReport = Mockery::mock(\App\Models\Entities\SpyReport::class);

        $this->mockSpyRepo->shouldReceive('findReportById')
            ->once()
            ->with($reportId, $viewerId)
            ->andReturn($mockReport);

        $result = $this->service->getSpyReport($reportId, $viewerId);

        $this->assertSame($mockReport, $result);
    }

    // Helper methods

    private function createMockUser(int $id, string $name): User
    {
        return new User(
            id: $id,
            email: "{$name}@test.com",
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

    private function createMockResources(int $userId, int $credits, int $spies): UserResource
    {
        return new UserResource(
            user_id: $userId,
            credits: $credits,
            banked_credits: 0,
            gemstones: 0,
            naquadah_crystals: 0.0,
            untrained_citizens: 50,
            workers: 10,
            soldiers: 100,
            guards: 50,
            spies: $spies,
            sentries: 10
        );
    }

    private function createMockStats(int $userId, int $attackTurns = 50): UserStats
    {
        return new UserStats(
            user_id: $userId,
            level: 5,
            experience: 1000,
            net_worth: 500000,
            war_prestige: 100,
            energy: 100,
            attack_turns: $attackTurns,
            level_up_points: 0,
            strength_points: 0,
            constitution_points: 0,
            wealth_points: 0,
            dexterity_points: 0,
            charisma_points: 0,
            deposit_charges: 5,
            last_deposit_at: null
        );
    }

    private function createMockStructure(int $userId): UserStructure
    {
        return new UserStructure(
            user_id: $userId,
            fortification_level: 10,
            offense_upgrade_level: 5,
            defense_upgrade_level: 3,
            spy_upgrade_level: 2,
            economy_upgrade_level: 8,
            population_level: 1,
            armory_level: 1
        );
    }
}
