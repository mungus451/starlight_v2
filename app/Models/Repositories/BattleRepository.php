<?php

namespace App\Models\Repositories;

use App\Models\Entities\BattleReport;
use PDO;

/**
 * Handles all database operations for the 'battle_reports' table.
 */
class BattleRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Creates a new battle report.
     * Updated to include defender_total_guards snapshot.
     *
     * @param int $attackerId
     * @param int $defenderId
     * @param string $attackType
     * @param string $attackResult
     * @param int $soldiersSent
     * @param int $attackerSoldiersLost
     * @param int $defenderGuardsLost
     * @param int $creditsPlundered
     * @param int $experienceGained
     * @param int $warPrestigeGained
     * @param int $netWorthStolen
     * @param int $attackerOffensePower
     * @param int $defenderDefensePower
     * @param int $defenderTotalGuards (NEW)
     * @param bool $isHidden (Default false)
     * @return int The ID of the new report
     */
    public function createReport(
        int $attackerId,
        int $defenderId,
        string $attackType,
        string $attackResult,
        int $soldiersSent,
        int $attackerSoldiersLost,
        int $defenderGuardsLost,
        int $creditsPlundered,
        int $experienceGained,
        int $warPrestigeGained,
        int $netWorthStolen,
        int $attackerOffensePower,
        int $defenderDefensePower,
        int $defenderTotalGuards,
        bool $isHidden = false,
        int $defenderShieldHp = 0,
        int $shieldDamageDealt = 0
    ): int {
        $sql = "
            INSERT INTO battle_reports
            (attacker_id, defender_id, attack_type, attack_result, soldiers_sent,
             attacker_soldiers_lost, defender_guards_lost, credits_plundered,
             experience_gained, war_prestige_gained, net_worth_stolen,
             attacker_offense_power, defender_defense_power, defender_total_guards, is_hidden,
             defender_shield_hp, shield_damage_dealt, created_at)
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $attackerId, $defenderId, $attackType, $attackResult, $soldiersSent,
            $attackerSoldiersLost, $defenderGuardsLost, $creditsPlundered,
            $experienceGained, $warPrestigeGained, $netWorthStolen,
            $attackerOffensePower, $defenderDefensePower, $defenderTotalGuards, (int)$isHidden,
            $defenderShieldHp, $shieldDamageDealt
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Finds the 50 most recent reports for an attacker.
     */
    public function findReportsByAttackerId(int $attackerId): array
    {
        $sql = "
            SELECT r.*, d.character_name as defender_name
            FROM battle_reports r
            JOIN users d ON r.defender_id = d.id
            WHERE r.attacker_id = ?
            ORDER BY r.created_at DESC
            LIMIT 50
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$attackerId]);
        
        $reports = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $reports[] = $this->hydrate($row);
        }
        return $reports;
    }

    /**
     * Finds the 50 most recent reports for a defender.
     */
    public function findReportsByDefenderId(int $defenderId): array
    {
        $sql = "
            SELECT r.*, d.character_name as defender_name, a.character_name as attacker_name
            FROM battle_reports r
            JOIN users d ON r.defender_id = d.id
            JOIN users a ON r.attacker_id = a.id
            WHERE r.defender_id = ?
            ORDER BY r.created_at DESC
            LIMIT 50
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$defenderId]);
        
        $reports = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $reports[] = $this->hydrate($row);
        }
        return $reports;
    }

    /**
     * Finds a single report by its ID, ensuring the viewer is either the attacker or defender.
     */
    public function findReportById(int $reportId, int $viewerId): ?BattleReport
    {
        $sql = "
            SELECT r.*, d.character_name as defender_name, a.character_name as attacker_name
            FROM battle_reports r
            JOIN users d ON r.defender_id = d.id
            JOIN users a ON r.attacker_id = a.id
            WHERE r.id = ? AND (r.attacker_id = ? OR r.defender_id = ?)
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$reportId, $viewerId, $viewerId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Finds the 5 most recent, non-hidden battle reports realm-wide.
     *
     * @return BattleReport[]
     */
    public function findLatestGlobal(): array
    {
        $sql = "
            SELECT r.*, d.character_name as defender_name, a.character_name as attacker_name
            FROM battle_reports r
            JOIN users d ON r.defender_id = d.id
            JOIN users a ON r.attacker_id = a.id
            WHERE r.is_hidden = 0
            ORDER BY r.created_at DESC
            LIMIT 5
        ";
        
        $stmt = $this->db->query($sql);
        
        $reports = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $reports[] = $this->hydrate($row);
        }
        return $reports;
    }

    /**
     * Retrieves paginated battle reports for a user (either attacker or defender).
     */
    public function getPaginatedUserBattles(int $userId, int $limit, int $offset): array
    {
        $sql = "
            SELECT r.*, d.character_name as defender_name, a.character_name as attacker_name
            FROM battle_reports r
            JOIN users d ON r.defender_id = d.id
            JOIN users a ON r.attacker_id = a.id
            WHERE r.attacker_id = ? OR r.defender_id = ?
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $userId, PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->bindValue(4, $offset, PDO::PARAM_INT);
        $stmt->execute();

        $reports = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $reports[] = $this->hydrate($row);
        }
        return $reports;
    }

    /**
     * Counts total battles for a user.
     */
    public function countUserBattles(int $userId): int
    {
        $sql = "SELECT COUNT(*) FROM battle_reports WHERE attacker_id = ? OR defender_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Finds the most recent battle report where a member of the given alliance was the defender.
     *
     * @param int $allianceId
     * @return BattleReport|null
     */
    public function findLatestDefenseByAlliance(int $allianceId): ?array
    {
        $sql = "
            SELECT r.*, 
                   d.character_name as defender_name, 
                   a.character_name as attacker_name,
                   TIMESTAMPDIFF(SECOND, r.created_at, NOW()) as seconds_ago
            FROM battle_reports r
            JOIN users d ON r.defender_id = d.id
            JOIN users a ON r.attacker_id = a.id
            WHERE d.alliance_id = ?
            ORDER BY r.created_at DESC
            LIMIT 1
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$allianceId]);
        
        // Return raw array to access seconds_ago, or modify hydrate/entity.
        // For minimal impact, I'll return the raw array here since ViewContextService calculates logic.
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Helper method to convert a database row into a BattleReport entity.
     */
    private function hydrate(array $data): BattleReport
    {
        return new BattleReport(
            id: (int)$data['id'],
            attacker_id: (int)$data['attacker_id'],
            defender_id: (int)$data['defender_id'],
            created_at: $data['created_at'],
            attack_type: $data['attack_type'],
            attack_result: $data['attack_result'],
            soldiers_sent: (int)$data['soldiers_sent'],
            attacker_soldiers_lost: (int)$data['attacker_soldiers_lost'],
            defender_guards_lost: (int)$data['defender_guards_lost'],
            credits_plundered: (int)$data['credits_plundered'],
            experience_gained: (int)$data['experience_gained'],
            war_prestige_gained: (int)$data['war_prestige_gained'],
            net_worth_stolen: (int)$data['net_worth_stolen'],
            attacker_offense_power: (int)$data['attacker_offense_power'],
            defender_defense_power: (int)$data['defender_defense_power'],
            defender_total_guards: (int)($data['defender_total_guards'] ?? 0),
            defender_shield_hp: (int)($data['defender_shield_hp'] ?? 0),
            shield_damage_dealt: (int)($data['shield_damage_dealt'] ?? 0),
            defender_name: $data['defender_name'] ?? null,
            attacker_name: $data['attacker_name'] ?? null,
            is_hidden: (bool)($data['is_hidden'] ?? false)
        );
    }
}