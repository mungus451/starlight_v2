<?php

declare(strict_types=1);

// tests/AllianceStructureBonusTest.php

/**
 * ALLIANCE STRUCTURE BONUS INTEGRATION TEST
 * 
 * Verifies that every Alliance Structure correctly modifies 
 * the calculations in PowerCalculatorService.
 */

if (php_sapi_name() !== 'cli') {
    die('Access Denied: CLI only.');
}

require __DIR__ . '/../../vendor/autoload.php';

// Load Environment
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    die("Error: Could not find .env file.\n");
}

use App\Core\ContainerFactory;
use App\Models\Services\PowerCalculatorService;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Repositories\AllianceStructureRepository;

echo "\n" . str_repeat("=", 60) . "\n";
echo "   ALLIANCE STRUCTURE WIRING VERIFICATION\n";
echo str_repeat("=", 60) . "\n";

try {
    // 1. Boot Container
    $container = ContainerFactory::createContainer();
    $db = $container->get(PDO::class);
    
    // 2. Resolve Dependencies
    $powerCalc = $container->get(PowerCalculatorService::class);
    $userRepo = $container->get(UserRepository::class);
    $resRepo = $container->get(ResourceRepository::class);
    $statsRepo = $container->get(StatsRepository::class);
    $structRepo = $container->get(StructureRepository::class);
    $allianceRepo = $container->get(AllianceRepository::class);
    $roleRepo = $container->get(AllianceRoleRepository::class);
    $allyStructRepo = $container->get(AllianceStructureRepository::class);
    
    // 3. Start Transaction
    $db->beginTransaction();

    // 4. Setup Test Data
    $seed = (string)microtime(true);
    $tag = substr(md5($seed), 0, 5);
    
    echo "Creating Test User & Alliance... ";
    $userId = $userRepo->createUser("struct_test_{$tag}@example.com", "Tester_{$tag}", 'hash');
    $resRepo->createDefaults($userId);
    $statsRepo->createDefaults($userId);
    $structRepo->createDefaults($userId);
    
    // Give base resources for calculations to work against
    $resRepo->updateTrainedUnits($userId, 1000000, 0, 100, 100, 100, 0, 0); 

    $allianceId = $allianceRepo->create("StructureTest_{$tag}", $tag, $userId);
    $roleId = $roleRepo->create($allianceId, 'Leader', 1, []);
    $userRepo->setAlliance($userId, $allianceId, $roleId);
    echo "OK (User: $userId, Ally: $allianceId)\n\n";

    // --- Helper to fetch fresh user entities ---
    $refresh = function() use ($userId, $resRepo, $statsRepo, $structRepo) {
        return [
            $resRepo->findByUserId($userId),
            $statsRepo->findByUserId($userId),
            $structRepo->findByUserId($userId)
        ];
    };

    // --- TEST 1: BASELINE (0 Structures) ---
    $powerCalc->clearCache();
    [$r, $st, $str] = $refresh();
    
    $inc = $powerCalc->calculateIncomePerTurn($userId, $r, $st, $str, $allianceId);
    $off = $powerCalc->calculateOffensePower($userId, $r, $st, $str, $allianceId);
    $def = $powerCalc->calculateDefensePower($userId, $r, $st, $str, $allianceId);

    if ($inc['alliance_citizen_bonus'] !== 0 || $inc['alliance_credit_bonus_pct'] > 0) {
        throw new Exception("Baseline Failed: Income bonuses detected where none should exist.");
    }
    if ($off['alliance_bonus_pct'] > 0 || $def['alliance_bonus_pct'] > 0) {
        throw new Exception("Baseline Failed: Military bonuses detected where none should exist.");
    }
    echo "✅ [PASS] Baseline (No Structures)\n";

    // --- TEST 2: POPULATION HABITAT (Citizens) ---
    // Bonus: +5 Flat per level
    $allyStructRepo->createOrUpgrade($allianceId, 'population_habitat', 1);
    $powerCalc->clearCache();
    $inc = $powerCalc->calculateIncomePerTurn($userId, $r, $st, $str, $allianceId);
    
    if ($inc['alliance_citizen_bonus'] !== 5) {
        throw new Exception("Population Habitat Failed. Expected 5, got {$inc['alliance_citizen_bonus']}");
    }
    echo "✅ [PASS] Population Habitat (+5 Citizens)\n";

    // --- TEST 3: COMMAND NEXUS (Income %) ---
    // Bonus: +0.05 (5%) per level
    $allyStructRepo->createOrUpgrade($allianceId, 'command_nexus', 1);
    $powerCalc->clearCache();
    $inc = $powerCalc->calculateIncomePerTurn($userId, $r, $st, $str, $allianceId);
    
    // Note: resource_bonus is 0, income_bonus is 0.05. Total = 0.05.
    if (abs($inc['alliance_credit_bonus_pct'] - 0.05) > 0.001) {
        throw new Exception("Command Nexus Failed. Expected 0.05, got {$inc['alliance_credit_bonus_pct']}");
    }
    echo "✅ [PASS] Command Nexus (+5% Income)\n";

    // --- TEST 4: GALACTIC RESEARCH HUB (Resources %) ---
    // Bonus: +0.10 (10%) per level. Additive with Nexus.
    $allyStructRepo->createOrUpgrade($allianceId, 'galactic_research_hub', 1);
    $powerCalc->clearCache();
    $inc = $powerCalc->calculateIncomePerTurn($userId, $r, $st, $str, $allianceId);
    
    // Expected: 0.05 (Nexus) + 0.10 (Hub) = 0.15
    if (abs($inc['alliance_credit_bonus_pct'] - 0.15) > 0.001) {
        throw new Exception("Research Hub Failed. Expected 0.15 (0.05+0.10), got {$inc['alliance_credit_bonus_pct']}");
    }
    echo "✅ [PASS] Galactic Research Hub (+10% Income/Res)\n";

    // --- TEST 5: ORBITAL TRAINING GROUNDS (Offense %) ---
    // Bonus: +0.05 (5%) per level
    $allyStructRepo->createOrUpgrade($allianceId, 'orbital_training_grounds', 1);
    $powerCalc->clearCache();
    $off = $powerCalc->calculateOffensePower($userId, $r, $st, $str, $allianceId);
    
    if (abs($off['alliance_bonus_pct'] - 0.05) > 0.001) {
        throw new Exception("Training Grounds Failed. Expected 0.05, got {$off['alliance_bonus_pct']}");
    }
    echo "✅ [PASS] Orbital Training Grounds (+5% Offense)\n";

    // --- TEST 6: CITADEL SHIELD (Defense %) ---
    // Bonus: +0.10 (10%) per level
    $allyStructRepo->createOrUpgrade($allianceId, 'citadel_shield', 1);
    $powerCalc->clearCache();
    $def = $powerCalc->calculateDefensePower($userId, $r, $st, $str, $allianceId);
    
    if (abs($def['alliance_bonus_pct'] - 0.10) > 0.001) {
        throw new Exception("Citadel Shield Failed. Expected 0.10, got {$def['alliance_bonus_pct']}");
    }
    echo "✅ [PASS] Citadel Shield (+10% Defense)\n";

    // --- TEST 7: WARLORD'S THRONE (Synergy) ---
    // Bonus: Multiplies ALL other bonuses by (1 + (0.15 * level))
    // We set Throne to Level 4 -> +60% Multiplier (1.6x)
    //
    // Calculations:
    // Population: 5 base * 1.6 = 8
    // Offense: 0.05 base * 1.6 = 0.08
    // Defense: 0.10 base * 1.6 = 0.16
    
    $allyStructRepo->createOrUpgrade($allianceId, 'warlords_throne', 4);
    $powerCalc->clearCache();
    
    $inc = $powerCalc->calculateIncomePerTurn($userId, $r, $st, $str, $allianceId);
    $off = $powerCalc->calculateOffensePower($userId, $r, $st, $str, $allianceId);
    $def = $powerCalc->calculateDefensePower($userId, $r, $st, $str, $allianceId);
    
    // Check Population
    if ($inc['alliance_citizen_bonus'] !== 8) {
        throw new Exception("Warlord's Throne Failed (Citizens). Expected 8, got {$inc['alliance_citizen_bonus']}");
    }
    
    // Check Offense
    if (abs($off['alliance_bonus_pct'] - 0.08) > 0.001) {
        throw new Exception("Warlord's Throne Failed (Offense). Expected 0.08, got {$off['alliance_bonus_pct']}");
    }

    // Check Defense
    if (abs($def['alliance_bonus_pct'] - 0.16) > 0.001) {
        throw new Exception("Warlord's Throne Failed (Defense). Expected 0.16, got {$def['alliance_bonus_pct']}");
    }

    echo "✅ [PASS] Warlord's Throne (Synergy Multiplier)\n";

    // --- CLEANUP ---
    $db->rollBack();
    echo "\nTest Complete. Database rolled back.\n";

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "\n❌ TEST FAILED: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}