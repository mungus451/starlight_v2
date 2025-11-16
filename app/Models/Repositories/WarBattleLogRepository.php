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
        int $creditsPlundered
    ): int {
        $sql = "
            INSERT INTO war_battle_logs
                (war_id, battle_report_id, user_id, alliance_id, prestige_gained, 
                 units_killed, credits_plundered)
            VALUES
                (?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $warId,
            $battleReportId,
            $userId,
            $allianceId,
            $prestigeGained,
            $unitsKilled,
            $creditsPlundered
        ]);
        
        return (int)$this->db->lastInsertId();
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