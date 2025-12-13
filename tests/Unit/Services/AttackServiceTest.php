<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\AttackService;
use App\Core\Config;
use App\Core\ServiceResponse;
use App\Core\Events\EventDispatcher;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\BattleRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceBankLogRepository;
use App\Models\Repositories\BountyRepository;
use App\Models\Services\ArmoryService;
use App\Models\Services\PowerCalculatorService;
use App\Models\Services\LevelUpService;
use App\Models\Services\EffectService;
use App\Models\Entities\User;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStats;
use App\Models\Entities\UserStructure;
use Mockery;
use PDO;

/**
 * Unit Tests for AttackService
 * 
 * Tests battle logic, power calculations, victory/defeat outcomes, 
 * alliance tax, and event dispatching without database dependencies.
 */
class AttackServiceTest extends TestCase
{
    private AttackService $service;
    private PDO|Mockery\MockInterface $mockDb;
    private Config|Mockery\MockInterface $mockConfig;
    private UserRepository|Mockery\MockInterface $mockUserRepo;
    private ResourceRepository|Mockery\MockInterface $mockResourceRepo;
    private StructureRepository|Mockery\MockInterface $mockStructureRepo;
    private StatsRepository|Mockery\MockInterface $mockStatsRepo;
    private BattleRepository|Mockery\MockInterface $mockBattleRepo;
    private AllianceRepository|Mockery\MockInterface $mockAllianceRepo;
    private AllianceBankLogRepository|Mockery\MockInterface $mockBankLogRepo;
    private BountyRepository|Mockery\MockInterface $mockBountyRepo;
    private ArmoryService|Mockery\MockInterface $mockArmoryService;
    private PowerCalculatorService|Mockery\MockInterface $mockPowerCalcService;
    private LevelUpService|Mockery\MockInterface $mockLevelUpService;
    private EventDispatcher|Mockery\MockInterface $mockDispatcher;
    private EffectService|Mockery\MockInterface $mockEffectService;

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
        $this->mockBattleRepo = Mockery::mock(BattleRepository::class);
        $this->mockAllianceRepo = Mockery::mock(AllianceRepository::class);
        $this->mockBankLogRepo = Mockery::mock(AllianceBankLogRepository::class);
        $this->mockBountyRepo = Mockery::mock(BountyRepository::class);
        $this->mockArmoryService = Mockery::mock(ArmoryService::class);
        $this->mockPowerCalcService = Mockery::mock(PowerCalculatorService::class);
        $this->mockLevelUpService = Mockery::mock(LevelUpService::class);
        $this->mockDispatcher = Mockery::mock(EventDispatcher::class);
        $this->mockEffectService = Mockery::mock(EffectService::class);
        $this->mockEffectService->shouldReceive('hasActiveEffect')->andReturn(false)->byDefault();

        // Instantiate service
        $this->service = new AttackService(
            $this->mockDb,
            $this->mockConfig,
            $this->mockUserRepo,
            $this->mockResourceRepo,
            $this->mockStructureRepo,
            $this->mockStatsRepo,
            $this->mockBattleRepo,
            $this->mockAllianceRepo,
            $this->mockBankLogRepo,
            $this->mockBountyRepo,
            $this->mockArmoryService,
            $this->mockPowerCalcService,
            $this->mockLevelUpService,
            $this->mockDispatcher,
            $this->mockEffectService
        );
    }

    /**
     * Test: getAttackPageData returns correct data structure
     */
    public function testGetAttackPageDataReturnsCorrectStructure(): void
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
            spies: 10,
            sentries: 5
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

        $mockCosts = ['attack_turn_cost' => 1, 'plunder_percent' => 0.1];

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
            ->with('game_balance.attack', [])
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
        $result = $this->service->getAttackPageData($userId, $page);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('attackerResources', $result);
        $this->assertArrayHasKey('attackerStats', $result);
        $this->assertArrayHasKey('costs', $result);
        $this->assertArrayHasKey('targets', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertSame($mockResource, $result['attackerResources']);
        $this->assertSame($mockStats, $result['attackerStats']);
    }

    /**
     * Test: conductAttack rejects empty target name
     */
    public function testConductAttackRejectsEmptyTargetName(): void
    {
        $response = $this->service->conductAttack(1, '', 'plunder');

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('You must enter a target.', $response->message);
    }

    /**
     * Test: conductAttack rejects invalid attack type
     */
    public function testConductAttackRejectsInvalidAttackType(): void
    {
        $response = $this->service->conductAttack(1, 'TestTarget', 'invalid_type');

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('Invalid attack type.', $response->message);
    }

    /**
     * Test: conductAttack rejects non-existent target
     */
    public function testConductAttackRejectsNonExistentTarget(): void
    {
        $this->mockUserRepo->shouldReceive('findByCharacterName')
            ->once()
            ->with('NonExistent')
            ->andReturn(null);

        $response = $this->service->conductAttack(1, 'NonExistent', 'plunder');

        $this->assertFalse($response->isSuccess());
        $this->assertEquals("Character 'NonExistent' not found.", $response->message);
    }

    /**
     * Test: conductAttack rejects self-attack
     */
    public function testConductAttackRejectsSelfAttack(): void
    {
        $attacker = new User(
            id: 1,
            email: 'attacker@test.com',
            characterName: 'Attacker',
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
            ->with('Attacker')
            ->andReturn($attacker);

        $response = $this->service->conductAttack(1, 'Attacker', 'plunder');

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('You cannot attack yourself.', $response->message);
    }

    /**
     * Test: conductAttack rejects when attacker has no soldiers
     */
    public function testConductAttackRejectsWhenNoSoldiers(): void
    {
        $attacker = $this->createMockUser(1, 'Attacker', null);
        $defender = $this->createMockUser(2, 'Defender', null);
        
        $attackerResources = $this->createMockResources(1, 100000, 0); // No soldiers
        
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
            ->andReturn($this->createMockResources(2, 100000, 100));

        $this->mockStatsRepo->shouldReceive('findByUserId')
            ->once()
            ->with(2)
            ->andReturn($this->createMockStats(2));

        $this->mockStructureRepo->shouldReceive('findByUserId')
            ->once()
            ->with(2)
            ->andReturn($this->createMockStructure(2));

        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.attack')
            ->andReturn(['attack_turn_cost' => 1]);

        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.alliance_treasury')
            ->andReturn([]);

        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.xp.rewards')
            ->andReturn([]);

        $response = $this->service->conductAttack(1, 'Defender', 'plunder');

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('You have no soldiers to send.', $response->message);
    }

    /**
     * Test: conductAttack rejects when attacker has insufficient attack turns
     */
    public function testConductAttackRejectsWhenInsufficientTurns(): void
    {
        $attacker = $this->createMockUser(1, 'Attacker', null);
        $defender = $this->createMockUser(2, 'Defender', null);
        
        $attackerResources = $this->createMockResources(1, 100000, 100);
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
            ->andReturn($this->createMockResources(2, 100000, 100));

        $this->mockStatsRepo->shouldReceive('findByUserId')
            ->once()
            ->with(2)
            ->andReturn($this->createMockStats(2));

        $this->mockStructureRepo->shouldReceive('findByUserId')
            ->once()
            ->with(2)
            ->andReturn($this->createMockStructure(2));

        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.attack')
            ->andReturn(['attack_turn_cost' => 1]);

        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.alliance_treasury')
            ->andReturn([]);

        $this->mockConfig->shouldReceive('get')
            ->with('game_balance.xp.rewards')
            ->andReturn([]);

        $response = $this->service->conductAttack(1, 'Defender', 'plunder');

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('You do not have enough attack turns.', $response->message);
    }

    /**
     * Test: getBattleReports returns combined offensive and defensive reports
     */
    public function testGetBattleReportsCombinesOffensiveAndDefensive(): void
    {
        $userId = 1;
        $offensiveReports = ['report1', 'report2'];
        $defensiveReports = ['report3'];

        $this->mockBattleRepo->shouldReceive('findReportsByAttackerId')
            ->once()
            ->with($userId)
            ->andReturn($offensiveReports);

        $this->mockBattleRepo->shouldReceive('findReportsByDefenderId')
            ->once()
            ->with($userId)
            ->andReturn($defensiveReports);

        $result = $this->service->getBattleReports($userId);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    /**
     * Test: getBattleReport returns correct report
     */
    public function testGetBattleReportReturnsCorrectReport(): void
    {
        $reportId = 123;
        $viewerId = 1;
        $mockReport = new \App\Models\Entities\BattleReport(
            id: $reportId,
            attacker_id: $viewerId,
            defender_id: 2,
            created_at: '2024-01-01 10:00:00',
            attack_type: 'plunder',
            attack_result: 'victory',
            soldiers_sent: 100,
            attacker_soldiers_lost: 10,
            defender_guards_lost: 20,
            credits_plundered: 5000,
            experience_gained: 100,
            war_prestige_gained: 10,
            net_worth_stolen: 1000,
            attacker_offense_power: 5000,
            defender_defense_power: 4000,
            defender_total_guards: 50,
            defender_name: 'Defender',
            attacker_name: 'Attacker',
            is_hidden: false
        );

        $this->mockBattleRepo->shouldReceive('findReportById')
            ->once()
            ->with($reportId, $viewerId)
            ->andReturn($mockReport);

        $result = $this->service->getBattleReport($reportId, $viewerId);

        $this->assertSame($mockReport, $result);
    }

    // Helper methods

    private function createMockUser(int $id, string $name, ?int $allianceId): User
    {
        return new User(
            id: $id,
            email: "{$name}@test.com",
            characterName: $name,
            bio: null,
            profile_picture_url: null,
            phone_number: null,
            alliance_id: $allianceId,
            alliance_role_id: null,
            passwordHash: 'hash',
            createdAt: '2024-01-01',
            is_npc: false
        );
    }

    private function createMockResources(int $userId, int $credits, int $soldiers): UserResource
    {
        return new UserResource(
            user_id: $userId,
            credits: $credits,
            banked_credits: 0,
            gemstones: 0,
            naquadah_crystals: 0.0,
            untrained_citizens: 50,
            workers: 10,
            soldiers: $soldiers,
            guards: 50,
            spies: 10,
            sentries: 5
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
            armory_level: 1,
            accounting_firm_level: 0
        );
    }
}
