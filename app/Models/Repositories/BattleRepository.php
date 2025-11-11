<?php

namespace App\Models\Repositories;

use App\Models\Entities\BattleReport;
use PDO;

/**
 * Handles all database operations for the 'battle_reports' table.
 */
class BattleRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Creates a new battle report.
     *
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
        int $defenderDefensePower
    ): int {
        $sql = "
            INSERT INTO battle_reports 
                (attacker_id, defender_id, attack_type, attack_result, soldiers_sent, 
                 attacker_soldiers_lost, defender_guards_lost, credits_plundered, 
                 experience_gained, war_prestige_gained, net_worth_stolen, 
                 attacker_offense_power, defender_defense_power)
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $attackerId, $defenderId, $attackType, $attackResult, $soldiersSent,
            $attackerSoldiersLost, $defenderGuardsLost, $creditsPlundered,
            $experienceGained, $warPrestigeGained, $netWorthStolen,
            $attackerOffensePower, $defenderDefensePower
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Finds the 50 most recent reports for an attacker.
     *
     * @param int $attackerId
     * @return BattleReport[]
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
     * Finds a single report by its ID, ensuring the viewer is the attacker.
     *
     * @param int $reportId
     * @param int $attackerId
     * @return BattleReport|null
     */
    public function findReportById(int $reportId, int $attackerId): ?BattleReport
    {
        $sql = "
            SELECT r.*, d.character_name as defender_name, a.character_name as attacker_name
            FROM battle_reports r
            JOIN users d ON r.defender_id = d.id
            JOIN users a ON r.attacker_id = a.id
            WHERE r.id = ? AND r.attacker_id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$reportId, $attackerId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Helper method to convert a database row into a BattleReport entity.
     *
     * @param array $data
     * @return BattleReport
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
            defender_name: $data['defender_name'] ?? null,
            attacker_name: $data['attacker_name'] ?? null
        );
    }
}