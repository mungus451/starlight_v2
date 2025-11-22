<?php

namespace App\Core;

use Redis;

/**
 * Handles Cross-Site Request Forgery (CSRF) protection using Redis.
 * * Strategy: Persistent Per-Session Token
 * Instead of single-use tokens (which break multi-tab browsing), we generate
 * one secure token per session and store it in Redis. It remains valid
 * until the session expires or the user logs out.
 */
class CSRFService
{
    private Redis $redis;
    private Session $session;
    
    // Prefix for Redis keys to avoid collisions
    private const KEY_PREFIX = 'csrf_token:';
    
    // Token lifetime matches session lifetime (24 hours)
    private const TTL = 86400;

    /**
     * DI Constructor.
     * The container automatically injects the configured Redis instance.
     * * @param Redis $redis
     * @param Session $session
     */
    public function __construct(Redis $redis, Session $session)
    {
        $this->redis = $redis;
        $this->session = $session;
    }

    /**
     * Retrieves the current CSRF token for the active session.
     * If one does not exist, it generates a new one.
     *
     * @return string The CSRF token
     */
    public function generateToken(): string
    {
        $sessionId = session_id();
        if (empty($sessionId)) {
            return '';
        }

        $key = self::KEY_PREFIX . $sessionId;
        
        // 1. Check if we already have a token for this session
        $existingToken = $this->redis->get($key);
        
        if ($existingToken) {
            return $existingToken;
        }

        // 2. Generate a new cryptographically secure token
        $newToken = bin2hex(random_bytes(32));
        
        // 3. Store in Redis with expiration
        $this->redis->setex($key, self::TTL, $newToken);
        
        return $newToken;
    }

    /**
     * Validates a submitted token against the one stored in Redis.
     *
     * @param string $submittedToken The token from $_POST
     * @return bool True if valid
     */
    public function validateToken(string $submittedToken): bool
    {
        if (empty($submittedToken)) {
            return false;
        }

        $sessionId = session_id();
        if (empty($sessionId)) {
            return false;
        }

        $key = self::KEY_PREFIX . $sessionId;
        $storedToken = $this->redis->get($key);

        if (!$storedToken) {
            return false; // Token expired or session invalid
        }

        // hash_equals prevents timing attacks
        return hash_equals($storedToken, $submittedToken);
    }

    /**
     * Explicitly rotates the token (e.g., on login/logout).
     * This prevents session fixation attacks.
     */
    public function rotateToken(): void
    {
        $sessionId = session_id();
        if (!empty($sessionId)) {
            $this->redis->del(self::KEY_PREFIX . $sessionId);
        }
        // A new one will be generated on next call to generateToken()
    }
}