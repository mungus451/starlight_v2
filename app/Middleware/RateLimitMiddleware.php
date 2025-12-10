<?php

namespace App\Middleware;

use Predis\Client;
use App\Core\Exceptions\TerminateException;

/**
 * RateLimitMiddleware
 * * strict O(1) rate limiting using Redis atomic counters.
 * Prevents abuse by limiting requests per IP/Route per time window.
 */
class RateLimitMiddleware
{
    private Client $redis;
    private int $limit;
    private int $window;

    /**
     * @param Client $redis Injected Redis client
     * @param int $limit Max requests per window (default 60)
     * @param int $window Time window in seconds (default 60)
     */
    public function __construct(Client $redis, int $limit = 60, int $window = 60)
    {
        $this->redis = $redis;
        $this->limit = $limit;
        $this->window = $window;
    }

    /**
     * Checks the rate limit for the current user/IP.
     * Stops execution with HTTP 429 if limit exceeded.
     *
     * @param string $uri The route URI being accessed
     */
    public function handle(string $uri): void
    {
        // 1. Identify Client
        // In production with load balancers (Cloudflare/AWS), use HTTP_X_FORWARDED_FOR
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        // Create a composite key: rate:{ip}:{uri}
        // e.g., starlight_v2:rate:192.168.1.1:/login
        $key = "rate:{$ip}:{$uri}";

        // 2. Atomic Increment
        // INCR returns the new value. If key didn't exist, it sets to 1.
        $currentCount = $this->redis->incr($key);

        // 3. Set Expiration (Only on first hit)
        if ($currentCount === 1) {
            $this->redis->expire($key, $this->window);
        }

        // 4. Check Limit
        if ($currentCount > $this->limit) {
            http_response_code(429);
            header('Retry-After: ' . $this->window);
            
            // Optional: Return a JSON response for APIs
            if ($this->wantsJson()) {
                echo json_encode(['error' => 'Too Many Requests', 'retry_after' => $this->window]);
            } else {
                echo 'Too Many Requests. Please slow down.';
            }
            throw new TerminateException();
        }
    }

    /**
     * Helper to detect if client expects JSON.
     */
    private function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json');
    }
}