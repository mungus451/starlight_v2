<?php

namespace App\Models\Repositories;

use App\Models\Entities\Scientist;
use PDO;

class ScientistRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    public function findByUserId(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM scientists WHERE user_id = ?");
        $stmt->execute([$userId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $scientists = [];
        foreach ($data as $row) {
            $scientists[] = $this->hydrate($row);
        }

        return $scientists;
    }

    public function getActiveScientistCount(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM scientists WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    private function hydrate(array $data): Scientist
    {
        return new Scientist(
            id: (int)$data['id'],
            user_id: (int)$data['user_id'],
            name: $data['name'],
            is_active: (bool)$data['is_active'],
            created_at: $data['created_at'],
            updated_at: $data['updated_at']
        );
    }
}
