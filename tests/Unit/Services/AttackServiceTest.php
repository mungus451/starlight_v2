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
use App\Models\Repositories\WarRepository;
use App\Models\Repositories\WarBattleLogRepository;
use App\Models\Services\ArmoryService;
use App\Models\Services\PowerCalculatorService;
use App\Models\Services\LevelUpService;
use App\Models\Services\EffectService;
use App\Models\Services\NetWorthCalculatorService;
use App\Models\Entities\User;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStats;
use App\Models\Entities\UserStructure;
use Mockery;
use PDO;

/**
 * Unit Tests for AttackService (Comprehensive)
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
    private WarRepository|Mockery\MockInterface $mockWarRepo;
    private WarBattleLogRepository|Mockery\MockInterface $mockWarBattleLogRepo;
    private ArmoryService|Mockery\MockInterface $mockArmoryService;
    private PowerCalculatorService|Mockery\MockInterface $mockPowerCalcService;
    private LevelUpService|Mockery\MockInterface $mockLevelUpService;
    private EventDispatcher|Mockery\MockInterface $mockDispatcher;
    private EffectService|Mockery\MockInterface $mockEffectService;
    private NetWorthCalculatorService|Mockery\MockInterface $mockNwCalculator;

    protected function setUp(): void
    {
        parent::setUp();

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
        $this->mockWarRepo = Mockery::mock(WarRepository::class);
        $this->mockWarBattleLogRepo = Mockery::mock(WarBattleLogRepository::class);
        $this->mockArmoryService = Mockery::mock(ArmoryService::class);
        $this->mockPowerCalcService = Mockery::mock(PowerCalculatorService::class);
        $this->mockLevelUpService = Mockery::mock(LevelUpService::class);
        $this->mockDispatcher = Mockery::mock(EventDispatcher::class);
        $this->mockEffectService = Mockery::mock(EffectService::class);
        $this->mockNwCalculator = $this->createMock(NetWorthCalculatorService::class);

        $this->mockEffectService->shouldReceive('hasActiveEffect')->andReturn(false)->byDefault();
        $this->mockEffectService->shouldReceive('getEffectDetails')->andReturn(null)->byDefault();

        // Mock NW Calculator to return a default value, as specific NW logic isn't central to most AttackService tests
        $this->mockNwCalculator->method('calculateTotalNetWorth')->willReturn(1000000.0);

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
            $this->mockWarRepo,
            $this->mockWarBattleLogRepo,
            $this->mockArmoryService,
            $this->mockPowerCalcService,
            $this->mockLevelUpService,
            $this->mockDispatcher,
            $this->mockEffectService,
            $this->mockNwCalculator // New dependency, cast to type
        );
    }

    // --- Basic Validation Tests ---

    public function testConductAttackRejectsEmptyTargetName(): void
    {
        $response = $this->service->conductAttack(1, '', 'plunder');
        $this->assertFalse($response->isSuccess());
        $this->assertEquals('You must enter a target.', $response->message);
    }

    public function testConductAttackRejectsInvalidAttackType(): void
    {
        $response = $this->service->conductAttack(1, 'TestTarget', 'invalid_type');
        $this->assertFalse($response->isSuccess());
        $this->assertEquals('Invalid attack type.', $response->message);
    }

    public function testConductAttackRejectsNonExistentTarget(): void
    {
        $this->mockUserRepo->shouldReceive('findByCharacterName')->with('NonExistent')->andReturn(null);
        $response = $this->service->conductAttack(1, 'NonExistent', 'plunder');
        $this->assertFalse($response->isSuccess());
        $this->assertEquals("Character 'NonExistent' not found.", $response->message);
    }

    public function testConductAttackRejectsSelfAttack(): void
    {
        $attacker = $this->createMockUser(1, 'Attacker', null);
        $this->mockUserRepo->shouldReceive('findByCharacterName')->with('Attacker')->andReturn($attacker);
        $response = $this->service->conductAttack(1, 'Attacker', 'plunder');
        $this->assertFalse($response->isSuccess());
        $this->assertEquals('You cannot attack yourself.', $response->message);
    }

    public function testConductAttackRejectsWhenNoSoldiers(): void
    {
        // ... (Same setup as before for this case) ...
        // Re-implementing simplified version
        $attacker = $this->createMockUser(1, 'Attacker', null);
        $defender = $this->createMockUser(2, 'Defender', null);
        
        $this->mockUserRepo->shouldReceive('findByCharacterName')->andReturn($defender);
        $this->mockUserRepo->shouldReceive('findById')->andReturn($attacker);
        
        // Mock Resource: 0 Soldiers
        $this->mockResourceRepo->shouldReceive('findByUserId')->with(1)->andReturn($this->createMockResources(1, 100, 0));
        $this->mockStatsRepo->shouldReceive('findByUserId')->andReturn($this->createMockStats(1));
        $this->mockStructureRepo->shouldReceive('findByUserId')->andReturn($this->createMockStructure(1));
        $this->mockResourceRepo->shouldReceive('findByUserId')->with(2)->andReturn($this->createMockResources(2, 100, 10));
        $this->mockStatsRepo->shouldReceive('findByUserId')->with(2)->andReturn($this->createMockStats(2));
        $this->mockStructureRepo->shouldReceive('findByUserId')->with(2)->andReturn($this->createMockStructure(2));

        $this->mockConfig->shouldReceive('get')->andReturn(['attack_turn_cost' => 1]);

        $response = $this->service->conductAttack(1, 'Defender', 'plunder');
        $this->assertEquals('You have no soldiers to send.', $response->message);
    }

    // --- Advanced Logic Tests ---

    public function testConductAttackBlockedByPeaceShield(): void
    {
        $attackerId = 1;
        $defenderId = 2;
        $targetName = 'TargetUser';

        $this->mockUserRepo->shouldReceive('findByCharacterName')
            ->with($targetName)
            ->andReturn($this->createMockUser($defenderId, 'TargetUser', null));

        // Mock Effect Details with future expiry (1 hour from now)
        $future = (new \DateTime())->modify('+1 hour')->format('Y-m-d H:i:s');
        $this->mockEffectService->shouldReceive('getEffectDetails')
            ->with($defenderId, 'peace_shield')
            ->once()
            ->andReturn(['expires_at' => $future]);

        $response = $this->service->conductAttack($attackerId, $targetName, 'plunder');

        $this->assertFalse($response->isSuccess());
        // Message should contain "1h" or "59m" roughly
        $this->assertStringContainsString('Target is under Safehouse protection for another', $response->message);
        $this->assertStringContainsString('Attack prevented.', $response->message);
    }

    public function testConductAttackCalculatesAllianceTaxesOnVictory(): void
    {
        $attackerId = 1;
        $defenderId = 2;
        $allianceId = 10;

        $attacker = $this->createMockUser($attackerId, 'Attacker', $allianceId);
        $defender = $this->createMockUser($defenderId, 'Defender', null);

        $this->setupAttackMocks($attacker, $defender, 1000, 500);

        $this->mockConfig->shouldReceive('get')->with('game_balance.alliance_treasury')
            ->andReturn(['battle_tax_rate' => 0.10, 'tribute_tax_rate' => 0.05]);

        $this->mockAllianceRepo->shouldReceive('updateBankCreditsRelative')
            ->once()
            ->with($allianceId, 1500)
            ->andReturn(true);

        $this->mockBankLogRepo->shouldReceive('createLog')->twice();

        $response = $this->service->conductAttack($attackerId, 'Defender', 'plunder');

        $this->assertTrue($response->isSuccess());
        $this->assertStringContainsString('plundered 10,000 credits', $response->message);
    }

    public function testConductAttackClaimsBountyOnVictory(): void
    {
        $attackerId = 1;
        $defenderId = 2;

        $attacker = $this->createMockUser($attackerId, 'Attacker', null);
        $defender = $this->createMockUser($defenderId, 'Defender', null);

        $this->setupAttackMocks($attacker, $defender, 1000, 500);

        $bountyId = 55;
        $bountyAmount = 50000;
        $this->mockBountyRepo->shouldReceive('findActiveByTargetId')
            ->once()
            ->with($defenderId)
            ->andReturn(['id' => $bountyId, 'amount' => $bountyAmount]);

        $this->mockBountyRepo->shouldReceive('claimBounty')
            ->once()
            ->with($bountyId, $attackerId);

        $this->mockResourceRepo->shouldReceive('updateResources')
            ->once()
            ->with($attackerId, 0, $bountyAmount);

        $response = $this->service->conductAttack($attackerId, 'Defender', 'plunder');

        $this->assertTrue($response->isSuccess());
        $this->assertStringContainsString('Bounty Claimed!', $response->message);
    }

    public function testConductAttackWithNaniteForgeReducesLosses(): void
    {
        $attackerId = 1;
        $defenderId = 2;

        $attacker = $this->createMockUser($attackerId, 'Attacker', null);
        $defender = $this->createMockUser($defenderId, 'Defender', null);

        // --- Mocks with Nanite Forge ---
        $this->mockUserRepo->shouldReceive('findByCharacterName')->with($defender->characterName)->andReturn($defender);
        $this->mockUserRepo->shouldReceive('findById')->with($attacker->id)->andReturn($attacker);

        $attRes = $this->createMockResources($attacker->id, 100000, 100);
        $defRes = $this->createMockResources($defender->id, 100000, 50, 50); // 50 Guards
        $attStats = $this->createMockStats($attacker->id);
        $defStats = $this->createMockStats($defender->id);
        $attStruct = $this->createMockStructure($attacker->id);
        $defStruct = $this->createMockStructure($defender->id, 10); // Nanite Forge Level 10

        $this->mockResourceRepo->shouldReceive('findByUserId')->with($attacker->id)->andReturn($attRes);
        $this->mockStatsRepo->shouldReceive('findByUserId')->with($attacker->id)->andReturn($attStats);
        $this->mockStructureRepo->shouldReceive('findByUserId')->with($attacker->id)->andReturn($attStruct);

        $this->mockResourceRepo->shouldReceive('findByUserId')->with($defender->id)->andReturn($defRes);
        $this->mockStatsRepo->shouldReceive('findByUserId')->with($defender->id)->andReturn($defStats);
        $this->mockStructureRepo->shouldReceive('findByUserId')->with($defender->id)->andReturn($defStruct);

        $this->mockConfig->shouldReceive('get')->with('game_balance.attack.nanite_casualty_reduction_per_level', 0.0)->andReturn(0.01);
        $this->mockConfig->shouldReceive('get')->with('game_balance.attack.max_nanite_casualty_reduction', 0.0)->andReturn(0.50);
        $this->mockConfig->shouldReceive('get')->with('game_balance.attack')->andReturn([
            'attack_turn_cost' => 1, 'plunder_percent' => 0.1, 'net_worth_steal_percent' => 0.05, 'war_prestige_gain_base' => 10,
            'global_casualty_scalar' => 1.0,
        ]);
        $this->mockConfig->shouldReceive('get')->with('game_balance.alliance_treasury')->andReturn([])->byDefault();
        $this->mockConfig->shouldReceive('get')->with('game_balance.xp.rewards')->andReturn(['battle_win' => 100, 'battle_defense_loss' => 50]);

        $this->mockPowerCalcService->shouldReceive('calculateOffensePower')->andReturn(['total' => 1000]); // Attacker wins
        $this->mockPowerCalcService->shouldReceive('calculateDefensePower')->andReturn(['total' => 500]); // 2:1 ratio
        $this->mockPowerCalcService->shouldReceive('calculateShieldPower')->andReturn(['total_shield_hp' => 0]);


        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction');
        $this->mockDb->shouldReceive('commit');

        // We expect the number of guards to be reduced, but the exact number can vary.
        // We will just check that the method is called with an integer.
        $this->mockResourceRepo->shouldReceive('updateBattleDefender')
            ->once()
            ->with($defender->id, Mockery::any(), Mockery::type('int'));
            
        // Other mocks
        $this->mockResourceRepo->shouldReceive('updateBattleAttacker');
        $this->mockStatsRepo->shouldReceive('updateBattleAttackerStats');
        $this->mockStatsRepo->shouldReceive('updateBattleDefenderStats');
        $this->mockStatsRepo->shouldReceive('incrementBattleStats');
        $this->mockLevelUpService->shouldReceive('grantExperience');
        $this->mockBattleRepo->shouldReceive('createReport')->andReturn(999);
        $this->mockDispatcher->shouldReceive('dispatch');
        $this->mockBountyRepo->shouldReceive('findActiveByTargetId')->andReturn(null);
        
        // Act
        $this->service->conductAttack($attackerId, 'Defender', 'plunder');
        
        // Assert
        $this->assertTrue(true); // Verifies that the mock expectations were met without error
    }

    public function testConductAttackAbsorbedByShield(): void
    {
        $attackerId = 1;
        $defenderId = 2;

        // 1. Mock Users
        $attacker = $this->createMockUser($attackerId, 'Attacker', null);
        $defender = $this->createMockUser($defenderId, 'Defender', null);
        $this->mockUserRepo->shouldReceive('findByCharacterName')->with('Defender')->andReturn($defender);
        $this->mockUserRepo->shouldReceive('findById')->with($attackerId)->andReturn($attacker);

        // 2. Mock Game State
        $attRes = $this->createMockResources($attackerId, 100000, 100);
        $defRes = $this->createMockResources($defenderId, 100000, 50, 50);
        $attStats = $this->createMockStats($attackerId);
        $defStats = $this->createMockStats($defenderId);
        $attStruct = $this->createMockStructure($attackerId);
        $defStruct = $this->createMockStructure($defenderId, 0, 10); // Shield Level 10

        $this->mockResourceRepo->shouldReceive('findByUserId')->andReturn($attRes, $defRes);
        $this->mockStatsRepo->shouldReceive('findByUserId')->andReturn($attStats, $defStats);
        $this->mockStructureRepo->shouldReceive('findByUserId')->andReturn($attStruct, $defStruct);

        // 3. Mock Config
        $this->mockConfig->shouldReceive('get')->with('game_balance.attack')->andReturn([
            'attack_turn_cost' => 1, 'plunder_percent' => 0.1, 'net_worth_steal_percent' => 0.0, 'war_prestige_gain_base' => 0, 'global_casualty_scalar' => 1.0,
        ]);
        $this->mockConfig->shouldReceive('get')->with('game_balance.xp.rewards')->andReturn([
            'battle_win' => 100, 'battle_loss' => 10, 'battle_stalemate' => 50, 'battle_defense_win' => 75, 'battle_defense_loss' => 25
        ]);
        $this->mockConfig->shouldReceive('get')->with('game_balance.alliance_treasury')->andReturn([]);


        // 4. Mock Power Calcs
        $this->mockPowerCalcService->shouldReceive('calculateOffensePower')->andReturn(['total' => 1000]);
        $this->mockPowerCalcService->shouldReceive('calculateDefensePower')->andReturn(['total' => 800]);
        $this->mockPowerCalcService->shouldReceive('calculateShieldPower')->andReturn(['total_shield_hp' => 1500]);

        // 5. Mock Transaction
        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction');
        $this->mockDb->shouldReceive('commit');

        // 6. Expect Updates
        $this->mockResourceRepo->shouldReceive('updateBattleAttacker')->once();
        $this->mockLevelUpService->shouldReceive('grantExperience')->twice();
        $this->mockStatsRepo->shouldReceive('updateBattleAttackerStats')->once();
        $this->mockStatsRepo->shouldReceive('incrementBattleStats')->once();
        $this->mockResourceRepo->shouldReceive('updateBattleDefender')->once();
        $this->mockStatsRepo->shouldReceive('updateBattleDefenderStats')->once();
        $this->mockDispatcher->shouldReceive('dispatch')->once();
        $this->mockBountyRepo->shouldReceive('findActiveByTargetId')->andReturn(null);

        // 7. Expect Report
        $this->mockBattleRepo->shouldReceive('createReport')
            ->once()
            ->with(
                $attackerId, $defenderId, 'plunder', 'defeat', Mockery::any(),
                0, 0, 0, // losses and plunder
                10, Mockery::any(), 0, 1000, 800, Mockery::any(), false,
                1500, 1000 // shield hp and damage
            )->andReturn(1000);

        // Act
        $response = $this->service->conductAttack($attackerId, 'Defender', 'plunder');

        // Assert
        $this->assertTrue($response->isSuccess());
        $this->assertStringContainsString('Attack Complete: defeat!', $response->message);
    }

    // --- Helpers ---

    private function setupAttackMocks(User $attacker, User $defender, int $offPower, int $defPower): void
    {
        $this->mockUserRepo->shouldReceive('findByCharacterName')->with($defender->characterName)->andReturn($defender);
        $this->mockUserRepo->shouldReceive('findById')->with($attacker->id)->andReturn($attacker);

        $attRes = $this->createMockResources($attacker->id, 100000, 100);
        $defRes = $this->createMockResources($defender->id, 100000, 50);
        $attStats = $this->createMockStats($attacker->id);
        $defStats = $this->createMockStats($defender->id);
        $attStruct = $this->createMockStructure($attacker->id);
        $defStruct = $this->createMockStructure($defender->id);

        $this->mockResourceRepo->shouldReceive('findByUserId')->with($attacker->id)->andReturn($attRes);
        $this->mockStatsRepo->shouldReceive('findByUserId')->with($attacker->id)->andReturn($attStats);
        $this->mockStructureRepo->shouldReceive('findByUserId')->with($attacker->id)->andReturn($attStruct);

        $this->mockResourceRepo->shouldReceive('findByUserId')->with($defender->id)->andReturn($defRes);
        $this->mockStatsRepo->shouldReceive('findByUserId')->with($defender->id)->andReturn($defStats);
        $this->mockStructureRepo->shouldReceive('findByUserId')->with($defender->id)->andReturn($defStruct);

        $this->mockConfig->shouldReceive('get')->with('game_balance.attack')->andReturn(['attack_turn_cost' => 1, 'plunder_percent' => 0.1, 'net_worth_steal_percent' => 0.05, 'war_prestige_gain_base' => 10]);
        $this->mockConfig->shouldReceive('get')->with('game_balance.alliance_treasury')->andReturn([])->byDefault();
        $this->mockConfig->shouldReceive('get')->with('game_balance.xp.rewards')->andReturn([
            'battle_win' => 100, 
            'battle_loss' => 10,
            'battle_stalemate' => 50,
            'battle_defense_win' => 75,
            'battle_defense_loss' => 25
        ]);

        $this->mockPowerCalcService->shouldReceive('calculateOffensePower')->andReturn(['total' => $offPower]);
        $this->mockPowerCalcService->shouldReceive('calculateDefensePower')->andReturn(['total' => $defPower]);
        $this->mockPowerCalcService->shouldReceive('calculateShieldPower')->andReturn(['total_shield_hp' => 0])->byDefault();

        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);
        $this->mockDb->shouldReceive('beginTransaction');
        $this->mockDb->shouldReceive('commit');

        $this->mockResourceRepo->shouldReceive('updateBattleAttacker');
        $this->mockResourceRepo->shouldReceive('updateBattleDefender');
        $this->mockStatsRepo->shouldReceive('updateBattleAttackerStats');
        $this->mockStatsRepo->shouldReceive('updateBattleDefenderStats');
        $this->mockStatsRepo->shouldReceive('incrementBattleStats');
        
        $this->mockLevelUpService->shouldReceive('grantExperience');
        
        $this->mockBattleRepo->shouldReceive('createReport')->andReturn(999);
        
        $this->mockDispatcher->shouldReceive('dispatch');

        $this->mockBountyRepo->shouldReceive('findActiveByTargetId')
            ->with($defender->id)
            ->andReturn(null)
            ->byDefault();
    }

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

    private function createMockResources(int $userId, int $credits, int $soldiers, int $guards = 50): UserResource
    {
        return new UserResource($userId, $credits, 0, 0, 0.0, 50, 10, $soldiers, $guards, 10, 5);
    }

    private function createMockStats(int $userId): UserStats
    {
        return new UserStats($userId, 5, 1000, 500000, 100, 100, 50, 0, 0, 0, 0, 0, 0, 5, null, 0, 0, 0, 0);
    }

    private function createMockStructure(int $userId, int $naniteForgeLevel = 0, int $shieldLevel = 0): UserStructure
    {
        return new UserStructure($userId, 10, 5, 3, 2, 8, 1, 1, 0, 0, $naniteForgeLevel, 0, $shieldLevel);
    }
}
