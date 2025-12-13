<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\ContainerFactory;
use App\Core\Database;

// Use the singleton directly to get PDO
$db = Database::getInstance();

echo "Migrating: Age of Ascension - Phase 1 (Research Era)...
";

try {
    // 1. Add research_data to user_resources
    echo "  -> Adding 'research_data' to user_resources...
";
    $db->exec("ALTER TABLE user_resources ADD COLUMN research_data BIGINT(20) UNSIGNED NOT NULL DEFAULT 0");

    // 2. Add quantum_research_lab_level to user_structures
    echo "  -> Adding 'quantum_research_lab_level' to user_structures...
";
    $db->exec("ALTER TABLE user_structures ADD COLUMN quantum_research_lab_level INT(11) NOT NULL DEFAULT 0");

    // 3. Add nanite_forge_level to user_structures
    echo "  -> Adding 'nanite_forge_level' to user_structures...
";
    $db->exec("ALTER TABLE user_structures ADD COLUMN nanite_forge_level INT(11) NOT NULL DEFAULT 0");

    echo "Migration Complete!\n";

} catch (PDOException $e) {
    // Ignore "Duplicate column" errors if re-run
    if (str_contains($e->getMessage(), "Duplicate column name")) {
        echo "  -> Columns already exist. Skipping.\n";
    } else {
        echo "ERROR: " . $e->getMessage() . "\n";
        exit(1);
    }
}