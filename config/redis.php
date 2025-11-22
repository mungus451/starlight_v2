<?php

/**
 * Redis Configuration
 *
 * Defines connection settings for the Redis data store.
 * Used for Sessions, Rate Limiting, and CSRF tokens.
 */

return [
    'host'     => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
    'port'     => (int)($_ENV['REDIS_PORT'] ?? 6379),
    'password' => $_ENV['REDIS_PASSWORD'] ?? null,
    'database' => (int)($_ENV['REDIS_DB'] ?? 0),
    'prefix'   => 'starlight_v2:', // Prevents key collisions
];