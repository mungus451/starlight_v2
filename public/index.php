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
use App\Controllers\LevelUpController;
use App\Controllers\AllianceController;
use App\Controllers\AllianceManagementController;
use App\Controllers\AllianceRoleController;
use App\Controllers\AllianceStructureController;
use App\Controllers\AllianceForumController;
use App\Controllers\DiplomacyController;
use App\Controllers\WarController;
use App\Controllers\ArmoryController;
use App\Controllers\PagesController;
use App\Controllers\ProfileController;
use App\Controllers\FileController;
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
    $r->addRoute('GET', '/', [PagesController::class, 'showHome']);
    $r->addRoute('GET', '/contact', [PagesController::class, 'showContact']);

    // --- Phase 1: Auth Routes ---
    $r->addRoute('GET', '/login', [AuthController::class, 'showLogin']);
    $r->addRoute('POST', '/login', [AuthController::class, 'handleLogin']);
    $r->addRoute('GET', '/register', [AuthController::class, 'showRegister']);
    $r->addRoute('POST', '/register', [AuthController::class, 'handleRegister']);
    $r->addRoute('GET', '/logout', [AuthController::class, 'handleLogout']);

    // --- Phase 2: Dashboard ---
    $r->addRoute('GET', '/dashboard', [DashboardController::class, 'show']);

    // --- Public Profile Route ---
    $r->addRoute('GET', '/profile/{id:\d+}', [ProfileController::class, 'show']);

    // --- Secure File Serving Route ---
    $r->addRoute('GET', '/serve/avatar/{filename}', [FileController::class, 'showAvatar']);

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

    // --- Armory Routes ---
    $r->addRoute('GET', '/armory', [ArmoryController::class, 'show']);
    $r->addRoute('POST', '/armory/manufacture', [ArmoryController::class, 'handleManufacture']);
    $r->addRoute('POST', '/armory/equip', [ArmoryController::class, 'handleEquip']);

    // --- Phase 6: Settings Routes ---
    $r->addRoute('GET', '/settings', [SettingsController::class, 'show']);
    $r->addRoute('POST', '/settings/profile', [SettingsController::class, 'handleProfile']);
    $r->addRoute('POST', '/settings/email', [SettingsController::class, 'handleEmail']);
    $r->addRoute('POST', '/settings/password', [SettingsController::class, 'handlePassword']);
    $r->addRoute('POST', '/settings/security', [SettingsController::class, 'handleSecurity']);

    // --- Phase 7: Spy Routes ---
    $r->addRoute('GET', '/spy', [SpyController::class, 'show']);
    $r->addRoute('GET', '/spy/page/{page:\d+}', [SpyController::class, 'show']);
    $r->addRoute('POST', '/spy/conduct', [SpyController::class, 'handleSpy']);
    $r->addRoute('GET', '/spy/reports', [SpyController::class, 'showReports']);
    $r->addRoute('GET', '/spy/report/{id:\d+}', [SpyController::class, 'showReport']);

    // --- Phase 8: Battle Routes ---
    $r->addRoute('GET', '/battle', [BattleController::class, 'show']);
    $r->addRoute('GET', '/battle/page/{page:\d+}', [BattleController::class, 'show']);
    $r->addRoute('POST', '/battle/attack', [BattleController::class, 'handleAttack']);
    $r->addRoute('GET', '/battle/reports', [BattleController::class, 'showReports']);
    $r->addRoute('GET', '/battle/report/{id:\d+}', [BattleController::class, 'showReport']);

    // --- Phase 9: Level Up Routes ---
    $r->addRoute('GET', '/level-up', [LevelUpController::class, 'show']);
    $r->addRoute('POST', '/level-up/spend', [LevelUpController::class, 'handleSpend']);

    // --- Phase 11: Alliance Routes ---
    $r->addRoute('GET', '/alliance/list', [AllianceController::class, 'showList']);
    $r->addRoute('GET', '/alliance/list/page/{page:\d+}', [AllianceController::class, 'showList']);
    $r->addRoute('GET', '/alliance/profile/{id:\d+}', [AllianceController::class, 'showProfile']);
    $r->addRoute('GET', '/alliance/create', [AllianceController::class, 'showCreateForm']);
    $r->addRoute('POST', '/alliance/create', [AllianceController::class, 'handleCreate']);

    // --- Phase 12: Alliance Management Routes ---
    $r->addRoute('POST', '/alliance/apply/{id:\d+}', [AllianceManagementController::class, 'handleApply']);
    $r->addRoute('POST', '/alliance/cancel-app/{id:\d+}', [AllianceManagementController::class, 'handleCancelApp']);
    $r->addRoute('POST', '/alliance/leave', [AllianceManagementController::class, 'handleLeave']);
    $r->addRoute('POST', '/alliance/accept-app/{id:\d+}', [AllianceManagementController::class, 'handleAcceptApp']);
    $r->addRoute('POST', '/alliance/reject-app/{id:\d+}', [AllianceManagementController::class, 'handleRejectApp']);
    $r->addRoute('POST', '/alliance/invite/{id:\d+}', [AllianceManagementController::class, 'handleInvite']);
    $r->addRoute('POST', '/alliance/donate', [AllianceManagementController::class, 'handleDonation']);
    
    // --- Phase 13: Alliance Admin Routes ---
    $r->addRoute('POST', '/alliance/profile/edit', [AllianceManagementController::class, 'handleUpdateProfile']);
    $r->addRoute('POST', '/alliance/kick/{id:\d+}', [AllianceManagementController::class, 'handleKickMember']);
    $r->addRoute('POST', '/alliance/role/assign', [AllianceManagementController::class, 'handleAssignRole']);
    $r->addRoute('GET', '/alliance/roles', [AllianceRoleController::class, 'showAll']);
    $r->addRoute('POST', '/alliance/roles/create', [AllianceRoleController::class, 'handleCreate']);
    $r->addRoute('POST', '/alliance/roles/update/{id:\d+}', [AllianceRoleController::class, 'handleUpdate']);
    $r->addRoute('POST', '/alliance/roles/delete/{id:\d+}', [AllianceManagementController::class, 'handleDelete']);
    
    // --- Alliance Structure Routes ---
    $r->addRoute('GET', '/alliance/structures', [AllianceStructureController::class, 'show']);
    $r->addRoute('POST', '/alliance/structures/upgrade', [AllianceStructureController::class, 'handleUpgrade']);
    
    // --- Alliance Forum Routes ---
    $r->addRoute('GET', '/alliance/forum', [AllianceForumController::class, 'showForum']);
    $r->addRoute('GET', '/alliance/forum/page/{page:\d+}', [AllianceForumController::class, 'showForum']);
    $r->addRoute('GET', '/alliance/forum/topic/create', [AllianceForumController::class, 'showCreateTopic']);
    $r->addRoute('POST', '/alliance/forum/topic/create', [AllianceForumController::class, 'handleCreateTopic']);
    $r->addRoute('GET', '/alliance/forum/topic/{id:\d+}', [AllianceForumController::class, 'showTopic']);
    $r->addRoute('POST', '/alliance/forum/topic/{id:\d+}/reply', [AllianceForumController::class, 'handleCreatePost']);
    $r->addRoute('POST', '/alliance/forum/topic/{id:\d+}/pin', [AllianceForumController::class, 'handlePinTopic']);
    $r->addRoute('POST', '/alliance/forum/topic/{id:\d+}/lock', [AllianceForumController::class, 'handleLockTopic']);
    
    // --- Alliance Loan Routes ---
    $r->addRoute('POST', '/alliance/loan/request', [AllianceManagementController::class, 'handleLoanRequest']);
    $r->addRoute('POST', '/alliance/loan/approve/{id:\d+}', [AllianceManagementController::class, 'handleLoanApprove']);
    $r->addRoute('POST', '/alliance/loan/deny/{id:\d+}', [AllianceManagementController::class, 'handleLoanDeny']);
    $r->addRoute('POST', '/alliance/loan/repay/{id:\d+}', [AllianceManagementController::class, 'handleLoanRepay']);

    // --- Alliance Diplomacy Routes ---
    $r->addRoute('GET', '/alliance/diplomacy', [DiplomacyController::class, 'show']);
    $r->addRoute('POST', '/alliance/diplomacy/treaty/propose', [DiplomacyController::class, 'handleProposeTreaty']);
    $r->addRoute('POST', '/alliance/diplomacy/treaty/accept/{id:\d+}', [DiplomacyController::class, 'handleAcceptTreaty']);
    $r->addRoute('POST', '/alliance/diplomacy/treaty/decline/{id:\d+}', [DiplomacyController::class, 'handleDeclineTreaty']);
    $r->addRoute('POST', '/alliance/diplomacy/treaty/break/{id:\d+}', [DiplomacyController::class, 'handleBreakTreaty']);
    $r->addRoute('POST', '/alliance/diplomacy/rivalry/declare', [DiplomacyController::class, 'handleDeclareRivalry']);
    
    // --- Alliance War Routes ---
    $r->addRoute('GET', '/alliance/war', [WarController::class, 'show']);
    $r->addRoute('POST', '/alliance/war/declare', [WarController::class, 'handleDeclareWar']);

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
                '/armory', '/armory/manufacture', '/armory/equip',
                '/settings', '/settings/profile', '/settings/email', '/settings/password', '/settings/security',
                '/spy', '/spy/conduct', '/spy/reports',
                '/battle', '/battle/attack', '/battle/reports',
                '/level-up', '/level-up/spend',
                '/alliance/list', '/alliance/create',
                '/alliance/leave',
                '/alliance/profile/edit',
                '/alliance/role/assign',
                '/alliance/roles',
                '/alliance/roles/create',
                '/alliance/donate',
                '/alliance/structures',
                '/alliance/structures/upgrade',
                '/alliance/forum',
                '/alliance/forum/topic/create',
                '/alliance/loan/request', // --- THIS IS THE ROUTE THAT CAUSED THE ERROR ---
                '/alliance/diplomacy',
                '/alliance/diplomacy/treaty/propose',
                '/alliance/diplomacy/rivalry/declare',
                '/alliance/war',
                '/alliance/war/declare'
            ];

            // Check exact routes
            $isProtected = in_array($uri, $protectedRoutes);

            // Check prefixed routes (for routes with parameters)
            if (!$isProtected) {
                if (str_starts_with($uri, '/spy/report/')) {
                    $isProtected = true;
                } elseif (str_starts_with($uri, '/spy/page/')) {
                    $isProtected = true;
                } elseif (str_starts_with($uri, '/battle/page/')) {
                    $isProtected = true;
                } elseif (str_starts_with($uri, '/battle/report/')) {
                    $isProtected = true;
                } elseif (str_starts_with($uri, '/alliance/list/page/')) {
                    $isProtected = true;
                } elseif (str_starts_with($uri, '/alliance/profile/')) { // Alliance profile
                    $isProtected = true;
                } elseif (str_starts_with($uri, '/alliance/apply/')) {
                    $isProtected = true;
                } elseif (str_starts_with($uri, '/alliance/cancel-app/')) {
                    $isProtected = true;
                } elseif (str_starts_with($uri, '/alliance/accept-app/')) {
                    $isProtected = true;
                } elseif (str_starts_with($uri, '/alliance/reject-app/')) {
                    $isProtected = true;
                } elseif (str_starts_with($uri, '/alliance/kick/')) {
                    $isProtected = true;
                } elseif (str_starts_with($uri, '/alliance/roles/update/')) {
                    $isProtected = true;
                } elseif (str_starts_with($uri, '/alliance/roles/delete/')) {
                    $isProtected = true;
                } elseif (str_starts_with($uri, '/profile/')) { // Player profile
                    $isProtected = true;
                } elseif (str_starts_with($uri, '/alliance/invite/')) {
                    $isProtected = true;
                } elseif (str_starts_with($uri, '/serve/avatar/')) {
                    $isProtected = true;
                } elseif (str_starts_with($uri, '/alliance/forum/page/')) {
                    $isProtected = true;
                } elseif (str_starts_with($uri, '/alliance/forum/topic/')) {
                    $isProtected = true;
                } elseif (str_starts_with($uri, '/alliance/loan/approve/')) {
                    $isProtected = true;
                } elseif (str_starts_with($uri, '/alliance/loan/deny/')) {
                    $isProtected = true;
                } elseif (str_starts_with($uri, '/alliance/loan/repay/')) {
                    $isProtected = true;
                } elseif (str_starts_with($uri, '/alliance/diplomacy/')) {
                    $isProtected = true;
                } elseif (str_starts_with($uri, '/alliance/war/')) {
                    $isProtected = true;
                }
            }
            // --- End Middleware Check ---

            if ($isProtected) {
                (new AuthMiddleware())->handle();
            }

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