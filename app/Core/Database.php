<?php

namespace App\Core;

use PDO;
use PDOException;
use Dotenv\Dotenv;

class Database
{
    private static ?PDO $instance = null;

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct()
    {
    }

    /**
     * Prevent cloning.
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserialization.
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    /**
     * Get the singleton PDO instance.
     *
     * @return PDO
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            // Load environment variables from the project root
            // __DIR__ is /app/Core, so ../../ is the project root
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();

            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $db = $_ENV['DB_DATABASE'] ?? 'starlightDB';
            $user = $_ENV['DB_USERNAME'] ?? 'sd_admin';
            $pass = $_ENV['DB_PASSWORD'] ?? 'starlight';
            $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                // In a real app, you'd log this error, not just echo it
                error_log('Database Connection Error: ' . $e->getMessage());
                // For development, it's useful to see the error.
                // In production (APP_ENV=production), show a generic message.
                if (($_ENV['APP_ENV'] ?? 'development') === 'production') {
                    die('Could not connect to the database. Please try again later.');
                } else {
                    die('Database Connection Error: ' . $e->getMessage());
                }
            }
        }

        return self::$instance;
    }
}