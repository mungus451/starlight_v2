<?php

namespace App\Models\Repositories;

use App\Core\Database;
use PDO;

class EffectRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Adds a new active effect to a user.
     * Overwrites active effects of the same type if they exist?
     * Or maybe just extends duration? For simplicity, we'll delete old ones of same type and add new.
     */
    public function addEffect(int $userId, string $type, string $expiresAt, ?array $metadata = null): void
    {
        // Remove existing effect of same type
        $this->removeEffect($userId, $type);

        $stmt = $this->db->prepare("
            INSERT INTO user_active_effects (user_id, effect_type, expires_at, metadata)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId, 
            $type, 
            $expiresAt, 
            $metadata ? json_encode($metadata) : null
        ]);
    }

    /**
     * Removes a specific effect type from a user.
     */
    public function removeEffect(int $userId, string $type): void
    {
        $stmt = $this->db->prepare("DELETE FROM user_active_effects WHERE user_id = ? AND effect_type = ?");
        $stmt->execute([$userId, $type]);
    }

    /**
     * Checks if a user has an active effect of a specific type.
     * Returns the expiration time if active, or null if not.
     */
    public function getActiveEffect(int $userId, string $type): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM user_active_effects 
            WHERE user_id = ? AND effect_type = ? AND expires_at > NOW()
        ");
        $stmt->execute([$userId, $type]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Gets all active effects for a user.
     */
    public function getAllActiveEffects(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM user_active_effects 
            WHERE user_id = ? AND expires_at > NOW()
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Updates the metadata of an active effect.
     */
    public function updateMetadata(int $userId, string $type, array $metadata): void
    {
        $stmt = $this->db->prepare("
            UPDATE user_active_effects 
            SET metadata = ? 
            WHERE user_id = ? AND effect_type = ?
        ");
        $stmt->execute([json_encode($metadata), $userId, $type]);
    }
}
