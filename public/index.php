<?php

// Import our controller and middleware namespaces
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\BankController;
use App\Controllers\TrainingController;
use App\Controllers\StructureController;
use App\Controllers\SettingsController;
use App\Controllers\SpyController;
use App\Controllers\BattleController;
use App\Controllers\LevelUpController; // NEW
use App\Middleware\AuthMiddleware;

// Start the session, which will be needed for authentication
session_start();

// 1. Autoloader
require __DIR__ . '/../vendor/autoload.php';

// 2. Environment Variables
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    die('Could not find .env file. Please copy .env.example to .env and configure it.');
}

// 3. Error Reporting
if (($_ENV['APP_ENV'] ?? 'development') === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}

// 4. Router Definition
$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
    
    // Landing Page Route
    $r->addRoute('GET', '/', [AuthController::class, 'showLogin']);

    // --- Phase 1: Auth Routes ---
    $r->addRoute('GET', '/login', [AuthController::class, 'showLogin']);
    $r->addRoute('POST', '/login', [AuthController::class, 'handleLogin']);
    $r->addRoute('GET', '/register', [AuthController::class, 'showRegister']);
    $r->addRoute('POST', '/register', [AuthController::class, 'handleRegister']);
    $r->addRoute('GET', '/logout', [AuthController::class, 'handleLogout']);

    // --- Phase 2: Dashboard ---
    $r->addRoute('GET', '/dashboard', [DashboardController::class, 'show']);

    // --- Phase 3: Bank Routes ---
    $r->addRoute('GET', '/bank', [BankController::class, 'show']);
    $r->addRoute('POST', '/bank/deposit', [BankController::class, 'handleDeposit']);
    $r->addRoute('POST', '/bank/withdraw', [BankController::class, 'handleWithdraw']);
    $r->addRoute('POST', '/bank/transfer', [BankController::class, 'handleTransfer']);

    // --- Phase 4: Training Routes ---
    $r->addRoute('GET', '/training', [TrainingController::class, 'show']);
    $r->addRoute('POST', '/training/train', [TrainingController::class, 'handleTrain']);

    // --- Phase 5: Structures Routes ---
    $r->addRoute('GET', '/structures', [StructureController::class, 'show']);
    $r->addRoute('POST', '/structures/upgrade', [StructureController::class, 'handleUpgrade']);

    // --- Phase 6: Settings Routes ---
    $r->addRoute('GET', '/settings', [SettingsController::class, 'show']);
    $r->addRoute('POST', '/settings/profile', [SettingsController::class, 'handleProfile']);
    $r->addRoute('POST', '/settings/email', [SettingsController::class, 'handleEmail']);
    $r->addRoute('POST', '/settings/password', [SettingsController::class, 'handlePassword']);
    $r->addRoute('POST', '/settings/security', [SettingsController::class, 'handleSecurity']);

    // --- Phase 7: Spy Routes ---
    $r->addRoute('GET', '/spy', [SpyController::class, 'show']);
    $r->addRoute('POST', '/spy/conduct', [SpyController::class, 'handleSpy']);
    $r->addRoute('GET', '/spy/reports', [SpyController::class, 'showReports']);
    $r->addRoute('GET', '/spy/report/{id:\d+}', [SpyController::class, 'showReport']);

    // --- Phase 8: Battle Routes ---
    $r->addRoute('GET', '/battle', [BattleController::class, 'show']);
    $r->addRoute('GET', '/battle/page/{page:\d+}', [BattleController::class, 'show']);
    $r->addRoute('POST', '/battle/attack', [BattleController::class, 'handleAttack']);
    $r->addRoute('GET', '/battle/reports', [BattleController::class, 'showReports']);
    $r->addRoute('GET', '/battle/report/{id:\d+}', [BattleController::class, 'showReport']);

    // --- NEW: Phase 9: Level Up Routes ---
    $r->addRoute('GET', '/level-up', [LevelUpController::class, 'show']);
    $r->addRoute('POST', '/level-up/spend', [LevelUpController::class, 'handleSpend']);
});

// 5. Global Error Handler
try {
    // 6. Router Dispatch
    $httpMethod = $_SERVER['REQUEST_METHOD'];
    $uri = $_SERVER['REQUEST_URI'];

    if (false !== $pos = strpos($uri, '?')) {
        $uri = substr($uri, 0, $pos);
    }
    $uri = rawurldecode($uri);

    $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            http_response_code(404);
            echo '404 - Page Not Found';
            break;

        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            http_response_code(405);
            echo '405 - Method Not Allowed';
            break;

        case FastRoute\Dispatcher::FOUND:
            $handler = $routeInfo[1];
            $vars = $routeInfo[2];

            // --- UPDATED: Middleware Check ---
            $protectedRoutes = [
                '/dashboard',
                '/bank', '/bank/deposit', '/bank/withdraw', '/bank/transfer',
                '/training', '/training/train',
                '/structures', '/structures/upgrade',
                '/settings', '/settings/profile', '/settings/email', '/settings/password', '/settings/security',
                '/spy', '/spy/conduct', '/spy/reports',
                '/battle', '/battle/attack', '/battle/reports',
                '/level-up', '/level-up/spend' // NEW
            ];

            // Check exact routes
            $isProtected = in_array($uri, $protectedRoutes);

            // Check prefixed routes (for routes with parameters)
            if (!$isProtected) {
                if (str_starts_with($uri, '/spy/report/')) {
                    $isProtected = true;
                } elseif (str_starts_with($uri, '/battle/page/')) {
                    $isProtected = true;
                } elseif (str_starts_with($uri, '/battle/report/')) {
                    $isProtected = true;
                }
            }

            if ($isProtected) {
                (new AuthMiddleware())->handle();
            }
            // --- End Middleware Check ---

            [$class, $method] = $handler;
            $controller = new $class();
            
            call_user_func_array([$controller, $method], [$vars]);
            break;
    }
} catch (\Throwable $e) {
    // Global exception handler
    http_response_code(500);
    if (($_ENV['APP_ENV'] ?? 'development') === 'development') {
        echo '<h1>Application Error:</h1>';
        echo '<pre>' . $e->getMessage() . '</pre>';
        echo '<pre>File: ' . $e->getFile() . ' on line ' . $e->getLine() . '</pre>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    } else {
        echo 'An unexpected error occurred. Please try again later.';
    }
}