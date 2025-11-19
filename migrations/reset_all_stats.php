<?php

// This script is intended to be run from the command line.
if (php_sapi_name() !== 'cli') {
    die('Access Denied: CLI only.');
}

// 1. Bootstrap
require __DIR__ . '/../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    die("Error: Could not find .env file.\n");
}

echo "Starting global stat reset...\n\n";

try {
    $db = \App\Core\Database::getInstance();
    
    // 1. Fetch all users with their stats
    $sql = "
        SELECT s.user_id, s.level, u.character_name 
        FROM user_stats s
        JOIN users u ON s.user_id = u.id
    ";
    $users = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    $count = count($users);
    echo "Found {$count} users to process.\n";
    echo str_repeat("-", 40) . "\n";

    // 2. Prepare the update statement
    // We reset all allocated stats to 0 and set available points to equal the level.
    $updateSql = "
        UPDATE user_stats SET 
            strength_points = 0,
            constitution_points = 0,
            wealth_points = 0,
            dexterity_points = 0,
            charisma_points = 0,
            level_up_points = ? 
        WHERE user_id = ?
    ";
    $stmt = $db->prepare($updateSql);

    // 3. Loop and Process
    foreach ($users as $user) {
        $userId = (int)$user['user_id'];
        $level = (int)$user['level'];
        $name = $user['character_name'];

        // Execute the reset
        // Points available becomes exactly equal to their level
        $stmt->execute([$level, $userId]);

        echo "[RESET] User: {$name} (ID: {$userId})\n";
        echo "        Level: {$level} -> Available Points: {$level}\n";
    }

    echo str_repeat("-", 40) . "\n";
    echo "Global reset complete. All stats refunded.\n";

} catch (Throwable $e) {
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
}