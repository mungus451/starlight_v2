<?php

namespace App\Models\Repositories;

use App\Models\Entities\WarSpyLog;
use PDO;

class WarSpyLogRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Creates a new war spy log entry.
     */
    public function createLog(
        int $warId,
        ?int $spyReportId,
        int $attackerUserId,
        int $attackerAllianceId,
        int $defenderUserId,
        int $defenderAllianceId,
        string $operationType,
        string $result
    ): int {
        $sql = "
            INSERT INTO war_spy_logs 
                (war_id, spy_report_id, attacker_user_id, attacker_alliance_id, 
                 defender_user_id, defender_alliance_id, operation_type, result)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $warId,
            $spyReportId,
            $attackerUserId,
            $attackerAllianceId,
            $defenderUserId,
            $defenderAllianceId,
            $operationType,
            $result
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    /**
     * Finds spy logs for a specific war.
     *
     * @param int $warId
     * @param int $limit
     * @param int $offset
     * @return WarSpyLog[]
     */
    public function findByWarId(int $warId, int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->db->prepare("SELECT * FROM war_spy_logs WHERE war_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$warId, $limit, $offset]);
        
        $logs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $logs[] = $this->hydrate($row);
        }
        return $logs;
    }

    /**
     * Counts spy logs for a specific war.
     *
     * @param int $warId
     * @return int
     */
    public function countByWarId(int $warId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM war_spy_logs WHERE war_id = ?");
        $stmt->execute([$warId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Helper method to convert a database row into a WarSpyLog entity.
     *
     * @param array $data
     * @return WarSpyLog
     */
    private function hydrate(array $data): WarSpyLog
    {
        return new WarSpyLog(
            id: (int)$data['id'],
            war_id: (int)$data['war_id'],
            spy_report_id: isset($data['spy_report_id']) ? (int)$data['spy_report_id'] : null,
            attacker_user_id: (int)$data['attacker_user_id'],
            attacker_alliance_id: (int)$data['attacker_alliance_id'],
            defender_user_id: (int)$data['defender_user_id'],
            defender_alliance_id: (int)$data['defender_alliance_id'],
            operation_type: $data['operation_type'],
            result: $data['result'],
            created_at: $data['created_at'],
            updated_at: $data['updated_at']
        );
    }
}
