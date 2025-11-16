<?php

// This script is intended to be run from the command line (cron)
if (php_sapi_name() !== 'cli') {
    die('Access Denied: This script can only be run from the command line.');
}

// 1. Bootstrap the application
// __DIR__ is /cron, so __DIR__ . '/../' is the project root
require __DIR__ . '/../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    die("Could not find .env file. \n");
}

// Set timezone to avoid warnings
date_default_timezone_set('UTC'); // Or your preferred timezone

// 2. Execute the service
echo "Starting turn processing... [" . date('Y-m-d H:i:s') . "]\n";
$startTime = microtime(true);

try {
    // We instantiate the service directly
    $service = new \App\Models\Services\TurnProcessorService();
    
    // --- THIS IS THE CHANGE ---
    // The service now returns an array with counts for both users and alliances
    $processedCounts = $service->processAllUsers();
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    echo "Turn processing complete. \n";
    // Updated echo to show both counts
    echo "Processed {$processedCounts['users']} users and {$processedCounts['alliances']} alliances in {$duration} seconds. \n";

} catch (\Throwable $e) {
    $errorMessage = "CRITICAL CRON ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo $errorMessage;
    // Log this to our main PHP error log as well
    error_log($errorMessage, 3, __DIR__ . '/../logs/php_errors.log');
}