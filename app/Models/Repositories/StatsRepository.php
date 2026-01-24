<?php

namespace App\Models\Repositories;

use App\Models\Entities\UserStats;
use PDO;

class StatsRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    public function findByUserId(int $userId): ?UserStats
    {
        $stmt = $this->db->prepare("SELECT * FROM user_stats WHERE user_id = ?");
        $stmt->execute([$userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    public function createDefaults(int $userId): void
    {
        $stmt = $this->db->prepare("INSERT INTO user_stats (user_id) VALUES (?)");
        $stmt->execute([$userId]);
    }

    // --- Updates ---

    public function updateAttackTurns(int $userId, int $newAttackTurns): bool
    {
        $stmt = $this->db->prepare("UPDATE user_stats SET attack_turns = ? WHERE user_id = ?");
        return $stmt->execute([$newAttackTurns, $userId]);
    }

    public function updateBattleAttackerStats(int $userId, int $newAttackTurns, int $newNetWorth, int $newExperience, int $newPrestige): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE user_stats SET attack_turns = ?, net_worth = ?, experience = ?, war_prestige = ? WHERE user_id = ?"
        );
        return $stmt->execute([$newAttackTurns, $newNetWorth, $newExperience, $newPrestige, $userId]);
    }

    public function updateBattleDefenderStats(int $userId, int $newNetWorth): bool
    {
        $stmt = $this->db->prepare("UPDATE user_stats SET net_worth = ? WHERE user_id = ?");
        return $stmt->execute([$newNetWorth, $userId]);
    }

    /**
     * Updates only the net worth of a user.
     * Used by the NetWorthCalculatorService in the turn processor.
     */
    public function updateNetWorth(int $userId, int $newNetWorth): bool
    {
        $stmt = $this->db->prepare("UPDATE user_stats SET net_worth = ? WHERE user_id = ?");
        return $stmt->execute([$newNetWorth, $userId]);
    }

    /**
     * Atomically increments battle win/loss counters.
     */
    public function incrementBattleStats(int $userId, bool $isVictory): bool
    {
        $column = $isVictory ? 'battles_won' : 'battles_lost';
        $sql = "UPDATE user_stats SET {$column} = {$column} + 1 WHERE user_id = ?";
        return $this->db->prepare($sql)->execute([$userId]);
    }

    /**
     * Atomically increments spy success/failure counters.
     */
    public function incrementSpyStats(int $userId, bool $isSuccess): bool
    {
        $column = $isSuccess ? 'spy_successes' : 'spy_failures';
        $sql = "UPDATE user_stats SET {$column} = {$column} + 1 WHERE user_id = ?";
        return $this->db->prepare($sql)->execute([$userId]);
    }

    public function updateLevelProgress(int $userId, int $newExperience, int $newLevel, int $newLevelUpPoints): bool
    {
        $sql = "UPDATE user_stats SET experience = ?, level = ?, level_up_points = ? WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$newExperience, $newLevel, $newLevelUpPoints, $userId]);
    }

    public function updateBaseStats(int $userId, int $pts, int $str, int $con, int $wlth, int $dex, int $cha): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE user_stats SET level_up_points = ?, strength_points = ?, constitution_points = ?, wealth_points = ?, dexterity_points = ?, charisma_points = ? WHERE user_id = ?"
        );
        return $stmt->execute([$pts, $str, $con, $wlth, $dex, $cha, $userId]);
    }

    public function updateDepositCharges(int $userId, int $newCharges): bool
    {
        $stmt = $this->db->prepare("UPDATE user_stats SET deposit_charges = ?, last_deposit_at = NOW() WHERE user_id = ?");
        return $stmt->execute([$newCharges, $userId]);
    }

    public function updateEnergy(int $userId, int $delta): bool
    {
        // Prevent energy from dropping below 0 is handled by application logic usually, 
        // but DB constraints might apply. For now, just simple arithmetic.
        $stmt = $this->db->prepare("UPDATE user_stats SET energy = energy + ? WHERE user_id = ?");
        return $stmt->execute([$delta, $userId]);
    }

    public function regenerateDepositCharges(int $userId, int $chargesToRegen): bool
    {
        $stmt = $this->db->prepare("UPDATE user_stats SET deposit_charges = deposit_charges + ? WHERE user_id = ?");
        return $stmt->execute([$chargesToRegen, $userId]);
    }

    public function applyTurnAttackTurn(int $userId, int $turnsToGrant): bool
    {
        $stmt = $this->db->prepare("UPDATE user_stats SET attack_turns = attack_turns + ? WHERE user_id = ?");
        return $stmt->execute([$turnsToGrant, $userId]);
    }

    // --- Counts & Lists ---

    public function getTotalPlayerCount(): int
    {
        $stmt = $this->db->query("SELECT COUNT(id) as total FROM users");
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    public function getTotalTargetCount(int $excludeUserId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(id) as total FROM users WHERE id != ?");
        $stmt->execute([$excludeUserId]);
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function getPaginatedTargetList(int $limit, int $offset, int $excludeUserId): array
    {
        $sql = "
            SELECT u.id, u.character_name, u.profile_picture_url, s.level, r.credits, r.soldiers AS army_size
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
     * Finds the user ranked immediately above the given user in net worth.
     *
     * @param UserStats $currentUserStats
     * @return array|null
     */
    public function findRivalByNetWorth(UserStats $currentUserStats): ?array
    {
        $sql = "
            SELECT u.character_name, s.net_worth
            FROM user_stats s
            JOIN users u ON s.user_id = u.id
            WHERE s.net_worth > ?
            ORDER BY s.net_worth ASC
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$currentUserStats->net_worth]);
        $rival = $stmt->fetch(PDO::FETCH_ASSOC);

        return $rival ?: null;
    }

    /**
     * Finds viable targets for NPC aggression.
     * Excludes the NPC itself. Prioritizes high net worth targets within a reasonable range.
     *
     * @param int $npcId
     * @param int $limit
     * @return array
     */
    public function findTargetsForNpc(int $npcId, int $limit = 5): array
    {
        // Simple logic: Find top N richest players who are not this NPC.
        // Future enhancement: Add power range logic (e.g. +/- 50% power)
        $sql = "
            SELECT u.id, u.character_name, s.net_worth, r.credits, r.soldiers
            FROM users u
            JOIN user_stats s ON u.id = s.user_id
            JOIN user_resources r ON u.id = r.user_id
            WHERE u.id != ? AND u.is_npc = 0
            ORDER BY s.net_worth DESC
            LIMIT ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $npcId, PDO::PARAM_INT);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves Leaderboard with dynamic sorting.
     * Supports: Net Worth, Prestige, Army Size, Population, Battles Won, Spy Success
     *
     * @param string $sortKey
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getLeaderboardPlayers(string $sortKey, int $limit, int $offset): array
    {
        // 1. Define Sort Logic
        $orderBy = match ($sortKey) {
            'prestige'      => 's.war_prestige DESC',
            'army'          => '(r.soldiers + r.guards + r.spies + r.sentries) DESC',
            'population'    => '(r.untrained_citizens + r.workers + r.soldiers + r.guards + r.spies + r.sentries) DESC',
            'battles_won'   => 's.battles_won DESC',
            'battles_lost'  => 's.battles_lost DESC',
            'spy_success'   => 's.spy_successes DESC',
            'spy_fail'      => 's.spy_failures DESC',
            'level'         => 's.level DESC',
            default         => 's.net_worth DESC' // 'net_worth' or fallback
        };

        // 2. Add secondary sort to break ties deterministically
        $orderBy .= ", s.net_worth DESC, u.id ASC";

        // 3. Construct Query
        // We select raw values to display in the view even if not sorting by them
        $sql = "
            SELECT 
                u.id,
                u.character_name,
                u.profile_picture_url,
                s.level,
                s.net_worth,
                s.war_prestige,
                s.battles_won,
                s.battles_lost,
                s.spy_successes,
                s.spy_failures,
                (r.soldiers + r.guards + r.spies + r.sentries) as army_size,
                (r.untrained_citizens + r.workers + r.soldiers + r.guards + r.spies + r.sentries) as population,
                a.id as alliance_id,
                a.name as alliance_name,
                a.tag as alliance_tag
            FROM user_stats s
            JOIN users u ON s.user_id = u.id
            JOIN user_resources r ON u.id = r.user_id
            LEFT JOIN alliances a ON u.alliance_id = a.id
            ORDER BY {$orderBy}
            LIMIT ? OFFSET ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->bindParam(2, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

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
            last_deposit_at: $data['last_deposit_at'],
            battles_won: (int)($data['battles_won'] ?? 0),
            battles_lost: (int)($data['battles_lost'] ?? 0),
            spy_successes: (int)($data['spy_successes'] ?? 0),
            spy_failures: (int)($data['spy_failures'] ?? 0)
        );
    }
}