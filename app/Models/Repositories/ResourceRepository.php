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
     * Atomically updates all resources and units involved in training.
     *
     * @return bool True on success
     */
    public function updateTrainedUnits(
        int $userId,
        int $newCredits,
        int $newUntrained,
        int $newWorkers,
        int $newSoldiers,
        int $newGuards,
        int $newSpies,
        int $newSentries
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE user_resources SET 
                credits = ?, 
                untrained_citizens = ?, 
                workers = ?, 
                soldiers = ?, 
                guards = ?, 
                spies = ?, 
                sentries = ? 
            WHERE user_id = ?"
        );
        return $stmt->execute([
            $newCredits, $newUntrained, $newWorkers, $newSoldiers, $newGuards, $newSpies, $newSentries, $userId
        ]);
    }

    /**
     * Updates an attacker's credits and spies after an operation.
     *
     * @param int $userId
     * @param int $newCredits
     * @param int $newSpies
     * @return bool True on success
     */
    public function updateSpyAttacker(int $userId, int $newCredits, int $newSpies): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE user_resources SET credits = ?, spies = ? WHERE user_id = ?"
        );
        return $stmt->execute([$newCredits, $newSpies, $userId]);
    }

    /**
     * Updates a defender's sentries after being spied on.
     *
     * @param int $userId
     * @param int $newSentries
     * @return bool True on success
     */
    public function updateSpyDefender(int $userId, int $newSentries): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE user_resources SET sentries = ? WHERE user_id = ?"
        );
        return $stmt->execute([$newSentries, $userId]);
    }

    /**
     * Updates an attacker's credits and soldiers after a battle.
     *
     * @param int $userId
     * @param int $newCredits
     * @param int $newSoldiers
     * @return bool True on success
     */
    public function updateBattleAttacker(int $userId, int $newCredits, int $newSoldiers): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE user_resources SET credits = ?, soldiers = ? WHERE user_id = ?"
        );
        return $stmt->execute([$newCredits, $newSoldiers, $userId]);
    }

    /**
     * Updates a defender's credits and guards after a battle.
     *
     * @param int $userId
     * @param int $newCredits
     * @param int $newGuards
     * @return bool True on success
     */
    public function updateBattleDefender(int $userId, int $newCredits, int $newGuards): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE user_resources SET credits = ?, guards = ? WHERE user_id = ?"
        );
        return $stmt->execute([$newCredits, $newGuards, $userId]);
    }

    // --- NEW METHOD FOR TURN PROCESSOR ---

    /**
     * Atomically applies all turn-based income to a user's account.
     * This uses relative addition (e.g., credits = credits + ?) to be safe.
     *
     * @param int $userId
     * @param int $creditsGained
     * @param int $interestGained
     * @param int $citizensGained
     * @return bool True on success
     */
    public function applyTurnIncome(int $userId, int $creditsGained, int $interestGained, int $citizensGained): bool
    {
        $sql = "
            UPDATE user_resources SET
                credits = credits + ?,
                banked_credits = banked_credits + ?,
                untrained_citizens = untrained_citizens + ?
            WHERE user_id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$creditsGained, $interestGained, $citizensGained, $userId]);
    }

    // --- END NEW METHOD ---

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