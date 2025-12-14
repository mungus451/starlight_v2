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
        $stmt = $this->db->prepare("
            SELECT
                user_id,
                credits,
                banked_credits,
                gemstones,
                naquadah_crystals,
                untrained_citizens,
                workers,
                soldiers,
                guards,
                spies,
                sentries,
                untraceable_chips,
                research_data,
                dark_matter
            FROM user_resources WHERE user_id = ?
        ");
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
     * Updates the Untraceable Chips balance.
     *
     * @param int $userId
     * @param int $newAmount
     * @return bool
     */
    public function updateChips(int $userId, int $newAmount): bool
    {
        $stmt = $this->db->prepare("UPDATE user_resources SET untraceable_chips = ? WHERE user_id = ?");
        return $stmt->execute([$newAmount, $userId]);
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
    public function applyTurnIncome(int $userId, int $creditsGained, int $interestGained, int $citizensGained, int $researchDataGained, float $darkMatterGained): bool
    {
        $sql = "
            UPDATE user_resources SET
                credits = credits + ?,
                banked_credits = banked_credits + ?,
                untrained_citizens = untrained_citizens + ?,
                research_data = research_data + ?,
                dark_matter = dark_matter + ?
            WHERE user_id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$creditsGained, $interestGained, $citizensGained, $researchDataGained, $darkMatterGained, $userId]);
    }

    // --- END NEW METHOD ---

    /**
     * Atomically updates a user's resources using relative changes.
     *
     * @param int $userId
     * @param float|null $creditsChange
     * @param float|null $naquadahCrystalsChange
     * @return bool
     */
    public function updateResources(int $userId, ?float $creditsChange = null, ?float $naquadahCrystalsChange = null): bool
    {
        $updates = [];
        $params = [];

        if ($creditsChange !== null) {
            $updates[] = "credits = credits + :credits_change";
            $params[':credits_change'] = $creditsChange;
        }

        if ($naquadahCrystalsChange !== null) {
            $updates[] = "naquadah_crystals = naquadah_crystals + :naquadah_crystals_change";
            $params[':naquadah_crystals_change'] = $naquadahCrystalsChange;
        }

        if (empty($updates)) {
            return true; // Nothing to update
        }

        $params[':user_id'] = $userId;

        $sql = "UPDATE user_resources SET " . implode(', ', $updates) . " WHERE user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
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
            naquadah_crystals: (float)$data['naquadah_crystals'],
            untrained_citizens: (int)$data['untrained_citizens'],
            workers: (int)$data['workers'],
            soldiers: (int)$data['soldiers'],
            guards: (int)$data['guards'],
            spies: (int)$data['spies'],
            sentries: (int)$data['sentries'],
            untraceable_chips: (int)($data['untraceable_chips'] ?? 0),
            research_data: (int)($data['research_data'] ?? 0),
            dark_matter: (int)($data['dark_matter'] ?? 0)
        );
    }
}