<?php

namespace App\Models\Repositories;

use App\Models\Entities\WarHistory;
use PDO;

/**
 * Handles all database operations for the 'war_history' table.
 */
class WarHistoryRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Creates a new war history archive entry.
     *
     * @param int $warId
     * @param string $declarerName
     * @param string $defenderName
     * @param string|null $casusBelli
     * @param string $goalText
     * @param string $outcome
     * @param string|null $finalStatsJson
     * @param string $startTime
     * @param string $endTime
     * @return int The ID of the new history entry
     */
    public function createHistory(
        int $warId,
        string $declarerName,
        string $defenderName,
        ?string $casusBelli,
        string $goalText,
        string $outcome,
        ?string $finalStatsJson,
        string $startTime,
        string $endTime
    ): int {
        $sql = "
            INSERT INTO war_history
                (war_id, declarer_name, defender_name, casus_belli_text, goal_text, 
                 outcome, final_stats_json, start_time, end_time)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $warId,
            $declarerName,
            $defenderName,
            $casusBelli,
            $goalText,
            $outcome,
            $finalStatsJson,
            $startTime,
            $endTime
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * Helper method to convert a database row into a WarHistory entity.
     *
     * @param array $data
     * @return WarHistory
     */
    private function hydrate(array $data): WarHistory
    {
        return new WarHistory(
            id: (int)$data['id'],
            war_id: (int)$data['war_id'],
            declarer_name: $data['declarer_name'],
            defender_name: $data['defender_name'],
            casus_belli_text: $data['casus_belli_text'] ?? null,
            goal_text: $data['goal_text'],
            outcome: $data['outcome'],
            mvp_metadata_json: $data['mvp_metadata_json'] ?? null,
            final_stats_json: $data['final_stats_json'] ?? null,
            start_time: $data['start_time'],
            end_time: $data['end_time']
        );
    }
}