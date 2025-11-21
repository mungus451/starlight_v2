<?php

namespace App\Middleware;

use App\Core\Database;
use PDO;

class RateLimitMiddleware
{
    private PDO $db;
    private int $limit;
    private int $window;

    /**
     * @param int $limit Max requests per window (default 5)
     * @param int $window Time window in seconds (default 60)
     */
    public function __construct(int $limit = 5, int $window = 60)
    {
        $this->db = Database::getInstance();
        $this->limit = $limit;
        $this->window = $window;
    }

    /**
     * Checks the rate limit for the current user/IP on the given route.
     * Stops execution with HTTP 429 if limit exceeded.
     *
     * @param string $uri The route URI being accessed
     */
    public function handle(string $uri): void
    {
        // 1. Identify Client (IP Address)
        // In a real app, also check X-Forwarded-For if behind a load balancer
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $clientHash = hash('sha256', $ip); 
        
        $now = time();

        // 2. Garbage Collection (Probabilistic: 1% chance)
        // Keeps the table clean without running a separate cron job
        if (mt_rand(1, 100) === 1) {
            $this->cleanup($now);
        }

        // 3. Check & Update Limit (Atomic UPSERT)
        // If the window has passed, reset the count. Otherwise, increment.
        $sql = "
            INSERT INTO rate_limits (client_hash, route_uri, request_count, window_start)
            VALUES (?, ?, 1, ?)
            ON DUPLICATE KEY UPDATE
                request_count = IF(window_start < ?, 1, request_count + 1),
                window_start = IF(window_start < ?, ?, window_start)
        ";
        
        // Calculate the cutoff time for the window
        $windowCutoff = $now - $this->window;

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $clientHash, 
            $uri, 
            $now,           // Insert window_start
            $windowCutoff,  // Update check: has window expired?
            $windowCutoff,  // Update check: has window expired?
            $now            // Update: New window start if expired
        ]);

        // 4. Retrieve current count
        $stmt = $this->db->prepare("SELECT request_count FROM rate_limits WHERE client_hash = ? AND route_uri = ?");
        $stmt->execute([$clientHash, $uri]);
        $count = $stmt->fetchColumn();

        // 5. Block if limit exceeded
        if ($count > $this->limit) {
            http_response_code(429);
            header('Retry-After: ' . $this->window);
            die('Too Many Requests. Please slow down.');
        }
    }

    /**
     * Removes old entries from the rate limit table.
     */
    private function cleanup(int $now): void
    {
        $cutoff = $now - $this->window;
        $stmt = $this->db->prepare("DELETE FROM rate_limits WHERE window_start < ?");
        $stmt->execute([$cutoff]);
    }
}