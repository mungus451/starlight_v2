<?php

namespace App\Models\Entities;

/**
 * Represents a single row from the 'notifications' table.
 * Immutable DTO for passing notification data between layers.
 */
class Notification
{
    /**
     * @param int $id
     * @param int $user_id
     * @param string $type ('attack', 'spy', 'alliance', 'system')
     * @param string $title
     * @param string $message
     * @param string|null $link Optional URL for action
     * @param bool $is_read
     * @param string $created_at
     */
    public function __construct(
        public readonly int $id,
        public readonly int $user_id,
        public readonly string $type,
        public readonly string $title,
        public readonly string $message,
        public readonly ?string $link,
        public readonly bool $is_read,
        public readonly string $created_at
    ) {
    }
}