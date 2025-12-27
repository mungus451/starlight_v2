<?php

namespace App\Models\Entities;

/**
 * Represents a user's notification preferences.
 * Immutable DTO for passing notification preference data between layers.
 */
readonly class UserNotificationPreferences
{
    /**
     * @param int $user_id
     * @param bool $attack_enabled User wants attack notifications
     * @param bool $spy_enabled User wants spy notifications
     * @param bool $alliance_enabled User wants alliance notifications
     * @param bool $system_enabled User wants system notifications
     * @param bool $push_notifications_enabled User has enabled browser push notifications
     * @param string $created_at
     * @param string $updated_at
     */
    public function __construct(
        public readonly int $user_id,
        public readonly bool $attack_enabled,
        public readonly bool $spy_enabled,
        public readonly bool $alliance_enabled,
        public readonly bool $system_enabled,
        public readonly bool $push_notifications_enabled,
        public readonly string $created_at,
        public readonly string $updated_at
    ) {
    }

    /**
     * Check if a specific notification type is enabled.
     * 
     * @param string $type The notification type ('attack', 'spy', 'alliance', 'system')
     * @return bool
     */
    public function isTypeEnabled(string $type): bool
    {
        return match($type) {
            'attack' => $this->attack_enabled,
            'spy' => $this->spy_enabled,
            'alliance' => $this->alliance_enabled,
            'system' => $this->system_enabled,
            default => false
        };
    }
}
