<?php

namespace App\Models\Repositories;

use App\Models\Entities\AllianceBankLog;
use PDO;

/**
 * Handles all database operations for the 'alliance_bank_logs' table.
 */
class AllianceBankLogRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Creates a new alliance bank log entry.
     *
     * @param int $allianceId
     * @param int|null $userId (User associated, e.g., donator, taxee)
     * @param string $logType (e.g., 'donation', 'battle_tax', 'interest')
     * @param int $amount (Positive for income, negative for expense)
     * @param string $message
     * @return bool
     */
    public function createLog(
        int $allianceId,
        ?int $userId,
        string $logType,
        int $amount,
        string $message
    ): bool {
        $sql = "
            INSERT INTO alliance_bank_logs 
                (alliance_id, user_id, log_type, amount, message)
            VALUES
                (?, ?, ?, ?, ?)
        ";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$allianceId, $userId, $logType, $amount, $message]);
    }

    /**
     * Finds the most recent bank logs for an alliance (e.g., last 50).
     *
     * @param int $allianceId
     * @param int $limit
     * @return AllianceBankLog[]
     */
    public function findLogsByAllianceId(int $allianceId, int $limit = 50): array
    {
        $sql = "
            SELECT 
                abl.*, 
                u.character_name 
            FROM alliance_bank_logs abl
            LEFT JOIN users u ON abl.user_id = u.id
            WHERE abl.alliance_id = ?
            ORDER BY abl.created_at DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $allianceId, PDO::PARAM_INT);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $logs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $logs[] = $this->hydrate($row);
        }
        return $logs;
    }


    /**
     * Helper method to convert a database row into an AllianceBankLog entity.
     *
     * @param array $data
     * @return AllianceBankLog
     */
    private function hydrate(array $data): AllianceBankLog
    {
        return new AllianceBankLog(
            id: (int)$data['id'],
            alliance_id: (int)$data['alliance_id'],
            user_id: isset($data['user_id']) ? (int)$data['user_id'] : null,
            log_type: $data['log_type'],
            amount: (int)$data['amount'],
            message: $data['message'],
            created_at: $data['created_at'],
            character_name: $data['character_name'] ?? null
        );
    }
}