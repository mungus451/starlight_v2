<?php

namespace App\Models\Repositories;

use PDO;

class GeneralRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(int $userId, string $name): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO generals (user_id, name, experience, created_at, updated_at)
            VALUES (:user_id, :name, 0, NOW(), NOW())
        ");
        $stmt->execute(['user_id' => $userId, 'name' => $name]);
        return (int)$this->db->lastInsertId();
    }

    public function findByUserId(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM generals WHERE user_id = :user_id ORDER BY created_at ASC");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function findById(int $generalId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM generals WHERE id = :id");
        $stmt->execute(['id' => $generalId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function countByUserId(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM generals WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    public function updateWeaponSlot(int $generalId, string $weaponKey): void
    {
        $stmt = $this->db->prepare("UPDATE generals SET weapon_slot_1 = :key, updated_at = NOW() WHERE id = :id");
        $stmt->execute(['key' => $weaponKey, 'id' => $generalId]);
    }
}