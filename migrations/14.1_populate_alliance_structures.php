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

echo "Starting alliance structure definition migration... \n";
$startTime = microtime(true);

try {
    $db = \App\Core\Database::getInstance();
    
    $structures = [
        [
            'key' => 'citadel_shield',
            'name' => 'Citadel Shield Array',
            'desc' => 'Grants a global defense bonus to all members of the alliance.',
            'cost' => 100000000, // 100M
            'mult' => 1.8,
            'text' => '+10% Defense',
            'json' => json_encode([['type' => 'defense_bonus_percent', 'value' => 0.10]])
        ],
        [
            'key' => 'command_nexus',
            'name' => 'Command Nexus',
            'desc' => 'Provides a bonus to all credit income for all members.',
            'cost' => 150000000, // 150M
            'mult' => 1.7,
            'text' => '+5% Income/Turn',
            'json' => json_encode([['type' => 'income_bonus_percent', 'value' => 0.05]])
        ],
        [
            'key' => 'galactic_research_hub',
            'name' => 'Galactic Research Hub',
            'desc' => 'Increases all resource production for alliance members.',
            'cost' => 120000000, // 120M
            'mult' => 1.75,
            'text' => '+10% Resources',
            'json' => json_encode([['type' => 'resource_bonus_percent', 'value' => 0.10]])
        ],
        [
            'key' => 'orbital_training_grounds',
            'name' => 'Orbital Training Grounds',
            'desc' => 'Grants a global offense bonus to all members of the alliance.',
            'cost' => 100000000, // 100M
            'mult' => 1.8,
            'text' => '+5% Offense',
            'json' => json_encode([['type' => 'offense_bonus_percent', 'value' => 0.05]])
        ],
        [
            'key' => 'population_habitat',
            'name' => 'Population Habitat',
            'desc' => 'Provides a flat boost to citizen growth per turn for all members.',
            'cost' => 50000000, // 50M
            'mult' => 1.6,
            'text' => '+5 Citizens/Turn',
            'json' => json_encode([['type' => 'citizen_growth_flat', 'value' => 5]])
        ],
        [
            'key' => 'warlords_throne',
            'name' => "Warlord's Throne",
            'desc' => 'A monument to your alliance\'s power. Greatly boosts all other structure bonuses.',
            'cost' => 500000000, // 500M
            'mult' => 2.5,
            'text' => '+15% to all bonuses',
            'json' => json_encode([['type' => 'all_bonus_multiplier', 'value' => 0.15]])
        ],
    ];
    
    $sql = "
        INSERT INTO alliance_structures_definitions 
            (structure_key, name, description, base_cost, cost_multiplier, bonus_text, bonuses_json)
        VALUES 
            (:key, :name, :desc, :cost, :mult, :text, :json)
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            description = VALUES(description),
            base_cost = VALUES(base_cost),
            cost_multiplier = VALUES(cost_multiplier),
            bonus_text = VALUES(bonus_text),
            bonuses_json = VALUES(bonuses_json)
    ";
    
    $stmt = $db->prepare($sql);
    
    $migratedCount = 0;
    foreach ($structures as $s) {
        $stmt->execute([
            ':key' => $s['key'],
            ':name' => $s['name'],
            ':desc' => $s['desc'],
            ':cost' => $s['cost'],
            ':mult' => $s['mult'],
            ':text' => $s['text'],
            ':json' => $s['json']
        ]);
        $migratedCount++;
    }

    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    echo "Migration complete. \n";
    echo "Upserted {$migratedCount} alliance structure definitions in {$duration} seconds. \n";

} catch (\Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "CRITICAL MIGRATION ERROR: " . $e->getMessage() . "\n";
    error_log("CRITICAL MIGRATION ERROR: " . $e->getMessage());
}