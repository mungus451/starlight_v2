<?php

// Import our controller and middleware namespaces
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\BankController; // NEW
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

    // --- NEW: Phase 3: Bank Routes ---
    $r->addRoute('GET', '/bank', [BankController::class, 'show']);
    $r->addRoute('POST', '/bank/deposit', [BankController::class, 'handleDeposit']);
    $r->addRoute('POST', '/bank/withdraw', [BankController::class, 'handleWithdraw']);
    $r->addRoute('POST', '/bank/transfer', [BankController::class, 'handleTransfer']);
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
            // Define which routes are protected
            $protectedRoutes = [
                '/dashboard',
                '/bank',
                '/bank/deposit',
                '/bank/withdraw',
                '/bank/transfer'
            ];

            if (in_array($uri, $protectedRoutes)) {
                // This will run handle(), which redirects and exits if not logged in
                (new AuthMiddleware())->handle();
            }
            // --- End Middleware Check ---

            [$class, $method] = $handler;
            $controller = new $class();
            
            call_user_func_array([$controller, $method], $vars);
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