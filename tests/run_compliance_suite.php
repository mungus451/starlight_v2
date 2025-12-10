--- START OF FILE tests/run_compliance_suite.php ---

<?php

/**
 * STARLIGHT DOMINION: MASTER COMPLIANCE SUITE
 *
 * This script acts as a test runner, executing all architectural and integrity
 * checks in sequence. It replaces the legacy 'verify_mvc_compliance.php'.
 *
 * EXECUTES:
 * 1. StrictArchitectureAudit.php (Code Rules & MVC Purity)
 * 2. mvc_lint.php (Pattern Matching & Heuristics)
 * 3. VerifySystemIntegrity.php (File Existence & Wiring)
 * 4. VerifySessionDecoupling.php (Specific Service Isolation)
 * 5. verify_di_resolution.php (Container Health)
 *
 * Usage: php tests/run_compliance_suite.php
 */

if (php_sapi_name() !== 'cli') {
    die('Access Denied: CLI only.');
}

// Configuration
$tests = [
    'Strict Audit' => __DIR__ . '/StrictArchitectureAudit.php',
    'MVC Lint' => __DIR__ . '/mvc_lint.php',
    'System Integrity' => __DIR__ . '/VerifySystemIntegrity.php',
    'Session Decoupling' => __DIR__ . '/VerifySessionDecoupling.php',
    'DI Resolution' => __DIR__ . '/verify_di_resolution.php',
];

$startTime = microtime(true);
$failures = 0;

echo "\n" . str_repeat("=", 70) . "\n";
echo "   STARLIGHT DOMINION V2 - COMPLIANCE SUITE RUNNER\n";
echo str_repeat("=", 70) . "\n\n";

foreach ($tests as $name => $path) {
    echo str_repeat("-", 70) . "\n";
    echo ">> RUNNING: {$name}...\n";
    echo str_repeat("-", 70) . "\n";

    if (!file_exists($path)) {
        echo "\033[31m[ERROR] Test file not found: {$path}\033[0m\n";
        $failures++;
        continue;
    }

    // Execute the test script via passthru to preserve its own output/colors
    // and isolate its memory scope.
    passthru(PHP_BINARY . " " . escapeshellarg($path), $returnCode);

    if ($returnCode !== 0) {
        echo "\n\033[31m>> {$name}: FAILED (Exit Code: {$returnCode})\033[0m\n\n";
        $failures++;
    } else {
        echo "\n\033[32m>> {$name}: PASSED\033[0m\n\n";
    }
}

// Summary
$duration = round(microtime(true) - $startTime, 2);

echo str_repeat("=", 70) . "\n";
echo "   SUITE COMPLETE in {$duration}s\n";
echo str_repeat("=", 70) . "\n";

if ($failures === 0) {
    echo "\033[32m   ALL CHECKS PASSED. ARCHITECTURE IS STABLE.\033[0m\n";
    exit(0);
} else {
    echo "\033[31m   {$failures} CHECKS FAILED. REVIEW LOGS ABOVE.\033[0m\n";
    exit(1);
}