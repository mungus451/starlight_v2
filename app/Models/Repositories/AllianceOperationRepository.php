<?php

namespace App\Models\Repositories;

use App\Models\Entities\Operation;
use PDO;

class AllianceOperationRepository
{
    public function __construct(private PDO $db) {}

    public function findActiveByAllianceId(int $allianceId): ?Operation
    {
        $stmt = $this->db->prepare("
            SELECT * FROM alliance_operations 
            WHERE alliance_id = ? AND status = 'active' 
            LIMIT 1
        ");
        $stmt->execute([$allianceId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    public function create(int $allianceId, string $type, int $target, int $hoursDuration, ?string $reward): int
    {
        $deadline = date('Y-m-d H:i:s', strtotime("+{$hoursDuration} hours"));
        
        $stmt = $this->db->prepare("
            INSERT INTO alliance_operations 
            (alliance_id, type, target_value, current_value, deadline, status, reward_buff)
            VALUES (?, ?, ?, 0, ?, 'active', ?)
        ");
        $stmt->execute([$allianceId, $type, $target, $deadline, $reward]);
        
        return (int)$this->db->lastInsertId();
    }

    public function updateProgress(int $opId, int $amountToAdd): void
    {
        $stmt = $this->db->prepare("
            UPDATE alliance_operations 
            SET current_value = current_value + ? 
            WHERE id = ?
        ");
        $stmt->execute([$amountToAdd, $opId]);
    }
    
    public function completeOperation(int $opId): void
    {
        $stmt = $this->db->prepare("UPDATE alliance_operations SET status = 'completed' WHERE id = ?");
        $stmt->execute([$opId]);
    }

    public function failOperation(int $opId): void
    {
        $stmt = $this->db->prepare("UPDATE alliance_operations SET status = 'failed' WHERE id = ?");
        $stmt->execute([$opId]);
    }

    // --- Contributions ---

    public function trackContribution(int $opId, int $userId, int $amount): void
    {
        // Insert or Update (Upsert)
        $sql = "
            INSERT INTO alliance_op_contributions (operation_id, user_id, amount, created_at, updated_at)
            VALUES (?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE amount = amount + ?, updated_at = NOW()
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$opId, $userId, $amount, $amount]);
    }

    public function getUserContribution(int $opId, int $userId): int
    {
        $stmt = $this->db->prepare("
            SELECT amount FROM alliance_op_contributions 
            WHERE operation_id = ? AND user_id = ?
        ");
        $stmt->execute([$opId, $userId]);
        return (int)$stmt->fetchColumn();
    }

    // --- Energy Logs ---

    public function logEnergy(int $allianceId, ?int $userId, string $type, int $amount, ?string $details): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO alliance_energy_logs (alliance_id, user_id, type, amount, details, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$allianceId, $userId, $type, $amount, $details]);
    }

    public function getRecentLogs(int $allianceId, int $limit = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT l.*, u.character_name 
            FROM alliance_energy_logs l
            LEFT JOIN users u ON l.user_id = u.id
            WHERE l.alliance_id = ?
            ORDER BY l.created_at DESC
            LIMIT ?
        ");
        $stmt->bindParam(1, $allianceId, PDO::PARAM_INT);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function hydrate(array $row): Operation
    {
        return new Operation(
            id: (int)$row['id'],
            alliance_id: (int)$row['alliance_id'],
            type: $row['type'],
            target_value: (int)$row['target_value'],
            current_value: (int)$row['current_value'],
            deadline: $row['deadline'],
            status: $row['status'],
            reward_buff: $row['reward_buff'],
            created_at: $row['created_at']
        );
    }
}
