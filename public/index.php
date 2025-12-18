<?php

/**
 * Starlight Dominion V2 - Entry Point
 * * This file handles the bootstrapping of the application:
 * 1. Autoloading
 * 2. Environment Variables
 * 3. Dependency Injection Container
 * 4. Session Setup (Now via Redis)
 * 5. Routing & Dispatching
 */

use App\Core\ContainerFactory;
use App\Core\RedisSessionHandler;
use App\Controllers\PagesController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ProfileController;
use App\Controllers\FileController;
use App\Controllers\BankController;
use App\Controllers\TrainingController;
use App\Controllers\StructureController;
use App\Controllers\ArmoryController;
use App\Controllers\SettingsController;
use App\Controllers\SpyController;
use App\Controllers\BattleController;
use App\Controllers\LevelUpController;
use App\Controllers\AllianceController;
// --- Refactored Alliance Controllers ---
use App\Controllers\AllianceApplicationController;
use App\Controllers\AllianceFundingController;
use App\Controllers\AllianceMemberController;
use App\Controllers\AllianceSettingsController;
// ---------------------------------------
use App\Controllers\AllianceRoleController;
use App\Controllers\AllianceStructureController;
use App\Controllers\AllianceForumController;
use App\Controllers\DiplomacyController;
use App\Controllers\WarController;
use App\Controllers\CurrencyConverterController;
use App\Controllers\NotificationController;
use App\Controllers\LeaderboardController;
use App\Controllers\BlackMarketController;
use App\Controllers\EmbassyController;
use App\Middleware\AuthMiddleware;

use App\Core\Exceptions\RedirectException;
use App\Core\Exceptions\TerminateException;

// 1. Autoloader
require __DIR__ . '/../vendor/autoload.php';

// 2. Environment Variables
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    throw new \Exception('Could not find .env file.');
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

// 4. Build Dependency Injection Container
try {
    $container = ContainerFactory::createContainer();
} catch (Exception $e) {
    throw new \Exception('CRITICAL: Failed to initialize application container. ' . $e->getMessage());
}

// 5. Setup Redis Session Handler
try {
    $redis = $container->get(Predis\Client::class);
    $handler = new RedisSessionHandler($redis);
    
    // Register the custom handler
    session_set_save_handler($handler, true);
    
    // Now start the session
    session_start();
} catch (Exception $e) {
    throw new \Exception('CRITICAL: Session storage unavailable. ' . $e->getMessage());
}

// 6. Router Definition
$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
    
    // --- Public Pages ---
    $r->addRoute('GET', '/', [PagesController::class, 'showHome']);
    $r->addRoute('GET', '/contact', [PagesController::class, 'showContact']);

    // --- Authentication ---
    $r->addRoute('GET', '/login', [AuthController::class, 'showLogin']);
    $r->addRoute('POST', '/login', [AuthController::class, 'handleLogin']);
    $r->addRoute('GET', '/register', [AuthController::class, 'showRegister']);
    $r->addRoute('POST', '/register', [AuthController::class, 'handleRegister']);
    $r->addRoute('GET', '/logout', [AuthController::class, 'handleLogout']);

    // --- Core Game Features ---
    $r->addRoute('GET', '/dashboard', [DashboardController::class, 'show']);
    $r->addRoute('GET', '/profile/{id:\d+}', [ProfileController::class, 'show']);
    $r->addRoute('GET', '/serve/avatar/{filename}', [FileController::class, 'showAvatar']);
    $r->addRoute('GET', '/serve/alliance_avatar/{filename}', [FileController::class, 'showAllianceAvatar']);

    // --- Economy ---
    $r->addRoute('GET', '/bank', [BankController::class, 'show']);
    $r->addRoute('POST', '/bank/deposit', [BankController::class, 'handleDeposit']);
    $r->addRoute('POST', '/bank/withdraw', [BankController::class, 'handleWithdraw']);
    $r->addRoute('POST', '/bank/transfer', [BankController::class, 'handleTransfer']);

    // --- Black Market (Refactored) ---
    // The Exchange (Crystal Converter)
    $r->addRoute('GET', '/black-market/converter', [BlackMarketController::class, 'showExchange']);
    $r->addRoute('POST', '/black-market/convert', [CurrencyConverterController::class, 'handleConversion']);
    // The Undermarket (Actions)
    $r->addRoute('GET', '/black-market/actions', [BlackMarketController::class, 'showActions']);
    $r->addRoute('POST', '/black-market/buy/{action}', [BlackMarketController::class, 'handlePurchase']);
    $r->addRoute('POST', '/black-market/launder', [BlackMarketController::class, 'handleLaunder']); // --- NEW ---
    $r->addRoute('POST', '/black-market/withdraw-chips', [BlackMarketController::class, 'handleWithdrawChips']); // --- NEW ---
    $r->addRoute('POST', '/black-market/bounty/place', [BlackMarketController::class, 'handlePlaceBounty']);
    $r->addRoute('POST', '/black-market/shadow', [BlackMarketController::class, 'handleShadowContract']);

    // --- Military ---
    $r->addRoute('GET', '/training', [TrainingController::class, 'show']);
    $r->addRoute('POST', '/training/train', [TrainingController::class, 'handleTrain']);
    
    $r->addRoute('GET', '/structures', [StructureController::class, 'show']);
    $r->addRoute('POST', '/structures/upgrade', [StructureController::class, 'handleUpgrade']);
    $r->addRoute('POST', '/structures/batch-upgrade', [StructureController::class, 'handleBatchUpgrade']);

    $r->addRoute('GET', '/armory', [ArmoryController::class, 'show']);
    $r->addRoute('POST', '/armory/manufacture', [ArmoryController::class, 'handleManufacture']);
    $r->addRoute('POST', '/armory/equip', [ArmoryController::class, 'handleEquip']);

    // --- User Settings ---
    $r->addRoute('GET', '/settings', [SettingsController::class, 'show']);
    $r->addRoute('POST', '/settings/profile', [SettingsController::class, 'handleProfile']);
    $r->addRoute('POST', '/settings/email', [SettingsController::class, 'handleEmail']);
    $r->addRoute('POST', '/settings/password', [SettingsController::class, 'handlePassword']);
    $r->addRoute('POST', '/settings/security', [SettingsController::class, 'handleSecurity']);

    // --- PvP ---
    $r->addRoute('GET', '/spy', [SpyController::class, 'show']);
    $r->addRoute('GET', '/spy/page/{page:\d+}', [SpyController::class, 'show']);
    $r->addRoute('POST', '/spy/conduct', [SpyController::class, 'handleSpy']);
    $r->addRoute('GET', '/spy/reports', [SpyController::class, 'showReports']);
    $r->addRoute('GET', '/spy/report/{id:\d+}', [SpyController::class, 'showReport']);

    $r->addRoute('GET', '/battle', [BattleController::class, 'show']);
    $r->addRoute('GET', '/battle/page/{page:\d+}', [BattleController::class, 'show']);
    $r->addRoute('POST', '/battle/attack', [BattleController::class, 'handleAttack']);
    $r->addRoute('GET', '/battle/reports', [BattleController::class, 'showReports']);
    $r->addRoute('GET', '/battle/report/{id:\d+}', [BattleController::class, 'showReport']);

    $r->addRoute('GET', '/level-up', [LevelUpController::class, 'show']);
    $r->addRoute('POST', '/level-up/spend', [LevelUpController::class, 'handleSpend']);

    // --- Leaderboard ---
    $r->addRoute('GET', '/leaderboard', [LeaderboardController::class, 'show']);
    $r->addRoute('GET', '/leaderboard/{type:players|alliances}', [LeaderboardController::class, 'show']);
    $r->addRoute('GET', '/leaderboard/{type:players|alliances}/{page:\d+}', [LeaderboardController::class, 'show']);

    // --- Embassy (Directives) ---
    $r->addRoute('GET', '/embassy', [EmbassyController::class, 'index']);
    $r->addRoute('POST', '/embassy/activate', [EmbassyController::class, 'activate']);
    $r->addRoute('POST', '/embassy/revoke', [EmbassyController::class, 'revoke']);

    // --- Alliance System ---
    $r->addRoute('GET', '/alliance/list', [AllianceController::class, 'showList']);
    $r->addRoute('GET', '/alliance/list/page/{page:\d+}', [AllianceController::class, 'showList']);
    $r->addRoute('GET', '/alliance/profile/{id:\d+}', [AllianceController::class, 'showProfile']);
    $r->addRoute('GET', '/alliance/create', [AllianceController::class, 'showCreateForm']);
    $r->addRoute('POST', '/alliance/create', [AllianceController::class, 'handleCreate']);

    // Refactored: Recruitment (Applications & Invites) -> AllianceApplicationController
    $r->addRoute('POST', '/alliance/apply/{id:\d+}', [AllianceApplicationController::class, 'handleApply']);
    $r->addRoute('POST', '/alliance/cancel-app/{id:\d+}', [AllianceApplicationController::class, 'handleCancelApp']);
    $r->addRoute('POST', '/alliance/accept-app/{id:\d+}', [AllianceApplicationController::class, 'handleAcceptApp']);
    $r->addRoute('POST', '/alliance/reject-app/{id:\d+}', [AllianceApplicationController::class, 'handleRejectApp']);
    $r->addRoute('POST', '/alliance/invite/{id:\d+}', [AllianceApplicationController::class, 'handleInvite']);

    // Refactored: Member Actions -> AllianceMemberController
    $r->addRoute('POST', '/alliance/leave', [AllianceMemberController::class, 'handleLeave']);
    $r->addRoute('POST', '/alliance/kick/{id:\d+}', [AllianceMemberController::class, 'handleKickMember']);
    $r->addRoute('POST', '/alliance/role/assign', [AllianceMemberController::class, 'handleAssignRole']);

    // Refactored: Funding & Banking -> AllianceFundingController
    $r->addRoute('POST', '/alliance/donate', [AllianceFundingController::class, 'handleDonation']);
    $r->addRoute('POST', '/alliance/loan/request', [AllianceFundingController::class, 'handleLoanRequest']);
    $r->addRoute('POST', '/alliance/loan/approve/{id:\d+}', [AllianceFundingController::class, 'handleLoanApprove']);
    $r->addRoute('POST', '/alliance/loan/deny/{id:\d+}', [AllianceFundingController::class, 'handleLoanDeny']);
    $r->addRoute('POST', '/alliance/loan/repay/{id:\d+}', [AllianceFundingController::class, 'handleLoanRepay']);
    
    // Refactored: Settings -> AllianceSettingsController
    $r->addRoute('POST', '/alliance/profile/edit', [AllianceSettingsController::class, 'handleUpdateProfile']);
    
    // Roles
    $r->addRoute('GET', '/alliance/roles', [AllianceRoleController::class, 'showAll']);
    $r->addRoute('POST', '/alliance/roles/create', [AllianceRoleController::class, 'handleCreate']);
    $r->addRoute('POST', '/alliance/roles/update/{id:\d+}', [AllianceRoleController::class, 'handleUpdate']);
    $r->addRoute('POST', '/alliance/roles/delete/{id:\d+}', [AllianceRoleController::class, 'handleDelete']);
    
    // Structures
    $r->addRoute('GET', '/alliance/structures', [AllianceStructureController::class, 'show']);
    $r->addRoute('POST', '/alliance/structures/upgrade', [AllianceStructureController::class, 'handleUpgrade']);
    
    // Forums
    $r->addRoute('GET', '/alliance/forum', [AllianceForumController::class, 'showForum']);
    $r->addRoute('GET', '/alliance/forum/page/{page:\d+}', [AllianceForumController::class, 'showForum']);
    $r->addRoute('GET', '/alliance/forum/topic/create', [AllianceForumController::class, 'showCreateTopic']);
    $r->addRoute('POST', '/alliance/forum/topic/create', [AllianceForumController::class, 'handleCreateTopic']);
    $r->addRoute('GET', '/alliance/forum/topic/{id:\d+}', [AllianceForumController::class, 'showTopic']);
    $r->addRoute('POST', '/alliance/forum/topic/{id:\d+}/reply', [AllianceForumController::class, 'handleCreatePost']);
    $r->addRoute('POST', '/alliance/forum/topic/{id:\d+}/pin', [AllianceForumController::class, 'handlePinTopic']);
    $r->addRoute('POST', '/alliance/forum/topic/{id:\d+}/lock', [AllianceForumController::class, 'handleLockTopic']);
    
    // Diplomacy & War
    $r->addRoute('GET', '/alliance/diplomacy', [DiplomacyController::class, 'show']);
    $r->addRoute('POST', '/alliance/diplomacy/treaty/propose', [DiplomacyController::class, 'handleProposeTreaty']);
    $r->addRoute('POST', '/alliance/diplomacy/treaty/accept/{id:\d+}', [DiplomacyController::class, 'handleAcceptTreaty']);
    $r->addRoute('POST', '/alliance/diplomacy/treaty/decline/{id:\d+}', [DiplomacyController::class, 'handleDeclineTreaty']);
    $r->addRoute('POST', '/alliance/diplomacy/treaty/break/{id:\d+}', [DiplomacyController::class, 'handleBreakTreaty']);
    $r->addRoute('POST', '/alliance/diplomacy/rivalry/declare', [DiplomacyController::class, 'handleDeclareRivalry']);
    
    $r->addRoute('GET', '/alliance/war', [WarController::class, 'show']);
    $r->addRoute('POST', '/alliance/war/declare', [WarController::class, 'handleDeclareWar']);

    // --- Notification System ---
    $r->addRoute('GET', '/notifications', [NotificationController::class, 'index']);
    $r->addRoute('GET', '/notifications/check', [NotificationController::class, 'check']);
    $r->addRoute('POST', '/notifications/read/{id:\d+}', [NotificationController::class, 'handleMarkRead']);
    $r->addRoute('POST', '/notifications/read-all', [NotificationController::class, 'handleMarkAllRead']);
});

// 7. Dispatch
try {
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

            // --- Auth Middleware Logic ---
            $protectedPrefixes = [
                '/dashboard', '/bank', '/training', '/structures', '/armory',
                '/settings', '/spy', '/battle', '/level-up', '/alliance', '/profile',
                '/serve/avatar', '/serve/alliance_avatar', '/notifications', '/black-market',
                '/leaderboard', '/embassy'
            ];
            
            $isProtected = false;
            foreach ($protectedPrefixes as $prefix) {
                if (str_starts_with($uri, $prefix)) {
                    $isProtected = true;
                    break;
                }
            }

            if ($isProtected) {
                $container->get(AuthMiddleware::class)->handle();
            }

            // --- Dispatch Controller ---
            [$class, $method] = $handler;
            
            if ($container->has($class)) {
                $controller = $container->get($class);
                call_user_func_array([$controller, $method], [$vars]);
            } else {
                throw new Exception("Controller class $class not found in DI container.");
            }
            break;
    }
} catch (RedirectException $e) {
    // Handle graceful redirect
    header("Location: " . $e->getMessage());
    exit; // Legitimate entry point exit
} catch (TerminateException $e) {
    // Application finished intentionally (JSON response, File serve, etc.)
    exit; // Legitimate entry point exit
} catch (\Throwable $e) {
    http_response_code(500);
    if (($_ENV['APP_ENV'] ?? 'development') === 'development') {
        echo '<h1>Application Error:</h1>';
        echo '<pre>' . $e->getMessage() . '</pre>';
        echo '<pre>File: ' . $e->getFile() . ' on line ' . $e->getLine() . '</pre>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    } else {
        error_log($e->getMessage());
        echo 'An unexpected error occurred. Please try again later.';
    }
}