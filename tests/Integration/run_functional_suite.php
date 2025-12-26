--- START OF FILE tests/run_functional_suite.php ---

<?php

/**
 * STARLIGHT DOMINION: FUNCTIONAL TEST SUITE
 *
 * Runs logic-heavy simulation tests to verify Game Balance, Math, and Database Integrations.
 * Unlike the Compliance Suite (which checks structure), this checks BEHAVIOR.
 *
 * EXECUTES:
 * 1. AllianceStructureBonusTest.php (Math/Formulas)
 * 2. BattleSimulationTest.php (Entity State/Combat Logic)
 * 3. verify_refactor.php (End-to-End War Integration)
 *
 * Usage: php tests/run_functional_suite.php
 */

if (php_sapi_name() !== 'cli') {
    die('Access Denied: CLI only.');
}

// Configuration
$tests = [
    'Alliance Structure Math' => __DIR__ . '/AllianceStructureBonusCheck.php',
    'Battle Logic Simulation' => __DIR__ . '/../Simulations/BattleSimulation.php',
    'War Integration (E2E)'   => __DIR__ . '/../Compliance/verify_refactor.php',
];

$startTime = microtime(true);
$failures = 0;

echo "\n" . str_repeat("=", 70) . "\n";
echo "   STARLIGHT DOMINION V2 - FUNCTIONAL LOGIC SUITE\n";
echo str_repeat("=", 70) . "\n\n";

foreach ($tests as $name => $path) {
    echo str_repeat("-", 70) . "\n";
    echo ">> TEST: {$name}\n";
    echo str_repeat("-", 70) . "\n";

    if (!file_exists($path)) {
        echo "\033[31m[ERROR] Test file not found: {$path}\033[0m\n";
        $failures++;
        continue;
    }

    // Pass output directly to console
    passthru(PHP_BINARY . " " . escapeshellarg($path), $returnCode);

    if ($returnCode !== 0) {
        echo "\n\033[31m>> RESULT: FAILED (Exit Code: {$returnCode})\033[0m\n\n";
        $failures++;
    } else {
        echo "\n\033[32m>> RESULT: PASSED\033[0m\n\n";
    }
}

// Summary
$duration = round(microtime(true) - $startTime, 2);

echo str_repeat("=", 70) . "\n";
echo "   FUNCTIONAL SUITE COMPLETE in {$duration}s\n";
echo str_repeat("=", 70) . "\n";

if ($failures === 0) {
    echo "\033[32m   ALL SYSTEMS OPERATIONAL. GAME LOGIC IS STABLE.\033[0m\n";
    exit(0);
} else {
    echo "\033[31m   {$failures} CRITICAL LOGIC FAILURES DETECTED.\033[0m\n";
    exit(1);
}