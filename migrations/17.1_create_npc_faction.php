<?php

// migrations/17.1_create_npc_faction.php

if (php_sapi_name() !== 'cli') {
    die('Access Denied');
}

require __DIR__ . '/../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    die("Could not find .env file.\n");
}

echo "Starting NPC Faction migration...\n";

$db = \App\Core\Database::getInstance();

try {
    // 1. Add is_npc column to users table if it doesn't exist
    echo "Updating schema...\n";
    $colCheck = $db->query("SHOW COLUMNS FROM users LIKE 'is_npc'");
    if ($colCheck->rowCount() === 0) {
        $db->exec("ALTER TABLE users ADD COLUMN is_npc TINYINT(1) NOT NULL DEFAULT 0 AFTER alliance_role_id");
        $db->exec("ALTER TABLE users ADD INDEX idx_is_npc (is_npc)");
        echo "Column 'is_npc' added.\n";
    } else {
        echo "Column 'is_npc' already exists.\n";
    }

    // 2. Begin Transaction for Data Entry
    $db->beginTransaction();

    // 3. Create NPC Users
    $npcs = [
        'Leonardo' => ['email' => 'leo@npc.void', 'role' => 'Leader'], // Leader
        'Michelangelo' => ['email' => 'mikey@npc.void', 'role' => 'Member'],
        'Donatello' => ['email' => 'donnie@npc.void', 'role' => 'Member'],
        'Raphael' => ['email' => 'raph@npc.void', 'role' => 'Member'],
    ];

    $npcIds = [];

    foreach ($npcs as $name => $data) {
        // Check if exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $npcIds[$name] = $existing['id'];
            echo "NPC {$name} already exists (ID: {$existing['id']}).\n";
            continue;
        }

        // Create User
        $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT); // Unusable password
        $stmt = $db->prepare("INSERT INTO users (email, character_name, password_hash, is_npc) VALUES (?, ?, ?, 1)");
        $stmt->execute([$data['email'], $name, $hash]);
        $newId = $db->lastInsertId();
        $npcIds[$name] = $newId;

        // Initialize Defaults
        // Resources: Give them a stockpile to start
        $db->prepare("INSERT INTO user_resources (user_id, credits, untrained_citizens, banked_credits) VALUES (?, 50000000, 1000, 100000000)")->execute([$newId]);
        // Stats: Level 10 start
        $db->prepare("INSERT INTO user_stats (user_id, level, net_worth, attack_turns) VALUES (?, 10, 100000, 50)")->execute([$newId]);
        // Structures: Level 5 basics
        $db->prepare("INSERT INTO user_structures (user_id, economy_upgrade_level, fortification_level, armory_level) VALUES (?, 5, 5, 1)")->execute([$newId]);
        
        echo "Created NPC: {$name} (ID: {$newId})\n";
    }

    // 4. Create Alliance "The Void Syndicate"
    $allianceName = "The Void Syndicate";
    $allianceTag = "VOID";
    $leaderId = $npcIds['Leonardo']; // Leonardo leads

    $stmt = $db->prepare("SELECT id FROM alliances WHERE name = ?");
    $stmt->execute([$allianceName]);
    $alliance = $stmt->fetch(PDO::FETCH_ASSOC);
    $allianceId = 0;

    if ($alliance) {
        $allianceId = $alliance['id'];
        echo "Alliance '{$allianceName}' already exists (ID: {$allianceId}).\n";
    } else {
        $stmt = $db->prepare("INSERT INTO alliances (name, tag, leader_id, description, is_joinable, bank_credits) VALUES (?, ?, ?, ?, 0, 500000000)");
        $stmt->execute([
            $allianceName, 
            $allianceTag, 
            $leaderId, 
            "Shadows in the starlight. The masters of the void.",
        ]);
        $allianceId = $db->lastInsertId();
        echo "Created Alliance: {$allianceName} (ID: {$allianceId})\n";

        // Create Roles immediately manually since we aren't using the Service here
        // Leader Role
        $db->prepare("INSERT INTO alliance_roles (alliance_id, name, sort_order, can_edit_profile, can_manage_applications, can_invite_members, can_kick_members, can_manage_roles, can_see_private_board, can_manage_forum, can_manage_bank, can_manage_structures, can_manage_diplomacy, can_declare_war) VALUES (?, 'Leader', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1)")->execute([$allianceId]);
        // Member Role
        $db->prepare("INSERT INTO alliance_roles (alliance_id, name, sort_order) VALUES (?, 'Member', 9)")->execute([$allianceId]);
    }

    // 5. Assign NPCs to Alliance
    // Fetch roles to get IDs
    $stmt = $db->prepare("SELECT id, name FROM alliance_roles WHERE alliance_id = ?");
    $stmt->execute([$allianceId]);
    $roles = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [id => name]... wait fetchKeyPair returns [col1 => col2].
    // Let's flip it to map Name -> ID
    $roleMap = array_flip($roles); 

    foreach ($npcs as $name => $data) {
        $uid = $npcIds[$name];
        $roleName = $data['role']; // 'Leader' or 'Member'
        $rid = $roleMap[$roleName] ?? $roleMap['Member'];

        $db->prepare("UPDATE users SET alliance_id = ?, alliance_role_id = ? WHERE id = ?")
           ->execute([$allianceId, $rid, $uid]);
        
        echo "Assigned {$name} to {$allianceName} as {$roleName}.\n";
    }

    $db->commit();
    echo "Migration complete.\n";

} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
}