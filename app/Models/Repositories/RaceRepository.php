<?php

namespace App\Models\Repositories;

use App\Models\Entities\Race;
use PDO;

/**
 * Handles all database operations for the 'races' table.
 */
class RaceRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Finds all races in the database.
     * 
     * @return Race[]
     */
    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM races ORDER BY id ASC");
        $races = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $races[] = $this->hydrate($row);
        }
        return $races;
    }

    /**
     * Finds a race by its ID.
     */
    public function findById(int $id): ?Race
    {
        $stmt = $this->db->prepare("SELECT * FROM races WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Helper method to convert a database row (array) into a Race entity.
     */
    private function hydrate(array $data): Race
    {
        return new Race(
            id: (int)$data['id'],
            name: $data['name'],
            exclusive_resource: $data['exclusive_resource'],
            lore: $data['lore'],
            uses: $data['uses']
        );
    }
}
