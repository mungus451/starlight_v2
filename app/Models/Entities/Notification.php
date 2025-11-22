<?php

namespace App\Models\Entities;

/**
 * Represents a single row from the 'notifications' table.
 * Immutable Data Transfer Object.
 */
class Notification
{
    /**
     * @param int $id
     * @param int $user_id
     * @param string $type
     * @param string $message
     * @param bool $is_read
     * @param string $created_at
     */
    public function __construct(
        public readonly int $id,
        public readonly int $user_id,
        public readonly string $type,
        public readonly string $message,
        public readonly bool $is_read,
        public readonly string $created_at
    ) {
    }
}