<?php

namespace App\Core;

use SessionHandlerInterface;
use Redis;

/**
 * RedisSessionHandler
 * * Replaces the default PHP session handler to store session data in Redis.
 * This improves performance (in-memory) and scalability (easier to share across servers).
 */
class RedisSessionHandler implements SessionHandlerInterface
{
    private Redis $redis;
    private int $lifetime;
    private string $prefix;

    /**
     * @param Redis $redis Connected Redis instance
     * @param int $lifetime Session duration in seconds (default 24 hours)
     * @param string $prefix Key prefix for session entries
     */
    public function __construct(Redis $redis, int $lifetime = 86400, string $prefix = 'session:')
    {
        $this->redis = $redis;
        $this->lifetime = $lifetime;
        $this->prefix = $prefix;
    }

    /**
     * Initialize session.
     */
    public function open(string $path, string $name): bool
    {
        // Redis connection is already handled by the container
        return true;
    }

    /**
     * Close the session.
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * Read session data.
     * @param string $id The session ID
     * @return string|false The session data or empty string
     */
    public function read(string $id): string|false
    {
        $key = $this->prefix . $id;
        $data = $this->redis->get($key);
        
        return $data !== false ? $data : '';
    }

    /**
     * Write session data.
     * @param string $id The session ID
     * @param string $data The serialized session data
     * @return bool
     */
    public function write(string $id, string $data): bool
    {
        $key = $this->prefix . $id;
        
        // SETEX: Set key, value, and expiration in one atomic command
        return $this->redis->setex($key, $this->lifetime, $data);
    }

    /**
     * Destroy a session.
     * @param string $id The session ID
     * @return bool
     */
    public function destroy(string $id): bool
    {
        $key = $this->prefix . $id;
        $this->redis->del($key);
        
        return true;
    }

    /**
     * Garbage Collection.
     * Redis handles expiration natively via TTL, so this is a no-op.
     */
    public function gc(int $max_lifetime): int|false
    {
        return 1;
    }
}