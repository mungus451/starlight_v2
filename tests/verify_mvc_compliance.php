<?php

// tests/verify_mvc_compliance.php

if (php_sapi_name() !== 'cli') {
    die('Access Denied: CLI only.');
}

require __DIR__ . '/../vendor/autoload.php';

echo "\n" . str_repeat("=", 60) . "\n";
echo "   STARLIGHT DOMINION: ARCHITECTURAL COMPLIANCE AUDIT\n";
echo str_repeat("=", 60) . "\n";

$projectRoot = realpath(__DIR__ . '/../');
$stats = ['checked' => 0, 'violations' => 0];

// --- DEFINITIONS ---

$layers = [
    'Services' => [
        'path' => $projectRoot . '/app/Models/Services',
        'forbidden_tokens' => [T_ECHO, T_PRINT, T_EXIT], // No output, no die()
        'forbidden_strings' => ['var_dump', 'dd('],
        'allowed_exceptions' => [] // Files to ignore if any
    ],
    'Views' => [
        'path' => $projectRoot . '/views',
        'forbidden_strings' => [
            'Database::getInstance', 
            'new PDO', 
            '->prepare(', 
            '->query(',
            'switch (' // We moved logic to Presenters, views should be dumb loops
        ], 
        'allowed_exceptions' => [
            'layouts/main.php' // Might contain some structural logic
        ]
    ],
    'Controllers' => [
        'path' => $projectRoot . '/app/Controllers',
        'forbidden_strings' => ['Database::getInstance', 'new PDO', 'echo json_encode'], // Controllers should return responses or render, not echo directly (except simplified AJAX for now)
        'allowed_exceptions' => [
            // NotificationController.php allowed to echo json_encode for AJAX endpoints currently
            'NotificationController.php',
            'ArmoryController.php', 
            'FileController.php' // Files stream content
        ]
    ]
];

// --- SCANNER LOGIC ---

foreach ($layers as $layerName => $rules) {
    echo "\nScanning {$layerName} Layer...\n";
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rules['path']));

    foreach ($files as $file) {
        if ($file->isDir() || $file->getExtension() !== 'php') continue;
        
        $filePath = $file->getRealPath();
        $fileName = $file->getFilename();
        
        // Check exceptions
        if (in_array($fileName, $rules['allowed_exceptions'] ?? [])) continue;

        $content = file_get_contents($filePath);
        $tokens = token_get_all($content);
        $violationsFound = [];

        // 1. Check Forbidden Tokens (PHP Internal Constants)
        if (!empty($rules['forbidden_tokens'])) {
            foreach ($tokens as $token) {
                if (is_array($token)) {
                    if (in_array($token[0], $rules['forbidden_tokens'])) {
                        $violationsFound[] = "Forbidden Token: " . token_name($token[0]);
                    }
                }
            }
        }

        // 2. Check Forbidden Strings (Literal code)
        if (!empty($rules['forbidden_strings'])) {
            foreach ($rules['forbidden_strings'] as $badString) {
                if (str_contains($content, $badString)) {
                    $violationsFound[] = "Forbidden String: '{$badString}'";
                }
            }
        }

        // Special Check for NpcService (should use Logger, not echo)
        if ($fileName === 'NpcService.php' && str_contains($content, 'echo ')) {
             $violationsFound[] = "Direct Output detected (Use Logger instead)";
        }

        // Report
        $stats['checked']++;
        if (!empty($violationsFound)) {
            // Deduplicate violations per file
            $violationsFound = array_unique($violationsFound);
            echo "  ❌ [FAIL] {$fileName}\n";
            foreach ($violationsFound as $v) {
                echo "      -> {$v}\n";
            }
            $stats['violations']++;
        } else {
            // echo "  ✅ [PASS] {$fileName}\n"; // Uncomment for verbose output
        }
    }
}

// --- PRESENTER VERIFICATION ---

echo "\nVerifying Presenter Layer Existence...\n";
$presenters = [
    'StructurePresenter.php',
    'BattleReportPresenter.php',
    'SpyReportPresenter.php'
];
foreach ($presenters as $p) {
    if (file_exists($projectRoot . '/app/Presenters/' . $p)) {
        echo "  ✅ [PASS] {$p} exists.\n";
    } else {
        echo "  ❌ [FAIL] {$p} MISSING.\n";
        $stats['violations']++;
    }
}

// --- SUMMARY ---

echo "\n" . str_repeat("-", 60) . "\n";
echo "Results: Scanned {$stats['checked']} files.\n";

if ($stats['violations'] === 0) {
    echo "✅ SUCCESS: Strict MVC Architecture Validated.\n";
    exit(0);
} else {
    echo "❌ FAILURE: {$stats['violations']} violations found.\n";
    exit(1);
}