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

echo "Migrating: Creating 'notifications' table...\n";

try {
    $db = \App\Core\Database::getInstance();

    // --- DROP PREVIOUS VERSION ---
    // Ensures a clean state for the second attempt at this system.
    $db->exec("DROP TABLE IF EXISTS `notifications`");
    echo "Dropped existing 'notifications' table (if any).\n";

    // Create notifications table
    // type: Categorizes the alert for icon/color logic in the frontend
    // link: Optional URL to redirect the user (e.g., to a battle report)
    // is_read: Boolean flag for UI badges
    $sql = "
        CREATE TABLE `notifications` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT UNSIGNED NOT NULL,
            `type` ENUM('attack', 'spy', 'alliance', 'system') NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `message` TEXT NOT NULL,
            `link` VARCHAR(255) NULL DEFAULT NULL,
            `is_read` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            
            PRIMARY KEY (`id`),
            
            -- Foreign Key constraint to ensure data integrity
            CONSTRAINT `fk_notification_user`
                FOREIGN KEY (`user_id`) 
                REFERENCES `users`(`id`)
                ON DELETE CASCADE,

            -- Index for fetching unread counts quickly (The 'Red Badge' query)
            KEY `idx_user_read` (`user_id`, `is_read`),
            
            -- Index for paginating recent notifications history
            KEY `idx_user_created` (`user_id`, `created_at` DESC)
            
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $db->exec($sql);
    echo "Success: 'notifications' table created.\n";

} catch (Throwable $e) {
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
}