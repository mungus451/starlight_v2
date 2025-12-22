<?php

// tests/VerifySessionDecoupling.php

/**
 * ARCHITECTURAL TEST
 * Scans all Service classes to ensure strict separation of concerns.
 * Specifically validates that NO Service depends on the Session class.
 */

if (php_sapi_name() !== 'cli') {
    die('Access Denied: CLI only.');
}

require __DIR__ . '/../../vendor/autoload.php';

echo "\n" . str_repeat("=", 50) . "\n";
echo "   ARCHITECTURAL AUDIT: SESSION DECOUPLING\n";
echo str_repeat("=", 50) . "\n\n";

$servicesDir = __DIR__ . '/../../app/Models/Services';
$files = glob($servicesDir . '/*.php');
$violations = [];
$checked = 0;

foreach ($files as $file) {
    $className = 'App\\Models\\Services\\' . basename($file, '.php');
    
    if (!class_exists($className)) {
        echo "⚠️  Skipping $className (Class not loaded)\n";
        continue;
    }

    $reflection = new ReflectionClass($className);
    $constructor = $reflection->getConstructor();
    $hasViolation = false;

    if ($constructor) {
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            if ($type && !$type->isBuiltin()) {
                $typeName = $type->getName();
                // Check if it injects Session
                if ($typeName === 'App\Core\Session') {
                    $violations[] = $className;
                    $hasViolation = true;
                }
            }
        }
    }

    if ($hasViolation) {
        echo "❌ VIOLATION: $className depends on Session\n";
    } else {
        echo "✅ CLEAN:     $className\n";
    }
    $checked++;
}

echo "\n" . str_repeat("-", 50) . "\n";
echo "Audit Complete.\n";
echo "Services Checked: $checked\n";

if (count($violations) > 0) {
    echo "Violations Found: " . count($violations) . "\n";
    echo "FAIL: The architecture is not fully decoupled.\n";
    exit(1);
} else {
    echo "Violations Found: 0\n";
    echo "SUCCESS: Service Layer is fully decoupled from Session.\n";
    exit(0);
}