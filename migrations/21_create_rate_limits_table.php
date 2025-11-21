<?php

// migrations/21_create_rate_limits_table.php

if (php_sapi_name() !== 'cli') {
    die('Access Denied: CLI only.');
}

require __DIR__ . '/../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    die("Error: Could not find .env file.\n");
}

echo "Migrating: Creating rate_limits table...\n";

try {
    $db = \App\Core\Database::getInstance();

    // Create the rate_limits table
    // client_hash: MD5 of IP + User Agent (or just IP) to identify the client
    // route_uri: The specific path being accessed (e.g., '/login')
    // request_count: How many hits in the current window
    // window_start: Timestamp of the first request in this window
    $sql = "
        CREATE TABLE IF NOT EXISTS `rate_limits` (
            `client_hash` VARCHAR(64) NOT NULL,
            `route_uri` VARCHAR(191) NOT NULL,
            `request_count` INT UNSIGNED NOT NULL DEFAULT 1,
            `window_start` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`client_hash`, `route_uri`),
            KEY `idx_window` (`window_start`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $db->exec($sql);
    echo "Success: 'rate_limits' table created.\n";

} catch (Throwable $e) {
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
}