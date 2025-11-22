#!/usr/bin/env php
<?php

/**
 * Phinx Schema Snapshot Generator
 * 
 * Creates a deterministic SQL snapshot of the current database schema.
 * Useful for auditing, diffing, and documenting schema state.
 * 
 * Usage:
 *   php scripts/phinx_snapshot.php [options]
 * 
 * Options:
 *   --out=FILE    Output filename (default: snapshots/snapshot_TIMESTAMP.sql)
 *   --format=X    Output format: sql, json (default: sql)
 *   --help        Show this help message
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
    'out' => null,
    'format' => 'sql',
    'help' => false,
];

foreach ($argv as $arg) {
    if (strpos($arg, '--out=') === 0) {
        $options['out'] = substr($arg, 6);
    } elseif (strpos($arg, '--format=') === 0) {
        $options['format'] = substr($arg, 9);
    } elseif ($arg === '--help' || $arg === '-h') {
        $options['help'] = true;
    }
}

// Show help
if ($options['help']) {
    echo file_get_contents(__FILE__);
    exit(0);
}

echo "\n=== Phinx Schema Snapshot Generator ===\n\n";

try {
    $db = Database::getInstance();
    $dbName = $_ENV['DB_DATABASE'] ?? 'starlightDB';
    
    // Default output filename
    if (!$options['out']) {
        $timestamp = date('YmdHis');
        $ext = $options['format'] === 'json' ? 'json' : 'sql';
        $options['out'] = __DIR__ . "/../database/snapshots/snapshot_{$timestamp}.{$ext}";
    }
    
    // Ensure snapshots directory exists
    $snapshotDir = dirname($options['out']);
    if (!is_dir($snapshotDir)) {
        mkdir($snapshotDir, 0755, true);
    }
    
    echo "Generating {$options['format']} snapshot from database: {$dbName}\n\n";
    
    if ($options['format'] === 'sql') {
        // Try mysqldump first, fall back to INFORMATION_SCHEMA
        $mysqldumpAvailable = false;
        exec('which mysqldump 2>/dev/null', $which, $whichCode);
        if ($whichCode === 0 && !empty($which)) {
            $mysqldumpAvailable = true;
        }
        
        if ($mysqldumpAvailable) {
            // Use mysqldump for SQL format
            $command = sprintf(
                'mysqldump --no-data --skip-comments --skip-add-drop-table --skip-add-locks --skip-disable-keys --skip-set-charset --compact --host=%s --port=%s --user=%s --password=%s %s 2>&1',
                escapeshellarg($_ENV['DB_HOST'] ?? '127.0.0.1'),
                escapeshellarg($_ENV['DB_PORT'] ?? '3306'),
                escapeshellarg($_ENV['DB_USERNAME'] ?? 'sd_admin'),
                escapeshellarg($_ENV['DB_PASSWORD'] ?? 'starlight'),
                escapeshellarg($dbName)
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new Exception("mysqldump failed: " . implode("\n", $output));
            }
            
            // Clean and sort output for deterministic results
            $lines = array_filter($output, function($line) {
                // Remove empty lines and comments
                return !empty(trim($line)) && 
                       strpos($line, '--') !== 0 && 
                       strpos($line, '/*') !== 0;
            });
            
            $sql = implode("\n", $lines);
            file_put_contents($options['out'], $sql);
            
        } else {
            // Fallback: Use SHOW CREATE TABLE
            echo "  (mysqldump not available, using SHOW CREATE TABLE)\n\n";
            
            $stmt = $db->prepare("
                SELECT TABLE_NAME
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = ?
                  AND TABLE_TYPE = 'BASE TABLE'
                ORDER BY TABLE_NAME
            ");
            $stmt->execute([$dbName]);
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $sqlOutput = [];
            $sqlOutput[] = "-- Schema snapshot generated at " . date('Y-m-d H:i:s');
            $sqlOutput[] = "-- Database: {$dbName}";
            $sqlOutput[] = "";
            
            foreach ($tables as $tableName) {
                echo "  • {$tableName}\n";
                
                $stmt = $db->prepare("SHOW CREATE TABLE `{$tableName}`");
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result && isset($result['Create Table'])) {
                    $sqlOutput[] = "-- Table: {$tableName}";
                    $sqlOutput[] = $result['Create Table'] . ";";
                    $sqlOutput[] = "";
                }
            }
            
            // Get views
            $stmt = $db->prepare("
                SELECT TABLE_NAME
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = ?
                  AND TABLE_TYPE = 'VIEW'
                ORDER BY TABLE_NAME
            ");
            $stmt->execute([$dbName]);
            $views = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($views as $viewName) {
                echo "  • {$viewName} (view)\n";
                
                $stmt = $db->prepare("SHOW CREATE VIEW `{$viewName}`");
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result && isset($result['Create View'])) {
                    $sqlOutput[] = "-- View: {$viewName}";
                    $sqlOutput[] = $result['Create View'] . ";";
                    $sqlOutput[] = "";
                }
            }
            
            file_put_contents($options['out'], implode("\n", $sqlOutput));
        }
        
    } elseif ($options['format'] === 'json') {
        // Use INFORMATION_SCHEMA for JSON format
        $schema = [
            'database' => $dbName,
            'generated_at' => date('Y-m-d H:i:s'),
            'tables' => [],
        ];
        
        // Get all tables
        $stmt = $db->prepare("
            SELECT TABLE_NAME, ENGINE, TABLE_COLLATION, TABLE_COMMENT
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = ?
            ORDER BY TABLE_NAME
        ");
        $stmt->execute([$dbName]);
        $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($tables as $table) {
            $tableName = $table['TABLE_NAME'];
            
            echo "  • {$tableName}\n";
            
            $tableInfo = [
                'name' => $tableName,
                'engine' => $table['ENGINE'],
                'collation' => $table['TABLE_COLLATION'],
                'comment' => $table['TABLE_COMMENT'],
                'columns' => [],
                'indexes' => [],
                'foreign_keys' => [],
            ];
            
            // Get columns
            $stmt = $db->prepare("
                SELECT 
                    COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT,
                    EXTRA, COLUMN_COMMENT
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                ORDER BY ORDINAL_POSITION
            ");
            $stmt->execute([$dbName, $tableName]);
            $tableInfo['columns'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get indexes
            $stmt = $db->prepare("
                SELECT 
                    INDEX_NAME, COLUMN_NAME, NON_UNIQUE, INDEX_TYPE
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                ORDER BY INDEX_NAME, SEQ_IN_INDEX
            ");
            $stmt->execute([$dbName, $tableName]);
            $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group indexes by name
            $indexGroups = [];
            foreach ($indexes as $index) {
                $indexName = $index['INDEX_NAME'];
                if (!isset($indexGroups[$indexName])) {
                    $indexGroups[$indexName] = [
                        'name' => $indexName,
                        'unique' => $index['NON_UNIQUE'] == 0,
                        'type' => $index['INDEX_TYPE'],
                        'columns' => [],
                    ];
                }
                $indexGroups[$indexName]['columns'][] = $index['COLUMN_NAME'];
            }
            $tableInfo['indexes'] = array_values($indexGroups);
            
            // Get foreign keys (MariaDB/MySQL compatible)
            $stmt = $db->prepare("
                SELECT 
                    kcu.CONSTRAINT_NAME, 
                    kcu.COLUMN_NAME,
                    kcu.REFERENCED_TABLE_NAME, 
                    kcu.REFERENCED_COLUMN_NAME,
                    rc.UPDATE_RULE, 
                    rc.DELETE_RULE
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
                LEFT JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc
                    ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
                    AND kcu.TABLE_SCHEMA = rc.CONSTRAINT_SCHEMA
                WHERE kcu.TABLE_SCHEMA = ? AND kcu.TABLE_NAME = ?
                  AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
                ORDER BY kcu.CONSTRAINT_NAME, kcu.ORDINAL_POSITION
            ");
            $stmt->execute([$dbName, $tableName]);
            $tableInfo['foreign_keys'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $schema['tables'][] = $tableInfo;
        }
        
        // Write JSON with pretty formatting
        file_put_contents(
            $options['out'],
            json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
        
    } else {
        throw new Exception("Unknown format: {$options['format']}");
    }
    
    $size = filesize($options['out']);
    $sizeKb = round($size / 1024, 2);
    
    echo "\n✅ Snapshot generated successfully!\n";
    echo "   File: {$options['out']}\n";
    echo "   Size: {$sizeKb} KB\n";
    echo "   Tables: " . count($schema['tables'] ?? $tables ?? []) . "\n";
    echo "\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
