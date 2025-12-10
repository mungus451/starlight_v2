<?php

// cron/process_npcs.php

// This script is intended to be run from the command line (cron)
if (php_sapi_name() !== 'cli') {
    throw new \RuntimeException('Access Denied: This script can only be run from the command line.');
}

// 1. Bootstrap the application
require __DIR__ . '/../vendor/autoload.php';

use App\Core\ContainerFactory;
use App\Models\Services\NpcService;

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    throw new \RuntimeException("Could not find .env file. \n");
}

// Set timezone
date_default_timezone_set('UTC');

// 2. Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // 3. Boot the Container
    $container = ContainerFactory::createContainer();

    // 4. Resolve the Service
    // The container injects the Logger configured for CLI output,
    // so we don't need manual echos here anymore.
    $service = $container->get(NpcService::class);
    
    // 5. Execute Logic
    $service->runNpcCycle();
    
} catch (\RuntimeException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    error_log("NPC CRON Runtime Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/php_errors.log');
    exit(1);
} catch (\Throwable $e) {
    // Final backstop for catastrophic failures before Logger is initialized
    $errorMessage = "CRITICAL NPC CRON ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo $errorMessage;
    error_log($errorMessage, 3, __DIR__ . '/../logs/php_errors.log');
    exit(1);
}