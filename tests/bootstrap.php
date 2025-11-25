<?php

/**
 * PHPUnit Bootstrap File
 * 
 * Loads Composer autoloader and initializes testing environment.
 * This file is executed before each test run.
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env.testing if it exists, otherwise fall back to .env
$envFile = __DIR__ . '/../.env.testing';
if (!file_exists($envFile)) {
    $envFile = __DIR__ . '/../.env';
}

$dotenv = Dotenv\Dotenv::createImmutable(dirname($envFile), basename($envFile));
$dotenv->load();

// Set testing environment
$_ENV['APP_ENV'] = 'testing';

// Mockery integration note:
// Mockery expectations are validated via TestCase::tearDown() which calls Mockery::close()
// All test classes extend Tests\Unit\TestCase, so expectations are automatically verified
// PHPUnit 10+ removed the TestListener API that Mockery previously used for global hooks
