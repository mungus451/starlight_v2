<?php

// migrations/20_create_sessions_table.php

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

echo "Migrating: Creating sessions table...\n";

try {
    $db = \App\Core\Database::getInstance();

    // Create the sessions table
    // id: The PHP session ID (string)
    // user_id: Optional, helpful for "logout all devices" features later
    // payload: The serialized session data
    // last_activity: Timestamp for garbage collection
    $sql = "
        CREATE TABLE IF NOT EXISTS `sessions` (
            `id` VARCHAR(128) NOT NULL,
            `user_id` INT UNSIGNED NULL DEFAULT NULL,
            `payload` MEDIUMTEXT NOT NULL,
            `last_activity` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_last_activity` (`last_activity`),
            KEY `idx_user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $db->exec($sql);
    echo "Success: 'sessions' table created.\n";

} catch (Throwable $e) {
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
}