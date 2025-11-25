<?php

// tests/verify_di_resolution.php

if (php_sapi_name() !== 'cli') {
    die('Access Denied: CLI only.');
}

require __DIR__ . '/../vendor/autoload.php';

// Load Env
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    // Ignore if missing for this test
}

use App\Core\ContainerFactory;

echo "\n" . str_repeat("=", 60) . "\n";
echo "   DEPENDENCY INJECTION RESOLUTION TEST\n";
echo "   Verifying Controller Constructors after Refactor\n";
echo str_repeat("=", 60) . "\n\n";

try {
    echo "[1/2] Building Container... ";
    $container = ContainerFactory::createContainer();
    echo "OK\n";

    // List of controllers to test
    $controllers = [
        App\Controllers\AuthController::class,
        App\Controllers\DashboardController::class,
        App\Controllers\ProfileController::class,
        App\Controllers\BankController::class,
        App\Controllers\TrainingController::class,
        App\Controllers\StructureController::class,
        App\Controllers\ArmoryController::class,
        App\Controllers\SettingsController::class,
        App\Controllers\SpyController::class,
        App\Controllers\BattleController::class,
        App\Controllers\LevelUpController::class,
        App\Controllers\AllianceController::class,
        App\Controllers\AllianceApplicationController::class,
        App\Controllers\AllianceFundingController::class,
        App\Controllers\AllianceMemberController::class,
        App\Controllers\AllianceRoleController::class,
        App\Controllers\AllianceSettingsController::class,
        App\Controllers\AllianceStructureController::class,
        App\Controllers\AllianceForumController::class,
        App\Controllers\DiplomacyController::class,
        App\Controllers\WarController::class,
        App\Controllers\CurrencyConverterController::class,
        App\Controllers\NotificationController::class,
        App\Controllers\PagesController::class,
        App\Controllers\FileController::class,
    ];

    echo "[2/2] Resolving Controllers...\n";
    
    $errors = 0;

    foreach ($controllers as $class) {
        $shortName = (new ReflectionClass($class))->getShortName();
        echo "      - Resolving {$shortName}... ";
        
        try {
            // This attempts to instantiate the class and inject all dependencies
            $instance = $container->get($class);
            echo "PASS\n";
        } catch (Throwable $e) {
            echo "FAIL\n";
            echo "        -> ERROR: " . $e->getMessage() . "\n";
            $errors++;
        }
    }

    echo "\n" . str_repeat("-", 60) . "\n";
    
    if ($errors === 0) {
        echo "✅ SUCCESS: All Controllers instantiated correctly.\n";
        echo "   The MVC Refactor is stable.\n";
        exit(0);
    } else {
        echo "❌ FAILURE: {$errors} controllers failed to load.\n";
        exit(1);
    }

} catch (Throwable $e) {
    echo "\nCRITICAL ERROR during test setup: " . $e->getMessage() . "\n";
    exit(1);
}