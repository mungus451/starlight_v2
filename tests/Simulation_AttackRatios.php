<?php

/**
 * ATTACK RATIO SIMULATION TEST
 *
 * Simulates 10 battles with increasing power disparity to verify:
 * 1. Ratio-based casualty logic (Winner losses should decrease as ratio increases).
 * 2. Wipeout logic (Ratio > 10 should result in 100% defender losses).
 * 3. Minimum casualty logic (Loser should always lose at least 1 unit).
 *
 * Usage: php tests/Simulation_AttackRatios.php
 */

if (php_sapi_name() !== 'cli') {
    die('Access Denied: CLI only.');
}

require __DIR__ . '/../vendor/autoload.php';

// Load Env
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (\Exception $e) {
    die("Error: Could not find .env file.\n");
}

use App\Core\ContainerFactory;
use App\Models\Services\AttackService;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\BattleRepository;

echo "\n" . str_repeat("=", 80) . "\n";
echo "   COMBAT SIMULATION: RATIO SCALING & CASUALTIES\n";
echo str_repeat("=", 80) . "\n\n";

try {
    // 1. Setup Container & Dependencies
    $container = ContainerFactory::createContainer();
    $db = $container->get(PDO::class);
    
    $attackService = $container->get(AttackService::class);
    $userRepo = $container->get(UserRepository::class);
    $resRepo = $container->get(ResourceRepository::class);
    $statsRepo = $container->get(StatsRepository::class);
    $structRepo = $container->get(StructureRepository::class);
    $battleRepo = $container->get(BattleRepository::class);

    // 2. Start Transaction (Sandbox Mode)
    $db->beginTransaction();
    echo "🔒 Database Transaction Started (Sandbox Mode)\n\n";

    // 3. Define Test Scenarios
    // [Attacker Soldiers, Defender Guards, Label]
    $scenarios = [
        [100, 100, "Even Match (1:1)"],
        [150, 100, "Slight Edge (1.5:1)"],
        [200, 100, "Double Power (2:1)"],
        [300, 100, "Triple Power (3:1)"],
        [500, 100, "Dominant (5:1)"],
        [900, 100, "Near Wipeout (9:1)"],
        [1000, 100, "Wipeout Threshold (10:1)"],
        [2000, 100, "Overkill (20:1)"],
        [10000, 5, "Massive Blowout (2000:1) - The '2 Guard' Bug Check"],
        [100, 1000, "Defeat Test (1:10) - Attacker should be wiped"]
    ];

    // Table Header
    printf(
        "| %-20s | %-10s | %-10s | %-8s | %-12s | %-12s |\n", 
        "Scenario", "Atk Units", "Def Units", "Ratio", "Atk Lost", "Def Lost"
    );
    echo str_repeat("-", 90) . "\n";

    foreach ($scenarios as $index => $scene) {
        $atkSoldiers = $scene[0];
        $defGuards = $scene[1];
        $label = $scene[2];
        $seed = bin2hex(random_bytes(4));

        // Create Fresh Users for Clean Data
        $atkId = $userRepo->createUser("sim_atk_{$index}_{$seed}@test.com", "Atk_{$index}", 'hash');
        $defId = $userRepo->createUser("sim_def_{$index}_{$seed}@test.com", "Def_{$index}", 'hash');

        // Initialize Data
        $resRepo->createDefaults($atkId);
        $statsRepo->createDefaults($atkId);
        $structRepo->createDefaults($atkId);
        
        $resRepo->createDefaults($defId);
        $statsRepo->createDefaults($defId);
        $structRepo->createDefaults($defId);

        // Set Army Sizes
        $resRepo->updateBattleAttacker($atkId, 10000000, $atkSoldiers); // Credits, Soldiers
        $statsRepo->updateAttackTurns($atkId, 100); // Give plenty of turns
        $resRepo->updateBattleDefender($defId, 10000000, $defGuards); // Credits, Guards

        // Execute Attack
        // We calculate expected ratio roughly here (assuming 1 power per unit base)
        // Note: Real logic includes stats/structures, but fresh users are mostly base stats.
        $approxRatio = $atkSoldiers / max(1, $defGuards);
        
        $targetUser = $userRepo->findById($defId);
        $response = $attackService->conductAttack($atkId, $targetUser->characterName, 'plunder');

        if (!$response->isSuccess()) {
            echo "❌ Error in Scenario {$index}: " . $response->message . "\n";
            continue;
        }

        // Fetch Report to get actual numbers
        $report = $battleRepo->findReportsByAttackerId($atkId)[0]; // Get most recent

        // Determine colors based on result
        $atkColor = $report->attacker_soldiers_lost === 0 ? "\033[32m" : "\033[33m"; // Green if perfect, Yellow if loss
        $defColor = $report->defender_guards_lost >= $defGuards ? "\033[31m" : "\033[33m"; // Red if wiped out

        // Calculate actual ratio used by logic
        $actualOffense = $report->attacker_offense_power;
        $actualDefense = $report->defender_defense_power;
        $actualRatio = ($actualOffense > $actualDefense) 
            ? $actualOffense / max(1, $actualDefense) 
            : $actualDefense / max(1, $actualOffense);
        
        // Print Row
        printf(
            "| %-20s | %-10s | %-10s | %-8.1f | %s%-12s\033[0m | %s%-12s\033[0m |\n",
            substr($label, 0, 20),
            number_format($atkSoldiers),
            number_format($defGuards),
            $actualRatio,
            $atkColor,
            number_format($report->attacker_soldiers_lost) . " (" . round(($report->attacker_soldiers_lost/$atkSoldiers)*100,1) . "%)",
            $defColor,
            number_format($report->defender_guards_lost) . " (" . round(($report->defender_guards_lost/$defGuards)*100,1) . "%)"
        );
    }

    echo str_repeat("-", 90) . "\n";

} catch (Throwable $e) {
    echo "\n\033[31mCRITICAL ERROR:\033[0m " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
} finally {
    // 4. Rollback
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
        echo "\n🔒 Transaction Rolled Back. No data saved.\n";
    }
}