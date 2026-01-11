<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\SpyService;
use App\Models\Services\AttackService;
use App\Core\Config;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\SpyRepository;
use App\Models\Repositories\BattleRepository;
use App\Models\Repositories\WarRepository;
use App\Models\Repositories\WarSpyLogRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceBankLogRepository;
use App\Models\Repositories\BountyRepository;
use App\Models\Repositories\WarBattleLogRepository;
use App\Models\Services\ArmoryService;
use App\Models\Services\PowerCalculatorService;
use App\Models\Services\LevelUpService;
use App\Models\Services\NotificationService;
use App\Models\Services\EffectService;
use App\Models\Services\NetWorthCalculatorService;
use App\Core\Events\EventDispatcher;
use App\Models\Entities\User;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStats;
use App\Models\Entities\UserStructure;
use Mockery;
use PDO;

class WorkerCasualtyTest extends TestCase
{
    private SpyService $spyService;
    private AttackService $attackService;
    
    // Mocks
    private $mockDb;
    private $mockConfig;
    private $mockUserRepo;
    private $mockResourceRepo;
    private $mockStructureRepo;
    private $mockStatsRepo;
    private $mockSpyRepo;
    private $mockBattleRepo;
    private $mockArmoryService;
    private $mockPowerCalcService;
    private $mockLevelUpService;
    private $mockNotificationService;
    private $mockEffectService;
    private $mockWarRepo;
    private $mockWarSpyLogRepo;
    private $mockAllianceRepo;
    private $mockBankLogRepo;
    private $mockBountyRepo;
    private $mockWarBattleLogRepo;
    private $mockEventDispatcher;
    private $mockNwCalculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockDb = Mockery::mock(PDO::class);
        $this->mockConfig = Mockery::mock(Config::class);
        $this->mockUserRepo = Mockery::mock(UserRepository::class);
        $this->mockResourceRepo = Mockery::mock(ResourceRepository::class);
        $this->mockStructureRepo = Mockery::mock(StructureRepository::class);
        $this->mockStatsRepo = Mockery::mock(StatsRepository::class);
        $this->mockSpyRepo = Mockery::mock(SpyRepository::class);
        $this->mockBattleRepo = Mockery::mock(BattleRepository::class);
        $this->mockArmoryService = Mockery::mock(ArmoryService::class);
        $this->mockPowerCalcService = Mockery::mock(PowerCalculatorService::class);
        $this->mockLevelUpService = Mockery::mock(LevelUpService::class);
        $this->mockNotificationService = Mockery::mock(NotificationService::class);
        $this->mockEffectService = Mockery::mock(EffectService::class);
        $this->mockWarRepo = Mockery::mock(WarRepository::class);
        $this->mockWarSpyLogRepo = Mockery::mock(WarSpyLogRepository::class);
        $this->mockAllianceRepo = Mockery::mock(AllianceRepository::class);
        $this->mockBankLogRepo = Mockery::mock(AllianceBankLogRepository::class);
        $this->mockBountyRepo = Mockery::mock(BountyRepository::class);
        $this->mockWarBattleLogRepo = Mockery::mock(WarBattleLogRepository::class);
        $this->mockEventDispatcher = Mockery::mock(EventDispatcher::class);
        $this->mockNwCalculator = Mockery::mock(NetWorthCalculatorService::class);

        $this->mockEffectService->shouldReceive('hasActiveEffect')->andReturn(false)->byDefault();
        $this->mockEffectService->shouldReceive('getEffectDetails')->andReturn(null)->byDefault();

        // Spy Service
        $this->spyService = new SpyService(
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
            $this->mockNotificationService,
            $this->mockEffectService,
            $this->mockWarRepo,
            $this->mockWarSpyLogRepo
        );

        // Attack Service
        $this->attackService = new AttackService(
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
            $this->mockWarRepo,
            $this->mockWarBattleLogRepo,
            $this->mockArmoryService,
            $this->mockPowerCalcService,
            $this->mockLevelUpService,
            $this->mockEventDispatcher,
            $this->mockEffectService,
            $this->mockNwCalculator
        );
    }

    public function testSpyMissionCausesWorkerCasualties(): void
    {
        $attackerId = 1;
        $defenderId = 2;
        $defenderName = 'Defender';

        $defender = $this->createMockUser($defenderId, $defenderName);
        $this->mockUserRepo->shouldReceive('findByCharacterName')->with($defenderName)->andReturn($defender);
        $this->mockUserRepo->shouldReceive('findById')->with($attackerId)->andReturn($this->createMockUser($attackerId, 'Attacker'));

        // Defender has 1000 workers
        $defenderResources = $this->createMockResources($defenderId, 1000, 0, 0, 1000);
        
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($attackerId)->andReturn($this->createMockResources($attackerId, 10000, 10));
        $this->mockStatsRepo->shouldReceive('findByUserId')->with($attackerId)->andReturn($this->createMockStats($attackerId));
        $this->mockStructureRepo->shouldReceive('findByUserId')->andReturn($this->createMockStructure($attackerId));
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($defenderId)->andReturn($defenderResources);

        // Spy Success (100% chance), Worker casualty rate 1%
        $this->mockConfig->shouldReceive('get')->with('game_balance.spy')->andReturn([
            'cost_per_spy' => 0, 
            'attack_turn_cost' => 1,
            'base_success_multiplier' => 100.0,
            'base_success_chance_floor' => 0.0,
            'base_success_chance_cap' => 1.0,
            'base_counter_spy_multiplier' => 0.0,
            'base_counter_spy_chance_cap' => 0.0,
            'worker_casualty_rate' => 0.01 // 1%
        ]);
        $this->mockConfig->shouldReceive('get')->with('game_balance.xp.rewards')->andReturn(['spy_success' => 100]);

        $this->mockPowerCalcService->shouldReceive('calculateSpyPower')->andReturn(['total' => 1000]);
        $this->mockPowerCalcService->shouldReceive('calculateSentryPower')->andReturn(['total' => 1]);

        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction');
        $this->mockDb->shouldReceive('commit');

        $this->mockResourceRepo->shouldReceive('updateSpyAttacker')->once();
        $this->mockStatsRepo->shouldReceive('updateAttackTurns')->once();
        $this->mockStatsRepo->shouldReceive('incrementSpyStats')->once();
        $this->mockLevelUpService->shouldReceive('grantExperience')->once();

        // EXPECTATION: updateSpyDefender called with worker reduction
        // 1% of 1000 = 10 workers killed.
        // New workers = 990.
        $this->mockResourceRepo->shouldReceive('updateSpyDefender')
            ->once()
            ->with($defenderId, Mockery::any(), 990);

        // Expect notification for collateral damage
        $this->mockNotificationService->shouldReceive('sendNotification')
            ->once()
            ->with($defenderId, 'spy', "Security Alert: Sabotage Detected", Mockery::pattern('/10 workers were assassinated/'), Mockery::any());

        $this->mockSpyRepo->shouldReceive('createReport')->once()->andReturn(99);

        $response = $this->spyService->conductOperation($attackerId, $defenderName);

        $this->assertTrue($response->isSuccess());
        $this->assertStringContainsString('Targets eliminated: 10 workers', $response->message);
    }

    public function testBattleCausesWorkerCasualtiesScaledByIntensity(): void
    {
        $attackerId = 1;
        $defenderId = 2;
        $targetName = 'Defender';

        $defender = $this->createMockUser($defenderId, $targetName);
        $this->mockUserRepo->shouldReceive('findByCharacterName')->with($targetName)->andReturn($defender);
        $this->mockUserRepo->shouldReceive('findById')->with($attackerId)->andReturn($this->createMockUser($attackerId, 'Attacker'));

        // Defender: 1000 Guards, 5000 Workers
        $defenderResources = $this->createMockResources($defenderId, 10000, 0, 1000, 5000);

        $attackerResources = $this->createMockResources($attackerId, 10000, 0, 0, 0, 2000);

        $this->mockResourceRepo->shouldReceive('findByUserId')->with($attackerId)->andReturn($attackerResources);
        $this->mockStatsRepo->shouldReceive('findByUserId')->with($attackerId)->andReturn($this->createMockStats($attackerId));
        $this->mockStructureRepo->shouldReceive('findByUserId')->andReturn($this->createMockStructure($attackerId));
        
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($defenderId)->andReturn($defenderResources);
        $this->mockStatsRepo->shouldReceive('findByUserId')->with($defenderId)->andReturn($this->createMockStats($defenderId));
        $this->mockStructureRepo->shouldReceive('findByUserId')->with($defenderId)->andReturn($this->createMockStructure($defenderId));

        $this->mockConfig->shouldReceive('get')->with('game_balance.attack')->andReturn([
            'attack_turn_cost' => 1,
            'plunder_percent' => 0.1,
            'global_casualty_scalar' => 1.0,
            'worker_casualty_rate_base' => 0.02, // 2%
            'worker_casualty_damage_scalar' => 0.05, // 5% per 100% guard loss
            'war_prestige_gain_base' => 10
        ]);
        $this->mockConfig->shouldReceive('get')->with('game_balance.alliance_treasury')->andReturn(['battle_tax_rate' => 0, 'tribute_tax_rate' => 0]);
        $this->mockConfig->shouldReceive('get')->with('game_balance.xp.rewards')->andReturn([
            'battle_win' => 100, 'battle_loss' => 50, 'battle_stalemate' => 20,
            'battle_defense_win' => 80, 'battle_defense_loss' => 30
        ]);

        // Mock Power - Attacker Wins Overwhelmingly (High Casualties for Defender)
        $this->mockPowerCalcService->shouldReceive('calculateOffensePower')->andReturn(['total' => 20000]);
        $this->mockPowerCalcService->shouldReceive('calculateDefensePower')->andReturn(['total' => 5000]); // Ratio 4:1
        $this->mockPowerCalcService->shouldReceive('calculateShieldPower')->andReturn(['total_shield_hp' => 0]);

        // Transaction Mocks
        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction');
        $this->mockDb->shouldReceive('commit');

        // Logic check:
        // Winner losses: 0.05 / 4 = 1.25% of 2000 = 25 soldiers.
        // Loser losses: 0.10 * 4 = 40% of 1000 = 400 guards.
        
        // Expected Worker Loss:
        // Guard Loss % = 400 / 1000 = 0.40 (40%)
        // Total % = Base (0.02) + (0.40 * Scalar 0.05)
        // Total % = 0.02 + 0.02 = 0.04 (4%)
        // Workers Lost = 4% of 5000 = 200.
        
        $this->mockResourceRepo->shouldReceive('updateBattleAttacker')->once();
        
        // EXPECTATION: Defender loses guards AND workers
        $this->mockResourceRepo->shouldReceive('updateBattleDefender')
            ->once()
            ->with($defenderId, Mockery::any(), Mockery::any(), Mockery::on(function($val) {
                return $val < 5000; // Confirm reduction happened
            }));
            
        // Wait, calculateLoserLosses uses mt_rand. I cannot predict exact numbers.
        // I need to mock the loss calculation or accept a range.
        // Or simply checking that `updateBattleDefender` is called with 4 args is enough to prove the interface change worked,
        // and checking one arg is < 5000 proves reduction happened.
        
        $this->mockStatsRepo->shouldReceive('updateBattleAttackerStats')->once();
        $this->mockStatsRepo->shouldReceive('incrementBattleStats')->once();
        $this->mockLevelUpService->shouldReceive('grantExperience')->twice();
        $this->mockNwCalculator->shouldReceive('calculateTotalNetWorth')->twice();
        $this->mockStatsRepo->shouldReceive('updateBattleDefenderStats')->once();
        $this->mockBountyRepo->shouldReceive('findActiveByTargetId')->andReturn(null);
        
        $this->mockBattleRepo->shouldReceive('createReport')
            ->once()
            ->with(
                Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(),
                Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(),
                Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(),
                Mockery::capture($actualWorkerLoss) // Capture the worker loss
            )
            ->andReturn(1);

        $this->mockEventDispatcher->shouldReceive('dispatch')->once();

        $response = $this->attackService->conductAttack($attackerId, $targetName, 'plunder');

        $this->assertTrue($response->isSuccess());
        $this->assertGreaterThan(0, $actualWorkerLoss); // Ensure SOME workers were lost
    }

    // --- Helpers ---

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

    private function createMockResources(int $userId, int $credits, int $spies, int $sentries = 0, int $workers = 10, int $soldiers = 10): UserResource
    {
        return new UserResource($userId, $credits, 0, 0, 0.0, 50, $workers, $soldiers, 10, 50, $spies, $sentries);
    }

    private function createMockStats(int $userId): UserStats
    {
        return new UserStats($userId, 5, 1000, 500000, 100, 100, 50, 0, 0, 0, 0, 0, 0, 5, null, 0, 0, 0, 0);
    }

    private function createMockStructure(int $userId): UserStructure
    {
        return new UserStructure($userId, 10, 5, 3, 2, 8, 1, 1, 0);
    }
}
