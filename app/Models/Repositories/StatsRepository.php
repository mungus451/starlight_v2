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
     * Updates an attacker's stats after a battle.
     *
     * @return bool True on success
     */
    public function updateBattleAttackerStats(int $userId, int $newAttackTurns, int $newNetWorth, int $newExperience, int $newPrestige): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE user_stats SET 
                attack_turns = ?, 
                net_worth = ?, 
                experience = ?, 
                war_prestige = ? 
            WHERE user_id = ?"
        );
        return $stmt->execute([$newAttackTurns, $newNetWorth, $newExperience, $newPrestige, $userId]);
    }

    /**
     * Updates a defender's net worth after a battle.
     *
     * @param int $userId
     * @param int $newNetWorth
     * @return bool True on success
     */
    public function updateBattleDefenderStats(int $userId, int $newNetWorth): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE user_stats SET net_worth = ? WHERE user_id = ?"
        );
        return $stmt->execute([$newNetWorth, $userId]);
    }

    /**
     * Updates XP, Level, and Points.
     *
     * @param int $userId
     * @param int $newExperience
     * @param int $newLevel
     * @param int $newLevelUpPoints
     * @return bool
     */
    public function updateLevelProgress(int $userId, int $newExperience, int $newLevel, int $newLevelUpPoints): bool
    {
        $sql = "
            UPDATE user_stats SET 
                experience = ?, 
                level = ?, 
                level_up_points = ?
            WHERE user_id = ?
        ";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$newExperience, $newLevel, $newLevelUpPoints, $userId]);
    }

    /**
     * Gets the total number of registered players.
     */
    public function getTotalPlayerCount(): int
    {
        $stmt = $this->db->query("SELECT COUNT(id) as total FROM users");
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    /**
     * Gets the total number of *targetable* players.
     *
     * @param int $excludeUserId The ID of the current user, to exclude them
     * @return int
     */
    public function getTotalTargetCount(int $excludeUserId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(id) as total FROM users WHERE id != ?");
        $stmt->execute([$excludeUserId]);
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    /**
     * Gets a paginated list of players, ranked by net worth.
     * Used for the general player list.
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getPaginatedPlayers(int $limit, int $offset): array
    {
        $sql = "
            SELECT u.character_name, s.level, s.net_worth, s.war_prestige 
            FROM user_stats s
            JOIN users u ON s.user_id = u.id 
            ORDER BY s.net_worth DESC 
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->bindParam(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * --- NEW: Detailed Player Leaderboard ---
     * Fetches rank, profile data, and alliance info in one go.
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getLeaderboardPlayers(int $limit, int $offset): array
    {
        $sql = "
            SELECT 
                u.id,
                u.character_name,
                u.profile_picture_url,
                s.level,
                s.net_worth,
                s.war_prestige,
                a.id as alliance_id,
                a.name as alliance_name,
                a.tag as alliance_tag
            FROM user_stats s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN alliances a ON u.alliance_id = a.id
            ORDER BY s.net_worth DESC, s.war_prestige DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->bindParam(2, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Gets a paginated list of targets with rich data.
     *
     * @param int $limit
     * @param int $offset
     * @param int $excludeUserId The ID of the current user
     * @return array
     */
    public function getPaginatedTargetList(int $limit, int $offset, int $excludeUserId): array
    {
        $sql = "
            SELECT 
                u.id, 
                u.character_name, 
                u.profile_picture_url, 
                s.level, 
                r.credits, 
                r.soldiers AS army_size
            FROM users u
            JOIN user_stats s ON u.id = s.user_id
            JOIN user_resources r ON u.id = r.user_id
            WHERE u.id != ?
            ORDER BY s.net_worth DESC 
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $excludeUserId, PDO::PARAM_INT);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->bindParam(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Atomically updates all base stats and level up points.
     *
     * @return bool True on success
     */
    public function updateBaseStats(
        int $userId,
        int $newLevelUpPoints,
        int $newStrength,
        int $newConstitution,
        int $newWealth,
        int $newDexterity,
        int $newCharisma
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE user_stats SET 
                level_up_points = ?, 
                strength_points = ?, 
                constitution_points = ?, 
                wealth_points = ?, 
                dexterity_points = ?, 
                charisma_points = ?
            WHERE user_id = ?"
        );
        return $stmt->execute([
            $newLevelUpPoints,
            $newStrength,
            $newConstitution,
            $newWealth,
            $newDexterity,
            $newCharisma,
            $userId
        ]);
    }

    /**
     * Updates a user's deposit charges and sets the last deposit timestamp.
     *
     * @param int $userId
     * @param int $newCharges
     * @return bool
     */
    public function updateDepositCharges(int $userId, int $newCharges): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE user_stats SET 
                deposit_charges = ?, 
                last_deposit_at = NOW() 
            WHERE user_id = ?"
        );
        return $stmt->execute([$newCharges, $userId]);
    }

    /**
     * Regenerates deposit charges for a user.
     *
     * @param int $userId
     * @param int $chargesToRegen
     * @return bool
     */
    public function regenerateDepositCharges(int $userId, int $chargesToRegen): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE user_stats SET 
                deposit_charges = deposit_charges + ?
            WHERE user_id = ?"
        );
        return $stmt->execute([$chargesToRegen, $userId]);
    }

    /**
     * Atomically applies turn-based attack turns to a user's account.
     *
     * @param int $userId
     * @param int $turnsToGrant
     * @return bool
     */
    public function applyTurnAttackTurn(int $userId, int $turnsToGrant): bool
    {
        $sql = "
            UPDATE user_stats SET
                attack_turns = attack_turns + ?
            WHERE user_id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$turnsToGrant, $userId]);
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
            charisma_points: (int)$data['charisma_points'],
            deposit_charges: (int)$data['deposit_charges'],
            last_deposit_at: $data['last_deposit_at']
        );
    }
}