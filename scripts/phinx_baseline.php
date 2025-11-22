#!/usr/bin/env php
<?php

/**
 * Phinx Baseline Script
 * 
 * Marks existing migrations as applied in the phinxlog table without executing them.
 * This is used when adopting Phinx on an existing production database.
 * 
 * Usage:
 *   php scripts/phinx_baseline.php [options]
 * 
 * Options:
 *   --snapshot       Generate a schema snapshot after baselining
 *   --force          Force baseline even if phinxlog has entries
 *   --up-to=VERSION  Baseline only up to specific migration version
 *   --help           Show this help message
 * 
 * Safety:
 *   - Aborts if phinxlog already has non-baseline entries (unless --force)
 *   - Does NOT execute any DDL
 *   - Only inserts records into phinxlog table
 */

// Ensure CLI execution only
if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line.');
}

// Bootstrap
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Parse arguments
$options = [
    'snapshot' => false,
    'force' => false,
    'upTo' => null,
    'help' => false,
];

foreach ($argv as $arg) {
    if ($arg === '--snapshot') {
        $options['snapshot'] = true;
    } elseif ($arg === '--force') {
        $options['force'] = true;
    } elseif (strpos($arg, '--up-to=') === 0) {
        $options['upTo'] = substr($arg, 8);
    } elseif ($arg === '--help' || $arg === '-h') {
        $options['help'] = true;
    }
}

// Show help
if ($options['help']) {
    echo file_get_contents(__FILE__);
    exit(0);
}

echo "\n=== Phinx Baseline Script ===\n\n";

try {
    $db = Database::getInstance();
    
    // Check if phinxlog exists
    $tables = $db->query("SHOW TABLES LIKE 'phinxlog'")->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "⚠️  phinxlog table does not exist.\n";
        echo "   Run 'composer phinx migrate' first to initialize Phinx.\n";
        exit(1);
    }
    
    // Check existing entries
    $stmt = $db->query("SELECT COUNT(*) as count, MAX(version) as max_version FROM phinxlog");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $existingCount = $result['count'];
    $maxVersion = $result['max_version'];
    
    if ($existingCount > 0 && !$options['force']) {
        echo "⚠️  phinxlog already has {$existingCount} entries.\n";
        echo "   Latest version: {$maxVersion}\n";
        echo "   Use --force to baseline anyway (NOT recommended).\n";
        echo "   This database may already be managed by Phinx.\n";
        exit(1);
    }
    
    // Get migration files
    $migrationDir = __DIR__ . '/../database/migrations';
    $files = glob($migrationDir . '/*.php');
    
    if (empty($files)) {
        echo "⚠️  No migration files found in {$migrationDir}\n";
        exit(1);
    }
    
    // Parse migration files
    $migrations = [];
    foreach ($files as $file) {
        $filename = basename($file);
        
        // Extract version (timestamp) and name from filename
        // Format: YYYYMMDDHHMMSS_name.php
        if (preg_match('/^(\d{14})_(.+)\.php$/', $filename, $matches)) {
            $version = $matches[1];
            $name = $matches[2];
            
            // Apply up-to filter
            if ($options['upTo'] && $version > $options['upTo']) {
                continue;
            }
            
            $migrations[] = [
                'version' => $version,
                'migration_name' => $name,
                'start_time' => date('Y-m-d H:i:s'),
                'end_time' => date('Y-m-d H:i:s'),
                'breakpoint' => 0,
            ];
        }
    }
    
    if (empty($migrations)) {
        echo "⚠️  No valid migrations found to baseline.\n";
        exit(1);
    }
    
    // Sort by version
    usort($migrations, fn($a, $b) => strcmp($a['version'], $b['version']));
    
    echo "Found " . count($migrations) . " migration(s) to baseline:\n\n";
    
    foreach ($migrations as $migration) {
        echo "  • {$migration['version']} - {$migration['migration_name']}\n";
    }
    
    echo "\n";
    
    // Confirm
    if (!$options['force']) {
        echo "This will mark the above migrations as applied WITHOUT executing them.\n";
        echo "The database is assumed to already have the corresponding schema.\n\n";
        echo "Continue? (yes/no): ";
        
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($line) !== 'yes') {
            echo "\nAborted.\n";
            exit(0);
        }
        echo "\n";
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        $inserted = 0;
        
        foreach ($migrations as $migration) {
            // Check if already exists
            $stmt = $db->prepare("SELECT version FROM phinxlog WHERE version = ?");
            $stmt->execute([$migration['version']]);
            
            if ($stmt->fetch()) {
                echo "  ⊘ {$migration['version']} already exists, skipping\n";
                continue;
            }
            
            // Insert baseline record
            $stmt = $db->prepare("
                INSERT INTO phinxlog (version, migration_name, start_time, end_time, breakpoint)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $migration['version'],
                $migration['migration_name'],
                $migration['start_time'],
                $migration['end_time'],
                $migration['breakpoint'],
            ]);
            
            echo "  ✓ {$migration['version']} - {$migration['migration_name']}\n";
            $inserted++;
        }
        
        $db->commit();
        
        echo "\n✅ Baseline complete! Marked {$inserted} migration(s) as applied.\n";
        
        // Generate snapshot if requested
        if ($options['snapshot']) {
            echo "\nGenerating schema snapshot...\n";
            
            $snapshotFile = __DIR__ . '/../database/snapshots/baseline_' . date('YmdHis') . '.sql';
            $result = shell_exec(sprintf(
                'mysqldump --no-data --skip-comments --host=%s --port=%s --user=%s --password=%s %s | sed "/^--/d" | sed "/^\/\*/d" > %s 2>&1',
                escapeshellarg($_ENV['DB_HOST'] ?? '127.0.0.1'),
                escapeshellarg($_ENV['DB_PORT'] ?? '3306'),
                escapeshellarg($_ENV['DB_USERNAME'] ?? 'sd_admin'),
                escapeshellarg($_ENV['DB_PASSWORD'] ?? 'starlight'),
                escapeshellarg($_ENV['DB_DATABASE'] ?? 'starlightDB'),
                escapeshellarg($snapshotFile)
            ));
            
            if (file_exists($snapshotFile) && filesize($snapshotFile) > 0) {
                echo "✓ Snapshot saved to: {$snapshotFile}\n";
            } else {
                echo "⚠️  Snapshot generation failed. Error: {$result}\n";
            }
        }
        
        echo "\nYou can now use Phinx to manage future migrations:\n";
        echo "  composer phinx status\n";
        echo "  composer phinx migrate\n";
        echo "  composer phinx create NewMigration\n";
        echo "\n";
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
