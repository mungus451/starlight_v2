<?php

namespace App\Models\Repositories;

use App\Models\Entities\UserStructure;
use PDO;

/**
 * Handles all database operations for the 'user_structures' table.
 */
class StructureRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Finds a user's structures by their user ID.
     *
     * @param int $userId
     * @return UserStructure|null
     */
    public function findByUserId(int $userId): ?UserStructure
    {
        $stmt = $this->db->prepare("SELECT * FROM user_structures WHERE user_id = ?");
        $stmt->execute([$userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Creates the default structures row for a new user.
     * This relies on the database's DEFAULT values.
     *
     * @param int $userId
     */
    public function createDefaults(int $userId): void
    {
        $stmt = $this->db->prepare("INSERT INTO user_structures (user_id) VALUES (?)");
        $stmt->execute([$userId]);
    }

    /**
     * Helper method to convert a database row (array) into a UserStructure entity.
     *
     * @param array $data
     * @return UserStructure
     */
    private function hydrate(array $data): UserStructure
    {
        return new UserStructure(
            user_id: (int)$data['user_id'],
            fortification_level: (int)$data['fortification_level'],
            offense_upgrade_level: (int)$data['offense_upgrade_level'],
            defense_upgrade_level: (int)$data['defense_upgrade_level'],
            spy_upgrade_level: (int)$data['spy_upgrade_level'],
            economy_upgrade_level: (int)$data['economy_upgrade_level'],
            population_level: (int)$data['population_level'],
            armory_level: (int)$data['armory_level']
        );
    }
}