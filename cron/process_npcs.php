<?php

// cron/process_npcs.php

// This script is intended to be run from the command line (cron)
if (php_sapi_name() !== 'cli') {
    die('Access Denied: This script can only be run from the command line.');
}

// 1. Bootstrap the application
require __DIR__ . '/../vendor/autoload.php';

use App\Core\ContainerFactory;
use App\Models\Services\NpcService;

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    die("Could not find .env file. \n");
}

// Set timezone
date_default_timezone_set('UTC');

// 2. Start Session (Required for Services that might touch Session flash messages, though unused in CLI)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// The log file location (for reference in output)
$logPath = realpath(__DIR__ . '/../logs') . '/npc_actions.log';

echo "Starting NPC Agent Cycle... [" . date('Y-m-d H:i:s') . "]\n";
echo "Detailed logs will be written to: {$logPath}\n\n";

$startTime = microtime(true);

try {
    // 3. Boot the Container
    $container = ContainerFactory::createContainer();

    // 4. Resolve the Service
    // The container injects StructureService, TrainingService, AttackService, etc.
    $service = $container->get(NpcService::class);
    
    // 5. Execute Logic
    $service->runNpcCycle();
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 4);
    
    echo "\nNPC Cycle complete in {$duration} seconds.\n";

} catch (\Throwable $e) {
    $errorMessage = "CRITICAL NPC CRON ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo $errorMessage;
    error_log($errorMessage, 3, __DIR__ . '/../logs/php_errors.log');
}