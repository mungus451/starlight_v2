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
        $stmt = $this->db->prepare("SELECT * FROM war_battle_logs WHERE war_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
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
            created_at: $data['created_at']
        );
    }
}