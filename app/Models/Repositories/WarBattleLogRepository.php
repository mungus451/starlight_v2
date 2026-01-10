<?php

namespace App\Models\Repositories;

use App\Models\Entities\WarBattleLog;
use PDO;

/**
 * Handles all database operations for the 'war_battle_logs' table.
 */
class WarBattleLogRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Creates a new log entry for a battle that occurred during a war.
     *
     * @param int $warId
     * @param int $battleReportId
     * @param int $userId (The user who fought)
     * @param int $allianceId (The alliance the user fought for)
     * @param int $prestigeGained
     * @param int $unitsKilled (Defender guards lost)
     * @param int $creditsPlundered
     * @return int The ID of the new log entry
     */
    public function createLog(
        int $warId,
        int $battleReportId,
        int $userId,
        int $allianceId,
        int $prestigeGained,
        int $unitsKilled,
        int $creditsPlundered,
        int $structureDamage // NEW
    ): int {
        $sql = "
            INSERT INTO war_battle_logs
                (war_id, battle_report_id, user_id, alliance_id, prestige_gained, 
                 units_killed, credits_plundered, structure_damage)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $warId,
            $battleReportId,
            $userId,
            $allianceId,
            $prestigeGained,
            $unitsKilled,
            $creditsPlundered,
            $structureDamage // NEW
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * Retrieves aggregated statistics for a specific war.
     *
     * @param int $warId
     * @return array
     */
    public function getWarAggregates(int $warId): array
    {
        $sql = "
            SELECT 
                COUNT(id) as total_attacks,
                SUM(credits_plundered) as total_plunder,
                SUM(units_killed) as total_units_killed,
                SUM(structure_damage) as total_structure_damage
            FROM war_battle_logs
            WHERE war_id = ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$warId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_attacks' => 0,
            'total_plunder' => 0,
            'total_units_killed' => 0,
            'total_structure_damage' => 0,
        ];
    }

    /**
     * Finds battle logs for a specific war.
     *
     * @param int $warId
     * @param int $limit
     * @param int $offset
     * @return WarBattleLog[]
     */
    public function findByWarId(int $warId, int $limit = 20, int $offset = 0): array
    {
        $sql = "
            SELECT 
                wbl.*, 
                u_att.character_name as attacker_name, 
                u_def.character_name as defender_name,
                CASE 
                    WHEN br.attack_result = 'victory' THEN u_att.character_name
                    WHEN br.attack_result = 'defeat' THEN u_def.character_name
                    ELSE 'Stalemate'
                END as victor_name
            FROM war_battle_logs wbl
            JOIN battle_reports br ON wbl.battle_report_id = br.id
            JOIN users u_att ON br.attacker_id = u_att.id
            JOIN users u_def ON br.defender_id = u_def.id
            WHERE wbl.war_id = ? 
            ORDER BY wbl.created_at DESC 
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$warId, $limit, $offset]);
        
        $logs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $logs[] = $this->hydrate($row);
        }
        return $logs;
    }

    /**
     * Counts battle logs for a specific war.
     *
     * @param int $warId
     * @return int
     */
    public function countByWarId(int $warId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM war_battle_logs WHERE war_id = ?");
        $stmt->execute([$warId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Helper method to convert a database row into a WarBattleLog entity.
     *
     * @param array $data
     * @return WarBattleLog
     */
    private function hydrate(array $data): WarBattleLog
    {
        return new WarBattleLog(
            id: (int)$data['id'],
            war_id: (int)$data['war_id'],
            battle_report_id: (int)$data['battle_report_id'],
            user_id: (int)$data['user_id'],
            alliance_id: (int)$data['alliance_id'],
            prestige_gained: (int)$data['prestige_gained'],
            units_killed: (int)$data['units_killed'],
            credits_plundered: (int)$data['credits_plundered'],
            structure_damage: (int)$data['structure_damage'],
            created_at: $data['created_at'],
            attacker_name: $data['attacker_name'] ?? null,
            defender_name: $data['defender_name'] ?? null,
            victor_name: $data['victor_name'] ?? null
        );
    }

    /**
     * Retrieves the top performers for a specific war and alliance based on a metric.
     *
     * @param int $warId
     * @param int $allianceId
     * @param string $metric 'structure_damage', 'units_killed', or 'credits_plundered'
     * @param int $limit
     * @return array An array of ['character_name' => string, 'total_value' => int]
     */
    public function getTopPerformers(int $warId, int $allianceId, string $metric, int $limit = 5): array
    {
        $allowedMetrics = ['structure_damage', 'units_killed', 'credits_plundered'];
        if (!in_array($metric, $allowedMetrics)) {
            throw new \InvalidArgumentException("Invalid metric: $metric");
        }

        $sql = "
            SELECT 
                u.character_name,
                SUM(wbl.$metric) as total_value
            FROM war_battle_logs wbl
            JOIN users u ON wbl.user_id = u.id
            WHERE wbl.war_id = ? AND wbl.alliance_id = ?
            GROUP BY wbl.user_id
            ORDER BY total_value DESC
            LIMIT ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$warId, $allianceId, $limit]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}