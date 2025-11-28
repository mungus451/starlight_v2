<?php

// tests/AllianceStructureBonusTest.php

if (php_sapi_name() !== 'cli') {
    die('Access Denied: CLI only.');
}

require __DIR__ . '/../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
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
use App\Models\Repositories\AllianceStructureDefinitionRepository;

echo "\n" . str_repeat("=", 60) . "\n";
echo "   ALLIANCE STRUCTURE WIRING TEST (ALL STRUCTURES)\n";
echo str_repeat("=", 60) . "\n";

try {
    // 1. Build Container & Services
    $container = ContainerFactory::createContainer();
    $db = $container->get(PDO::class);
    
    $powerCalc = $container->get(PowerCalculatorService::class);
    $userRepo = $container->get(UserRepository::class);
    $resRepo = $container->get(ResourceRepository::class);
    $statsRepo = $container->get(StatsRepository::class);
    $structRepo = $container->get(StructureRepository::class);
    $allianceRepo = $container->get(AllianceRepository::class);
    $roleRepo = $container->get(AllianceRoleRepository::class);
    $allyStructRepo = $container->get(AllianceStructureRepository::class);
    
    // 2. Start Transaction
    $db->beginTransaction();

    // 3. Create Unique Test Entities
    // Use microtime to ensure uniqueness even if run multiple times quickly
    $seed = str_replace('.', '', (string)microtime(true));
    $uniqueTag = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
    
    $userId = $userRepo->createUser("test_{$seed}@example.com", "Cmdr_{$seed}", 'hash');
    $resRepo->createDefaults($userId);
    $statsRepo->createDefaults($userId);
    $structRepo->createDefaults($userId);
    
    // Give user basic income capability (1 worker) and offense/defense (1 unit)
    $resRepo->updateTrainedUnits($userId, 1000000, 0, 1, 1, 1, 0, 0); 

    // Create Alliance with Unique Tag
    $allianceId = $allianceRepo->create("Ally_{$seed}", $uniqueTag, $userId);
    $roleId = $roleRepo->create($allianceId, 'Leader', 1, []);
    $userRepo->setAlliance($userId, $allianceId, $roleId);

    echo "✅ Setup Complete (User ID: $userId, Alliance: [$uniqueTag] $allianceId)\n\n";

    // --- Helpers ---
    $refresh = function() use ($userId, $resRepo, $statsRepo, $structRepo) {
        return [
            $resRepo->findByUserId($userId),
            $statsRepo->findByUserId($userId),
            $structRepo->findByUserId($userId)
        ];
    };

    // --- TEST 1: BASELINE (No Alliance Structures) ---
    $powerCalc->clearCache();
    [$r, $st, $str] = $refresh();
    
    $baselineInc = $powerCalc->calculateIncomePerTurn($userId, $r, $st, $str, $allianceId);
    $baselineOff = $powerCalc->calculateOffensePower($userId, $r, $st, $str, $allianceId);
    $baselineDef = $powerCalc->calculateDefensePower($userId, $r, $st, $str, $allianceId);

    echo "1. Baseline Check:\n";
    echo "   - Citizens: {$baselineInc['alliance_citizen_bonus']}\n";
    echo "   - Income Bonus %: {$baselineInc['alliance_credit_bonus_pct']}\n";
    echo "   - Offense Bonus %: {$baselineOff['alliance_bonus_pct']}\n";
    echo "   - Defense Bonus %: {$baselineDef['alliance_bonus_pct']}\n";
    
    if ($baselineInc['alliance_citizen_bonus'] !== 0 || $baselineInc['alliance_credit_bonus_pct'] > 0) {
        throw new Exception("Baseline failed: Bonuses detected where none should exist.");
    }
    echo "   -> PASS\n\n";

    // --- TEST 2: POPULATION HABITAT (Citizens) ---
    // Bonus: +5 Flat per level
    $allyStructRepo->createOrUpgrade($allianceId, 'population_habitat', 1);
    $powerCalc->clearCache();
    $inc = $powerCalc->calculateIncomePerTurn($userId, $r, $st, $str, $allianceId);
    
    echo "2. Population Habitat (Lvl 1):\n";
    echo "   - Expected: 5 | Actual: {$inc['alliance_citizen_bonus']}\n";
    if ($inc['alliance_citizen_bonus'] !== 5) throw new Exception("Population Habitat failed.");
    echo "   -> PASS\n\n";

    // --- TEST 3: COMMAND NEXUS (Income %) ---
    // Bonus: +0.05 (5%) per level
    $allyStructRepo->createOrUpgrade($allianceId, 'command_nexus', 1);
    $powerCalc->clearCache();
    $inc = $powerCalc->calculateIncomePerTurn($userId, $r, $st, $str, $allianceId);
    
    echo "3. Command Nexus (Lvl 1):\n";
    echo "   - Expected: 0.05 | Actual: {$inc['alliance_credit_bonus_pct']}\n";
    if (abs($inc['alliance_credit_bonus_pct'] - 0.05) > 0.001) throw new Exception("Command Nexus failed.");
    echo "   -> PASS\n\n";

    // --- TEST 4: GALACTIC RESEARCH HUB (Resources %) ---
    // Bonus: +0.10 (10%) per level (additive with Nexus)
    $allyStructRepo->createOrUpgrade($allianceId, 'galactic_research_hub', 1);
    $powerCalc->clearCache();
    $inc = $powerCalc->calculateIncomePerTurn($userId, $r, $st, $str, $allianceId);
    
    echo "4. Galactic Research Hub (Lvl 1 + Nexus Lvl 1):\n";
    // 0.05 (Nexus) + 0.10 (Hub) = 0.15
    echo "   - Expected: 0.15 | Actual: {$inc['alliance_credit_bonus_pct']}\n";
    if (abs($inc['alliance_credit_bonus_pct'] - 0.15) > 0.001) throw new Exception("Research Hub failed.");
    echo "   -> PASS\n\n";

    // --- TEST 5: ORBITAL TRAINING GROUNDS (Offense %) ---
    // Bonus: +0.05 (5%) per level
    $allyStructRepo->createOrUpgrade($allianceId, 'orbital_training_grounds', 1);
    $powerCalc->clearCache();
    $off = $powerCalc->calculateOffensePower($userId, $r, $st, $str, $allianceId);
    
    echo "5. Orbital Training Grounds (Lvl 1):\n";
    echo "   - Expected: 0.05 | Actual: {$off['alliance_bonus_pct']}\n";
    if (abs($off['alliance_bonus_pct'] - 0.05) > 0.001) throw new Exception("Training Grounds failed.");
    echo "   -> PASS\n\n";

    // --- TEST 6: CITADEL SHIELD (Defense %) ---
    // Bonus: +0.10 (10%) per level
    $allyStructRepo->createOrUpgrade($allianceId, 'citadel_shield', 1);
    $powerCalc->clearCache();
    $def = $powerCalc->calculateDefensePower($userId, $r, $st, $str, $allianceId);
    
    echo "6. Citadel Shield (Lvl 1):\n";
    echo "   - Expected: 0.10 | Actual: {$def['alliance_bonus_pct']}\n";
    if (abs($def['alliance_bonus_pct'] - 0.10) > 0.001) throw new Exception("Citadel Shield failed.");
    echo "   -> PASS\n\n";

    // --- TEST 7: WARLORD'S THRONE (Synergy) ---
    // Bonus: +0.15 (15%) multiplier to ALL bonuses
    // Lvl 4 Throne = +60% bonus (0.60).
    //
    // Population (5 base) -> 5 * 1.6 = 8
    // Offense (0.05 base) -> 0.05 * 1.6 = 0.08
    
    $allyStructRepo->createOrUpgrade($allianceId, 'warlords_throne', 4);
    $powerCalc->clearCache();
    
    $inc = $powerCalc->calculateIncomePerTurn($userId, $r, $st, $str, $allianceId);
    $off = $powerCalc->calculateOffensePower($userId, $r, $st, $str, $allianceId);
    
    echo "7. Warlord's Throne (Lvl 4 = +60% Boost):\n";
    
    // Check Population
    echo "   - Population: Expected 8 | Actual {$inc['alliance_citizen_bonus']}\n";
    if ($inc['alliance_citizen_bonus'] !== 8) throw new Exception("Throne failed on Citizens.");
    
    // Check Offense
    echo "   - Offense %: Expected 0.08 | Actual {$off['alliance_bonus_pct']}\n";
    if (abs($off['alliance_bonus_pct'] - 0.08) > 0.001) throw new Exception("Throne failed on Offense.");
    
    echo "   -> PASS\n";

    // Cleanup
    $db->rollBack();
    echo "\n✅ SUCCESS: All 6 Alliance Structures are wired correctly.\n";

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "\n❌ TEST FAILED: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}