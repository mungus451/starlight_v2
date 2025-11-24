<?php

/**
 * Phinx Configuration
 * 
 * Loads database credentials from .env to maintain consistency with App\Core\Database.
 * Supports development, testing, and production environments.
 */

// Load environment variables
require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Determine current environment
$environment = $_ENV['APP_ENV'] ?? 'development';

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/../database/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/../database/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => $environment,
        'development' => [
            'adapter' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'name' => $_ENV['DB_DATABASE'] ?? 'starlightDB',
            'user' => $_ENV['DB_USERNAME'] ?? 'sd_admin',
            'pass' => $_ENV['DB_PASSWORD'] ?? 'starlight',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
        'testing' => [
            'adapter' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'name' => $_ENV['DB_DATABASE_TEST'] ?? 'starlightDB_test',
            'user' => $_ENV['DB_USERNAME'] ?? 'sd_admin',
            'pass' => $_ENV['DB_PASSWORD'] ?? 'starlight',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
        'production' => [
            'adapter' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'name' => $_ENV['DB_DATABASE'] ?? 'starlightDB',
            'user' => $_ENV['DB_USERNAME'] ?? 'sd_admin',
            'pass' => $_ENV['DB_PASSWORD'] ?? 'starlight',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]
    ],
    'version_order' => 'creation'
];
