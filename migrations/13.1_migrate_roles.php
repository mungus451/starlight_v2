<?php

// This script is intended to be run from the command line ONE TIME.
if (php_sapi_name() !== 'cli') {
    die('Access Denied');
}

// 1. Bootstrap the application
require __DIR__ . '/../vendor/autoload.php';
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    die("Could not find .env file. \n");
}

echo "Starting alliance role migration... \n";
$startTime = microtime(true);

try {
    $db = \App\Core\Database::getInstance();
    
    // 1. Get all existing alliances
    $stmtAlliances = $db->query("SELECT id, leader_id FROM alliances");
    $alliances = $stmtAlliances->fetchAll(PDO::FETCH_ASSOC);

    if (empty($alliances)) {
        echo "No existing alliances found. No migration needed. \n";
        exit;
    }

    $migratedCount = 0;
    
    // 2. Loop through each alliance and create its roles
    foreach ($alliances as $alliance) {
        $allianceId = $alliance['id'];
        $leaderId = $alliance['leader_id'];

        echo "Migrating Alliance ID: {$allianceId}...\n";
        
        $db->beginTransaction();

        // 3. Create the 3 default roles for this alliance
        
        // a. Create 'Leader' role (all permissions)
        $sqlLeader = "
            INSERT INTO alliance_roles (alliance_id, name, sort_order, can_edit_profile, 
            can_manage_applications, can_invite_members, can_kick_members, can_manage_roles, 
            can_see_private_board, can_manage_forum, can_manage_bank, can_manage_structures) 
            VALUES (?, 'Leader', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1)
        ";
        $db->prepare($sqlLeader)->execute([$allianceId]);
        $leaderRoleId = $db->lastInsertId();

        // b. Create 'Recruit' role (only invite permission)
        $sqlRecruit = "
            INSERT INTO alliance_roles (alliance_id, name, sort_order, can_invite_members) 
            VALUES (?, 'Recruit', 10, 1)
        ";
        $db->prepare($sqlRecruit)->execute([$allianceId]);

        // c. Create 'Member' role (no permissions)
        $sqlMember = "
            INSERT INTO alliance_roles (alliance_id, name, sort_order) 
            VALUES (?, 'Member', 9)
        ";
        $db->prepare($sqlMember)->execute([$allianceId]);

        // 4. Find the original leader and assign them the new 'Leader' role
        $sqlLinkLeader = "UPDATE users SET alliance_role_id = ? WHERE id = ?";
        $db->prepare($sqlLinkLeader)->execute([$leaderRoleId, $leaderId]);

        $db->commit();
        $migratedCount++;
    }

    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    echo "Migration complete. \n";
    echo "Migrated {$migratedCount} alliances in {$duration} seconds. \n";

} catch (\Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "CRITICAL MIGRATION ERROR: " . $e->getMessage() . "\n";
    error_log("CRITICAL MIGRATION ERROR: " . $e->getMessage());
}