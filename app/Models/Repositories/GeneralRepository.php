<?php

namespace App\Models\Repositories;

use App\Models\Entities\General;
use PDO;

class GeneralRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    public function findByUserId(int $userId): ?General
    {
        $stmt = $this->db->prepare("SELECT * FROM generals WHERE user_id = ?");
        $stmt->execute([$userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    public function getGeneralCount(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM generals WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    private function hydrate(array $data): General
    {
        return new General(
            id: (int)$data['id'],
            user_id: (int)$data['user_id'],
            name: $data['name'],
            experience: (int)$data['experience'],
            weapon_slot_1: isset($data['weapon_slot_1']) ? (int)$data['weapon_slot_1'] : null,
            weapon_slot_2: isset($data['weapon_slot_2']) ? (int)$data['weapon_slot_2'] : null,
            weapon_slot_3: isset($data['weapon_slot_3']) ? (int)$data['weapon_slot_3'] : null,
            weapon_slot_4: isset($data['weapon_slot_4']) ? (int)$data['weapon_slot_4'] : null,
            armor_slot_1: isset($data['armor_slot_1']) ? (int)$data['armor_slot_1'] : null,
            armor_slot_2: isset($data['armor_slot_2']) ? (int)$data['armor_slot_2'] : null,
            armor_slot_3: isset($data['armor_slot_3']) ? (int)$data['armor_slot_3'] : null,
            armor_slot_4: isset($data['armor_slot_4']) ? (int)$data['armor_slot_4'] : null,
            created_at: $data['created_at'],
            updated_at: $data['updated_at']
        );
    }
}
