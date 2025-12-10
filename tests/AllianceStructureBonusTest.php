--- START OF FILE tests/AllianceStructureBonusTest.php ---

<?php

/**
 * FUNCTIONAL TEST: ALLIANCE BONUSES
 * Verifies that structures (e.g., Command Nexus, Warlord's Throne) correctly 
 * modify calculated values in PowerCalculatorService.
 */

if (php_sapi_name() !== 'cli') {
    die('Access Denied: CLI only.');
}

require __DIR__ . '/../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (\Exception $e) {}

use App\Core\ContainerFactory;
use App\Models\Services\PowerCalculatorService;
use App\Models\Repositories\{UserRepository, ResourceRepository, StatsRepository, StructureRepository, AllianceRepository, AllianceRoleRepository, AllianceStructureRepository};

try {
    $container = ContainerFactory::createContainer();
    $db = $container->get(PDO::class);
    
    // Resolve Services
    $powerCalc = $container->get(PowerCalculatorService::class);
    $userRepo = $container->get(UserRepository::class);
    $resRepo = $container->get(ResourceRepository::class);
    $statsRepo = $container->get(StatsRepository::class);
    $structRepo = $container->get(StructureRepository::class);
    $allianceRepo = $container->get(AllianceRepository::class);
    $roleRepo = $container->get(AllianceRoleRepository::class);
    $allyStructRepo = $container->get(AllianceStructureRepository::class);
    
    $db->beginTransaction();

    // 1. Setup Test Subjects
    $seed = bin2hex(random_bytes(2)); // 4 chars
    $userId = $userRepo->createUser("bonus_{$seed}@test.com", "Bonus_{$seed}", 'hash');
    $resRepo->createDefaults($userId);
    $statsRepo->createDefaults($userId);
    $structRepo->createDefaults($userId);
    
    // Ensure base production exists (1 worker)
    $resRepo->updateTrainedUnits($userId, 1000, 0, 1, 0, 0, 0, 0);

    // Create Alliance
    $allianceId = $allianceRepo->create("BonusAlly_{$seed}", "B{$seed}", $userId);
    $roleId = $roleRepo->create($allianceId, 'Leader', 1, []);
    $userRepo->setAlliance($userId, $allianceId, $roleId);

    // 2. Define Tests
    $tests = [
        'Baseline' => [
            'setup' => fn() => null,
            'check' => function($inc, $off) {
                if ($inc['alliance_credit_bonus_pct'] > 0) throw new Exception("Baseline has unexpected credit bonus.");
                if ($off['alliance_bonus_pct'] > 0) throw new Exception("Baseline has unexpected offense bonus.");
            }
        ],
        'Command Nexus (Income)' => [
            'setup' => fn() => $allyStructRepo->createOrUpgrade($allianceId, 'command_nexus', 1),
            'check' => function($inc, $off) {
                if (abs($inc['alliance_credit_bonus_pct'] - 0.05) > 0.001) throw new Exception("Command Nexus failed (Expected 0.05, got {$inc['alliance_credit_bonus_pct']})");
            }
        ],
        'Galactic Research Hub (Stacking)' => [
            'setup' => fn() => $allyStructRepo->createOrUpgrade($allianceId, 'galactic_research_hub', 1),
            'check' => function($inc, $off) {
                // 0.05 (Nexus) + 0.10 (Hub) = 0.15
                if (abs($inc['alliance_credit_bonus_pct'] - 0.15) > 0.001) throw new Exception("Hub Stacking failed (Expected 0.15, got {$inc['alliance_credit_bonus_pct']})");
            }
        ],
        'Warlords Throne (Multiplier)' => [
            'setup' => fn() => $allyStructRepo->createOrUpgrade($allianceId, 'warlords_throne', 4), // 4 * 0.15 = +60% mult
            'check' => function($inc, $off) {
                // Previous Base: 0.15. With +60% Throne: 0.15 * 1.6 = 0.24
                if (abs($inc['alliance_credit_bonus_pct'] - 0.24) > 0.001) throw new Exception("Throne Multiplier failed (Expected 0.24, got {$inc['alliance_credit_bonus_pct']})");
            }
        ]
    ];

    // 3. Run Tests
    $resources = $resRepo->findByUserId($userId);
    $stats = $statsRepo->findByUserId($userId);
    $structures = $structRepo->findByUserId($userId);

    foreach ($tests as $name => $test) {
        $test['setup']();
        $powerCalc->clearCache();
        
        $inc = $powerCalc->calculateIncomePerTurn($userId, $resources, $stats, $structures, $allianceId);
        $off = $powerCalc->calculateOffensePower($userId, $resources, $stats, $structures, $allianceId);
        
        $test['check']($inc, $off);
        echo "   \033[32m[PASS] {$name}\033[0m\n";
    }

} catch (Throwable $e) {
    echo "   \033[31m[FAIL] " . $e->getMessage() . "\033[0m\n";
    exit(1);
} finally {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
}