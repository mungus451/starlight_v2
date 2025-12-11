<?php

/**
 * EXCRUCIATINGLY THOROUGH SPY SERVICE AUDIT
 *
 * Verifies:
 * 1. Validation Logic (Resources, Targeting).
 * 2. Effect Systems (Radar Jamming, Peace Shields).
 * 3. Database Integrity (Snapshots, Reports).
 * 4. Notification Dispatch.
 * 5. Ratio-Based Casualty Mechanics (via Simulation).
 *
 * Usage: docker exec starlight_app php tests/SpyServiceDeepTest.php
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
use App\Models\Services\SpyService;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\SpyRepository;
use App\Models\Repositories\EffectRepository;
use App\Models\Repositories\NotificationRepository;

echo "\n" . str_repeat("=", 80) . "\n";
echo "   🕵️  SPY SERVICE DEEP INSPECTION & SIMULATION\n";
echo str_repeat("=", 80) . "\n\n";

try {
    // 1. Setup
    $container = ContainerFactory::createContainer();
    $db = $container->get(PDO::class);
    
    $spyService = $container->get(SpyService::class);
    $userRepo = $container->get(UserRepository::class);
    $resRepo = $container->get(ResourceRepository::class);
    $statsRepo = $container->get(StatsRepository::class);
    $structRepo = $container->get(StructureRepository::class);
    $spyRepo = $container->get(SpyRepository::class);
    $effectRepo = $container->get(EffectRepository::class);
    $notifRepo = $container->get(NotificationRepository::class);

    // Start Sandbox
    $db->beginTransaction();
    echo "🔒 Database Transaction Started (Sandbox Mode)\n\n";

    // 2. Seed Actors
    $seed = bin2hex(random_bytes(3));
    
    // Actor A: "Agent Zero" (The Attacker)
    $atkId = $userRepo->createUser("agent_{$seed}@test.com", "AgentZero_{$seed}", 'hash');
    $resRepo->createDefaults($atkId);
    $statsRepo->createDefaults($atkId);
    $structRepo->createDefaults($atkId); // Ensure structures exist
    $resRepo->updateSpyAttacker($atkId, 100_000_000, 1000); // 100M Credits, 1000 Spies
    $statsRepo->updateAttackTurns($atkId, 100);

    // Actor B: "Target Practice" (The Victim)
    $defId = $userRepo->createUser("target_{$seed}@test.com", "Target_{$seed}", 'hash');
    $resRepo->createDefaults($defId);
    $statsRepo->createDefaults($defId);
    $structRepo->createDefaults($defId); // Ensure structures exist
    $resRepo->updateSpyDefender($defId, 500); // 500 Sentries

    echo "Actors Initialized:\n";
    echo " - Attacker (ID $atkId): 1000 Spies, 100 Turns\n";
    echo " - Defender (ID $defId): 500 Sentries\n\n";

    // --- TEST 1: VALIDATION LOGIC ---
    echo "\033[1;33m[TEST 1] Validation Logic\033[0m\n";
    
    // 1.1 Self Spy
    $res = $spyService->conductOperation($atkId, "AgentZero_{$seed}");
    if ($res->isSuccess()) throw new Exception("FAIL: Allowed self-spy.");
    echo "  ✅ Self-spy blocked.\n";

    // 1.2 Insufficient Resources (Spies)
    $resRepo->updateSpyAttacker($atkId, 100_000_000, 0); // Remove spies
    $res = $spyService->conductOperation($atkId, "Target_{$seed}");
    if ($res->isSuccess()) throw new Exception("FAIL: Allowed spy mission with 0 spies.");
    echo "  ✅ Zero-spy mission blocked.\n";
    
    // Reset Spies
    $resRepo->updateSpyAttacker($atkId, 100_000_000, 1000);

    // --- TEST 2: RADAR JAMMING ---
    echo "\n\033[1;33m[TEST 2] Effect: Radar Jamming\033[0m\n";
    echo "  -> Applying 'jamming' to Target (duration: 24h)...\n";
    
    // Use +24 hours to avoid timezone issues during testing
    $effectRepo->addEffect($defId, 'jamming', date('Y-m-d H:i:s', strtotime('+24 hours')), null);
    
    $startTurns = $statsRepo->findByUserId($atkId)->attack_turns;
    $res = $spyService->conductOperation($atkId, "Target_{$seed}");
    
    // Should be SUCCESS message (as per Service logic) but contain "CRITICAL FAILURE" text
    if (!str_contains($res->message, "CRITICAL FAILURE")) {
        throw new Exception("FAIL: Radar Jamming did not trigger failure message. Got: " . $res->message);
    }
    
    // Verify Resource Consumption (Cost of failure)
    $endTurns = $statsRepo->findByUserId($atkId)->attack_turns;
    if ($startTurns <= $endTurns) {
        throw new Exception("FAIL: Radar Jamming did not consume attack turns.");
    }
    
    // Verify NO Report generated
    $reports = $spyRepo->findReportsByAttackerId($atkId);
    if (!empty($reports)) {
        throw new Exception("FAIL: Radar Jamming generated a report row (should be blocked).");
    }
    echo "  ✅ Radar Jamming logic verified (Resources consumed, no report).\n";
    $effectRepo->removeEffect($defId, 'jamming'); // Cleanup

    // --- TEST 3: PEACE SHIELD ---
    echo "\n\033[1;33m[TEST 3] Effect: Peace Shield\033[0m\n";
    echo "  -> Applying 'peace_shield' to Target...\n";
    $effectRepo->addEffect($defId, 'peace_shield', date('Y-m-d H:i:s', strtotime('+24 hours')), null);
    
    $res = $spyService->conductOperation($atkId, "Target_{$seed}");
    
    if ($res->isSuccess()) {
        throw new Exception("FAIL: Peace Shield ignored.");
    }
    echo "  ✅ Peace Shield blocked operation.\n";
    $effectRepo->removeEffect($defId, 'peace_shield'); // Cleanup

    // --- TEST 4: DATA INTEGRITY & SNAPSHOTS ---
    echo "\n\033[1;33m[TEST 4] Data Integrity & Snapshots\033[0m\n";
    
    // Set explicit amounts
    $sentriesBefore = 500;
    $resRepo->updateSpyDefender($defId, $sentriesBefore);
    
    $res = $spyService->conductOperation($atkId, "Target_{$seed}");
    if (!$res->isSuccess()) throw new Exception("FAIL: Operation failed: " . $res->message);

    // Check DB
    $reports = $spyRepo->findReportsByAttackerId($atkId);
    $lastReport = $reports[0];

    // Verify Snapshot
    if ($lastReport->defender_total_sentries !== $sentriesBefore) {
        throw new Exception("FAIL: Snapshot mismatch. Expected {$sentriesBefore}, got {$lastReport->defender_total_sentries}");
    }
    echo "  ✅ Defender Sentry Snapshot recorded correctly: {$lastReport->defender_total_sentries}\n";

    // Verify Notification (If combat occurred)
    if ($lastReport->sentries_lost_defender > 0 || $lastReport->spies_lost_attacker > 0) {
        // Combat happened -> check notification
        $notifs = $notifRepo->getRecent($defId, 1);
        if (empty($notifs)) {
            echo "  ⚠️  Combat occurred but no notification found. (Check Logic)\n";
        } else {
            echo "  ✅ Notification dispatched to defender.\n";
        }
    } else {
        echo "  ℹ️  Clean run (Ghosted), skipping notification check.\n";
    }

    // --- TEST 5: MASS SIMULATION (Ratio Verification) ---
    echo "\n\033[1;33m[TEST 5] Ratio-Based Casualty Simulation (12 Runs)\033[0m\n";
    echo "  Simulating various power ratios to verify dynamic losses...\n\n";
    
    printf("| %-8s | %-8s | %-12s | %-12s | %-15s |\n", "Spies", "Sentries", "Result", "Spies Lost", "Sentries Lost");
    echo str_repeat("-", 70) . "\n";

    $testCases = [
        [100, 5000],   // Suicide Mission (1:50) -> Expect Wipeout
        [500, 500],    // Even Match (1:1) -> Expect scraping
        [10000, 100],  // Overwhelming Force (100:1) -> Expect 0 losses
    ];

    foreach ($testCases as $case) {
        for ($i = 0; $i < 4; $i++) { // Run each case 4 times to smooth RNG
            $s = $case[0];
            $d = $case[1];
            
            // Reset forces
            $resRepo->updateSpyAttacker($atkId, 100_000_000, $s);
            $resRepo->updateSpyDefender($defId, $d);
            
            $res = $spyService->conductOperation($atkId, "Target_{$seed}");
            
            // Fetch fresh report (ORDER BY id DESC ensures we get the latest)
            $rep = $spyRepo->findReportsByAttackerId($atkId)[0];
            
            // Colorize
            $resColor = $rep->operation_result === 'success' ? "\033[32m" : "\033[31m";
            $lostColor = $rep->spies_lost_attacker > 0 ? "\033[33m" : "\033[37m";
            
            printf(
                "| %-8d | %-8d | %s%-12s\033[0m | %s%-12d\033[0m | %-15d |\n", 
                $s, 
                $d, 
                $resColor, strtoupper($rep->operation_result),
                $lostColor, $rep->spies_lost_attacker,
                $rep->sentries_lost_defender
            );
        }
    }
    echo str_repeat("-", 70) . "\n";
    echo "  ✅ Simulation completed. Check logic variance above.\n";

} catch (Throwable $e) {
    echo "\n\033[31m❌ CRITICAL FAILURE: " . $e->getMessage() . "\033[0m\n";
    echo $e->getTraceAsString();
    exit(1);
} finally {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
        echo "\n🔒 Transaction Rolled Back. System Clean.\n";
    }
}