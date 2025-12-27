<?php

namespace App\Models\Repositories;

use App\Models\Entities\UserNotificationPreferences;
use PDO;

/**
 * Handles all database operations for the 'user_notification_preferences' table.
 */
class UserNotificationPreferencesRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Gets the notification preferences for a user.
     * If no preferences exist, returns default preferences.
     *
     * @param int $userId
     * @return UserNotificationPreferences
     */
    public function getByUserId(int $userId): UserNotificationPreferences
    {
        $sql = "SELECT * FROM user_notification_preferences WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            return $this->hydrate($data);
        }

        // Return default preferences if none exist
        return new UserNotificationPreferences(
            user_id: $userId,
            attack_enabled: true,
            spy_enabled: true,
            alliance_enabled: true,
            system_enabled: true,
            push_notifications_enabled: false,
            created_at: date('Y-m-d H:i:s'),
            updated_at: date('Y-m-d H:i:s')
        );
    }

    /**
     * Creates or updates notification preferences for a user.
     *
     * @param int $userId
     * @param bool $attackEnabled
     * @param bool $spyEnabled
     * @param bool $allianceEnabled
     * @param bool $systemEnabled
     * @param bool $pushEnabled
     * @return bool
     */
    public function upsert(
        int $userId,
        bool $attackEnabled,
        bool $spyEnabled,
        bool $allianceEnabled,
        bool $systemEnabled,
        bool $pushEnabled
    ): bool {
        $sql = "
            INSERT INTO user_notification_preferences 
                (user_id, attack_enabled, spy_enabled, alliance_enabled, system_enabled, push_notifications_enabled)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                attack_enabled = VALUES(attack_enabled),
                spy_enabled = VALUES(spy_enabled),
                alliance_enabled = VALUES(alliance_enabled),
                system_enabled = VALUES(system_enabled),
                push_notifications_enabled = VALUES(push_notifications_enabled),
                updated_at = CURRENT_TIMESTAMP
        ";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $userId,
            $attackEnabled ? 1 : 0,
            $spyEnabled ? 1 : 0,
            $allianceEnabled ? 1 : 0,
            $systemEnabled ? 1 : 0,
            $pushEnabled ? 1 : 0
        ]);
    }

    /**
     * Creates default preferences for a new user.
     * Called when a user registers.
     *
     * @param int $userId
     * @return bool
     */
    public function createDefault(int $userId): bool
    {
        return $this->upsert(
            userId: $userId,
            attackEnabled: true,
            spyEnabled: true,
            allianceEnabled: true,
            systemEnabled: true,
            pushEnabled: false
        );
    }

    /**
     * Helper method to convert a database row into a UserNotificationPreferences entity.
     *
     * @param array $data
     * @return UserNotificationPreferences
     */
    private function hydrate(array $data): UserNotificationPreferences
    {
        return new UserNotificationPreferences(
            user_id: (int)$data['user_id'],
            attack_enabled: (bool)$data['attack_enabled'],
            spy_enabled: (bool)$data['spy_enabled'],
            alliance_enabled: (bool)$data['alliance_enabled'],
            system_enabled: (bool)$data['system_enabled'],
            push_notifications_enabled: (bool)$data['push_notifications_enabled'],
            created_at: $data['created_at'],
            updated_at: $data['updated_at']
        );
    }
}
