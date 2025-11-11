<?php

namespace App\Models\Repositories;

use App\Models\Entities\UserStats;
use PDO;

/**
 * Handles all database operations for the 'user_stats' table.
 */
class StatsRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Finds a user's stats by their user ID.
     *
     * @param int $userId
     * @return UserStats|null
     */
    public function findByUserId(int $userId): ?UserStats
    {
        $stmt = $this->db->prepare("SELECT * FROM user_stats WHERE user_id = ?");
        $stmt->execute([$userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        // --- THIS IS THE FIX ---
        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Creates the default stats row for a new user.
     *
     * @param int $userId
     */
    public function createDefaults(int $userId): void
    {
        $stmt = $this->db->prepare("INSERT INTO user_stats (user_id) VALUES (?)");
        $stmt->execute([$userId]);
    }

    /**
     * Updates a user's attack turns.
     *
     * @param int $userId
     * @param int $newAttackTurns
     * @return bool True on success
     */
    public function updateAttackTurns(int $userId, int $newAttackTurns): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE user_stats SET attack_turns = ? WHERE user_id = ?"
        );
        return $stmt->execute([$newAttackTurns, $userId]);
    }

    /**
     * Helper method to convert a database row (array) into a UserStats entity.
     *
     * @param array $data
     * @return UserStats
     */
    private function hydrate(array $data): UserStats
    {
        return new UserStats(
            user_id: (int)$data['user_id'],
            level: (int)$data['level'],
            experience: (int)$data['experience'],
            net_worth: (int)$data['net_worth'],
            war_prestige: (int)$data['war_prestige'],
            energy: (int)$data['energy'],
            attack_turns: (int)$data['attack_turns'],
            level_up_points: (int)$data['level_up_points'],
            strength_points: (int)$data['strength_points'],
            constitution_points: (int)$data['constitution_points'],
            wealth_points: (int)$data['wealth_points'],
            dexterity_points: (int)$data['dexterity_points'],
            charisma_points: (int)$data['charisma_points']
        );
    }
}