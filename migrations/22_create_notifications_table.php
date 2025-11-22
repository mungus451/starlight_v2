<?php

// migrations/22_create_notifications_table.php

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

echo "Migrating: Creating notifications table...\n";

try {
    $db = \App\Core\Database::getInstance();

    // Create the notifications table
    // user_id: The recipient of the notification
    // type: Categorization (e.g., 'attack', 'alliance', 'system')
    // is_read: Boolean flag for unread status
    // Indexes: optimized for fetching a user's unread count instantly
    $sql = "
        CREATE TABLE IF NOT EXISTS `notifications` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT UNSIGNED NOT NULL,
            `type` VARCHAR(50) NOT NULL COMMENT 'e.g., attack_alert, alliance_invite, system',
            `message` TEXT NOT NULL,
            `is_read` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            
            PRIMARY KEY (`id`),
            KEY `idx_user_read` (`user_id`, `is_read`),
            KEY `idx_created_at` (`created_at`),
            
            CONSTRAINT `fk_notifications_user`
                FOREIGN KEY (`user_id`) 
                REFERENCES `users`(`id`)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $db->exec($sql);
    echo "Success: 'notifications' table created.\n";

} catch (Throwable $e) {
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
}