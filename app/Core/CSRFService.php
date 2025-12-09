<?php

namespace App\Core;

use Predis\Client;

/**
 * Handles Cross-Site Request Forgery (CSRF) protection.
 * * Strategy: Persistent Per-Session Token
 * * Storage: Redis (Strict)
 * 
 * Instead of single-use tokens (which break multi-tab browsing), we generate
 * one secure token per session. It remains valid until the session expires 
 * or the user logs out.
 */
class CSRFService
{
    private Client $redis;
    private Session $session;
    
    // Prefix for Redis keys to avoid collisions
    private const KEY_PREFIX = 'csrf_token:';
    
    // Token lifetime matches session lifetime (24 hours)
    private const TTL = 86400;

    /**
     * DI Constructor.
     * 
     * @param Client $redis
     * @param Session $session
     */
    public function __construct(Client $redis, Session $session)
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

        // Predis Client handles prefixing automatically if configured, 
        // but we add our own specific key segment here.
        // Note: If 'starlight_v2:' is the global prefix, and we add 'csrf_token:', 
        // the actual key in Redis is 'starlight_v2:csrf_token:sess_id'.
        $key = self::KEY_PREFIX . $sessionId;
        
        $existingToken = $this->redis->get($key);
        
        if ($existingToken) {
            return $existingToken;
        }

        $newToken = bin2hex(random_bytes(32));
        $this->redis->setex($key, self::TTL, $newToken);
        
        return $newToken;
    }

    /**
     * Validates a submitted token against the stored one.
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
            $this->redis->del([self::KEY_PREFIX . $sessionId]);
        }
    }
}