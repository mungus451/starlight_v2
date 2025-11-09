<?php

namespace App\Models\Repositories;

use App\Models\Entities\UserResource;
use PDO;

/**
 * Handles all database operations for the 'user_resources' table.
 */
class ResourceRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Finds a user's resources by their user ID.
     *
     * @param int $userId
     * @return UserResource|null
     */
    public function findByUserId(int $userId): ?UserResource
    {
        $stmt = $this->db->prepare("SELECT * FROM user_resources WHERE user_id = ?");
        $stmt->execute([$userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Creates the default resource row for a new user.
     *
     * @param int $userId
     */
    public function createDefaults(int $userId): void
    {
        $stmt = $this->db->prepare("INSERT INTO user_resources (user_id) VALUES (?)");
        $stmt->execute([$userId]);
    }

    /**
     * Updates a user's credits and banked credits.
     * Used for Deposit and Withdraw.
     *
     * @param int $userId
     * @param int $newCredits The new total of credits on hand
     * @param int $newBankedCredits The new total of banked credits
     * @return bool True on success
     */
    public function updateBankingCredits(int $userId, int $newCredits, int $newBankedCredits): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE user_resources SET credits = ?, banked_credits = ? WHERE user_id = ?"
        );
        return $stmt->execute([$newCredits, $newBankedCredits, $userId]);
    }

    /**
     * --- NEW: Method for Transfers ---
     * Updates only a user's on-hand credits.
     *
     * @param int $userId
     * @param int $newCredits The new total of credits on hand
     * @return bool True on success
     */
    public function updateCredits(int $userId, int $newCredits): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE user_resources SET credits = ? WHERE user_id = ?"
        );
        return $stmt->execute([$newCredits, $userId]);
    }

    /**
     * Helper method to convert a database row (array) into a UserResource entity.
     *
     * @param array $data
     * @return UserResource
     */
    private function hydrate(array $data): UserResource
    {
        return new UserResource(
            user_id: (int)$data['user_id'],
            credits: (int)$data['credits'],
            banked_credits: (int)$data['banked_credits'],
            gemstones: (int)$data['gemstones'],
            untrained_citizens: (int)$data['untrained_citizens'],
            workers: (int)$data['workers'],
            soldiers: (int)$data['soldiers'],
            guards: (int)$data['guards'],
            spies: (int)$data['spies'],
            sentries: (int)$data['sentries']
        );
    }
}