<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Services\BlackMarketService;
use App\Models\Services\AttackService;
use App\Models\Services\PowerCalculatorService;
use App\Models\Services\EffectService;
use App\Models\Repositories\EffectRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\BountyRepository;
use App\Models\Repositories\BlackMarketLogRepository;
use App\Models\Repositories\HouseFinanceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\BattleRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceBankLogRepository;
use App\Models\Repositories\AllianceStructureRepository;
use App\Models\Repositories\AllianceStructureDefinitionRepository;
use App\Models\Repositories\EdictRepository;
use App\Models\Repositories\GeneralRepository;
use App\Models\Services\ArmoryService;
use App\Models\Services\LevelUpService;
use App\Core\Config;
use App\Core\ServiceResponse;
use App\Core\Events\EventDispatcher;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStats;
use App\Models\Entities\UserStructure;
use App\Models\Entities\User;
use PDO;

class HighRiskProtocolTest extends TestCase
{
    private $db;
    private $config;
    private $effectService;
    private $bmService;
    private $attackService;
    private $powerService;

    // Repos needed for mocking
    private $resourceRepo;
    private $effectRepo;
    private $logRepo;
    private $userRepo;
    private $structureRepo;
    private $statsRepo;

    protected function setUp(): void
    {
        // Mock PDO
        $this->db = $this->createMock(PDO::class);
        $this->db->method('inTransaction')->willReturn(true);

        // Mock Config
        $this->config = $this->createMock(Config::class);
        $this->config->method('get')->willReturnCallback(function($key, $default = null) {
            if ($key === 'black_market.costs.high_risk_buff') return 50000000;
            if ($key === 'black_market.costs.safehouse') return 100000;
            if ($key === 'game_balance.attack') return [
                'attack_turn_cost' => 10,
                'power_per_soldier' => 1,
                'power_per_guard' => 1,
                'global_casualty_scalar' => 1.0,
                'plunder_percent' => 0.1,
                'net_worth_steal_percent' => 0.1,
                'war_prestige_gain_base' => 10,
                'power_per_offense_level' => 0.1,
                'power_per_defense_level' => 0.1,
                'power_per_strength_point' => 0.1,
                'power_per_constitution_point' => 0.1,
                'power_per_fortification_level' => 0.1
            ];
            if ($key === 'game_balance.xp.rewards') return [
                'battle_win' => 100,
                'battle_loss' => 20,
                'battle_stalemate' => 50,
                'battle_defense_win' => 50,
                'battle_defense_loss' => 20
            ];
            if ($key === 'game_balance.turn_processor') return [
                'credit_income_per_econ_level' => 1000,
                'credit_income_per_worker' => 100,
                'credit_bonus_per_wealth_point' => 0.01,
                'bank_interest_rate' => 0.02,
                'citizen_growth_per_pop_level' => 50,
                'accounting_firm_base_bonus' => 0.01,
                'accounting_firm_multiplier' => 1.0,
            ];
            if ($key === 'game_balance.generals') return [];
            return $default;
        });

        // Mock Repositories
        $this->resourceRepo = $this->createMock(ResourceRepository::class);
        $this->effectRepo = $this->createMock(EffectRepository::class);
        $this->logRepo = $this->createMock(BlackMarketLogRepository::class);
        $this->userRepo = $this->createMock(UserRepository::class);
        $this->structureRepo = $this->createMock(StructureRepository::class);
        $this->statsRepo = $this->createMock(StatsRepository::class);
        
        // Setup EffectService with mocked Repo
        $this->effectService = new EffectService($this->effectRepo, $this->userRepo);

        // Setup BlackMarketService
        $this->bmService = new BlackMarketService(
            $this->db,
            $this->config,
            $this->resourceRepo,
            $this->statsRepo, 
            $this->userRepo,
            $this->createMock(BountyRepository::class),
            $this->createMock(AttackService::class), 
            $this->logRepo,
            $this->effectService,
            $this->createMock(HouseFinanceRepository::class)
        );

        // Setup AttackService (Testing combat modifiers)
        $this->powerService = new PowerCalculatorService(
            $this->config,
            $this->createMock(ArmoryService::class),
            $this->createMock(AllianceStructureRepository::class),
            $this->createMock(AllianceStructureDefinitionRepository::class),
            $this->createMock(EdictRepository::class),
            $this->createMock(GeneralRepository::class),
            $this->effectService
        );

        $this->attackService = $this->getMockBuilder(AttackService::class)
            ->setConstructorArgs([
                $this->db, $this->config, $this->userRepo, $this->resourceRepo,
                $this->structureRepo, $this->statsRepo, 
                $this->createMock(BattleRepository::class),
                $this->createMock(AllianceRepository::class),
                $this->createMock(AllianceBankLogRepository::class),
                $this->createMock(BountyRepository::class),
                $this->createMock(ArmoryService::class),
                $this->powerService, 
                $this->createMock(LevelUpService::class),
                $this->createMock(EventDispatcher::class),
                $this->effectService
            ])
            ->onlyMethods(['calculateWinnerLosses', 'calculateLoserLosses']) 
            ->getMock();
    }

    // Helper Methods for Entities
    private function makeUser(int $id): User {
        return new User(
            $id, 
            "test{$id}@example.com", 
            "TestChar{$id}", 
            null, null, null, null, null, 
            'hash', 
            '2024-01-01 00:00:00'
        );
    }

    private function makeResource(int $userId, array $overrides = []): UserResource {
        $defaults = [
            'user_id' => $userId,
            'credits' => 0,
            'banked_credits' => 0,
            'gemstones' => 0,
            'naquadah_crystals' => 0.0,
            'untrained_citizens' => 0,
            'workers' => 0,
            'soldiers' => 0,
            'guards' => 0,
            'spies' => 0,
            'sentries' => 0,
            'untraceable_chips' => 0,
            'research_data' => 0,
            'dark_matter' => 0,
            'protoform' => 0.0
        ];
        $d = array_merge($defaults, $overrides);
        return new UserResource(
            $d['user_id'], $d['credits'], $d['banked_credits'], $d['gemstones'], $d['naquadah_crystals'],
            $d['untrained_citizens'], $d['workers'], $d['soldiers'], $d['guards'], $d['spies'], $d['sentries'],
            $d['untraceable_chips'], $d['research_data'], $d['dark_matter'], $d['protoform']
        );
    }

    private function makeStats(int $userId, array $overrides = []): UserStats {
        $defaults = [
            'user_id' => $userId,
            'level' => 1,
            'experience' => 0,
            'net_worth' => 0,
            'war_prestige' => 0,
            'energy' => 10,
            'attack_turns' => 10,
            'level_up_points' => 0,
            'strength_points' => 0,
            'constitution_points' => 0,
            'wealth_points' => 0,
            'dexterity_points' => 0,
            'charisma_points' => 0,
            'deposit_charges' => 4,
            'last_deposit_at' => null
        ];
        $d = array_merge($defaults, $overrides);
        return new UserStats(
            $d['user_id'], $d['level'], $d['experience'], $d['net_worth'], $d['war_prestige'],
            $d['energy'], $d['attack_turns'], $d['level_up_points'], $d['strength_points'], $d['constitution_points'],
            $d['wealth_points'], $d['dexterity_points'], $d['charisma_points'], $d['deposit_charges'], $d['last_deposit_at']
        );
    }

    private function makeStructure(int $userId, array $overrides = []): UserStructure {
        $defaults = [
            'user_id' => $userId,
            'fortification_level' => 0,
            'offense_upgrade_level' => 0,
            'defense_upgrade_level' => 0,
            'spy_upgrade_level' => 0,
            'economy_upgrade_level' => 0,
            'population_level' => 0,
            'armory_level' => 0,
            'accounting_firm_level' => 0
        ];
        $d = array_merge($defaults, $overrides);
        return new UserStructure(
            $d['user_id'], $d['fortification_level'], $d['offense_upgrade_level'], $d['defense_upgrade_level'],
            $d['spy_upgrade_level'], $d['economy_upgrade_level'], $d['population_level'], $d['armory_level'],
            $d['accounting_firm_level']
        );
    }

    public function testPurchaseProtocolSuccess(): void
    {
        $userId = 1;
        $cost = 50000000;

        $resources = $this->makeResource($userId, ['naquadah_crystals' => 60000000]);
        $this->resourceRepo->method('findByUserId')->willReturn($resources);

        $this->resourceRepo->expects($this->once())
            ->method('updateResources')
            ->with($userId, 0, -$cost);

        $this->effectRepo->expects($this->atLeastOnce())
            ->method('getActiveEffect')
            ->willReturnMap([
                [$userId, 'peace_shield', ['type' => 'peace_shield']],
                [$userId, 'high_risk_protocol', null]
            ]);

        $this->effectRepo->expects($this->once())
            ->method('removeEffect')
            ->with($userId, 'peace_shield');

        $this->effectRepo->expects($this->once())
            ->method('addEffect')
            ->with($userId, 'high_risk_protocol', $this->anything());

        $result = $this->bmService->purchaseHighRiskBuff($userId);
        
        $this->assertTrue($result->isSuccess());
        $this->assertStringContainsString('Safehouse disabled', $result->message);
    }

    public function testPurchaseProtocolFailsInsufficientFunds(): void
    {
        $userId = 1;
        $resources = $this->makeResource($userId, ['naquadah_crystals' => 100]);
        $this->resourceRepo->method('findByUserId')->willReturn($resources);

        $result = $this->bmService->purchaseHighRiskBuff($userId);
        $this->assertFalse($result->isSuccess());
    }

    public function testEarlyTermination(): void
    {
        $userId = 1;

        $this->effectRepo->method('getActiveEffect')
            ->with($userId, 'high_risk_protocol')
            ->willReturn(['expires_at' => '2099-01-01 00:00:00']);

        $this->effectRepo->expects($this->once())
            ->method('removeEffect')
            ->with($userId, 'high_risk_protocol');

        $this->effectRepo->expects($this->once())
            ->method('addEffect')
            ->with($userId, 'safehouse_cooldown', $this->callback(function($date) {
                $ts = strtotime($date);
                $diff = $ts - time();
                return $diff > 3500 && $diff < 3700;
            }));

        $result = $this->bmService->terminateHighRiskProtocol($userId);
        $this->assertTrue($result->isSuccess());
    }

    public function testSafehouseBlockedByProtocol(): void
    {
        $userId = 1;
        $this->effectRepo->method('getActiveEffect')
            ->with($userId, 'high_risk_protocol')
            ->willReturn(['expires_at' => '2099-01-01']);

        $result = $this->bmService->purchaseSafehouse($userId);
        
        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('Cannot activate Safehouse', $result->message);
    }

    public function testSafehouseBlockedByCooldown(): void
    {
        $userId = 1;
        $this->effectRepo->method('getActiveEffect')
            ->willReturnMap([
                [$userId, 'high_risk_protocol', null],
                [$userId, 'safehouse_cooldown', ['expires_at' => date('Y-m-d H:i:s', time() + 1800)]]
            ]);

        $result = $this->bmService->purchaseSafehouse($userId);
        
        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('Safehouse systems are rebooting', $result->message);
    }

    public function testAttackerCasualtiesReduced(): void
    {
        $attackerId = 1;
        $defenderId = 2;

        // Attacker has OVERWHELMING power to ensure Victory
        $attacker = $this->makeUser(1);
        $attRes = $this->makeResource(1, ['soldiers' => 10000]); // 10k soldiers
        $attStats = $this->makeStats(1, ['attack_turns' => 100]);
        $attStruct = $this->makeStructure(1);

        // Defender is weak
        $defender = new User(
            2, "def@ex.com", "Target", null, null, null, null, null, "hash", "now"
        );
        $defRes = $this->makeResource(2, ['guards' => 100, 'credits' => 100000]);
        $defStats = $this->makeStats(2, ['net_worth' => 5000]);
        $defStruct = $this->makeStructure(2);

        $this->userRepo->method('findByCharacterName')->willReturn($defender);
        $this->userRepo->method('findById')->willReturn($attacker);
        $this->resourceRepo->method('findByUserId')->willReturnMap([
            [$attackerId, $attRes],
            [$defenderId, $defRes]
        ]);
        $this->statsRepo->method('findByUserId')->willReturnMap([
            [$attackerId, $attStats],
            [$defenderId, $defStats]
        ]);
        $this->structureRepo->method('findByUserId')->willReturnMap([
            [$attackerId, $attStruct],
            [$defenderId, $defStruct]
        ]);

        $this->effectRepo->method('getActiveEffect')
            ->willReturnMap([
                [$attackerId, 'high_risk_protocol', ['active' => true]],
                [$defenderId, 'peace_shield', null]
            ]);

        // Mock calculateWinnerLosses to return specific value (100)
        // With Protocol (-10%), loss should be 90.
        // Resulting Soldiers: 10000 - 90 = 9910.
        $this->attackService->method('calculateWinnerLosses')->willReturn(100);

        $this->resourceRepo->expects($this->once())
            ->method('updateBattleAttacker')
            ->with(
                $attackerId, 
                $this->anything(), 
                $this->callback(function($newSoldiers) {
                    return $newSoldiers === 9910;
                })
            );

        $this->attackService->conductAttack($attackerId, 'Target', 'plunder');
    }

    public function testIncomeBoosted(): void
    {
        $userId = 1;

        $res = $this->makeResource(1, ['workers' => 100]);
        $stats = $this->makeStats(1, ['wealth_points' => 0]);
        $struct = $this->makeStructure(1, ['economy_upgrade_level' => 10]);
        
        $this->effectRepo->method('getActiveEffect')
            ->with($userId, 'high_risk_protocol')
            ->willReturn(['active' => true]);

        $this->createMock(EdictRepository::class)->method('findActiveByUserId')->willReturn([]);

        $result = $this->powerService->calculateIncomePerTurn($userId, $res, $stats, $struct);

        // Base Calc:
        // Econ: 10 * 1000 = 10,000
        // Worker: 100 * 100 = 10,000
        // Total Base = 20,000
        // Multiplier: 1.0 (Base) + 0.5 (Protocol) = 1.5.
        // Expected: 30,000.
        
        $this->assertEquals(30000, $result['total_credit_income']);
    }

    public function testNaturalExpiration(): void
    {
        $userId = 1;
        
        $this->effectRepo->method('getActiveEffect')
            ->willReturnMap([
                [$userId, 'high_risk_protocol', null],
                [$userId, 'safehouse_cooldown', null]
            ]);

        $res = $this->makeResource(1, ['naquadah_crystals' => 1000000]);
        $this->resourceRepo->method('findByUserId')->willReturn($res);

        $this->effectRepo->expects($this->exactly(2))
            ->method('addEffect')
            ->willReturnCallback(function($uid, $type, $dur) {
                return true;
            });

        $result = $this->bmService->purchaseSafehouse($userId);
        $this->assertTrue($result->isSuccess());
    }
}
