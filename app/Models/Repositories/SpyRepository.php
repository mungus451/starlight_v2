<?php

namespace App\Models\Repositories;

use App\Models\Entities\SpyReport;
use PDO;

/**
 * Handles all database operations for the 'spy_reports' table.
 */
class SpyRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    public function createReport(
        int $attackerId,
        int $defenderId,
        string $result,
        int $spiesSent,
        int $spiesLost,
        int $sentriesLost,
        int $defenderTotalSentries,
        ?int $credits, 
        ?int $gemstones, ?int $workers, ?int $soldiers, ?int $guards, ?int $spies, ?int $sentries,
        ?int $econLevel, ?int $popLevel, ?int $armoryLevel,
        int $defenderWorkersLost = 0 // New Parameter
    ): int {
        $sql = "
            INSERT INTO spy_reports 
                (attacker_id, defender_id, operation_result, spies_sent, spies_lost_attacker, sentries_lost_defender,
                 defender_total_sentries, defender_workers_lost,
                 credits_seen, 
                 gemstones_seen, workers_seen, soldiers_seen, guards_seen, spies_seen, sentries_seen,
                 economy_upgrade_level_seen, population_level_seen, armory_level_seen, created_at)
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $attackerId, $defenderId, $result, $spiesSent, $spiesLost, $sentriesLost,
            $defenderTotalSentries, $defenderWorkersLost,
            $credits,
            $gemstones, $workers, $soldiers, $guards, $spies, $sentries,
            $econLevel, $popLevel, $armoryLevel
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function findReportsByAttackerId(int $attackerId): array
    {
        // CHANGED: ORDER BY r.id DESC for precise LIFO retrieval during tests
        $sql = "
            SELECT r.*, d.character_name as defender_name, a.character_name as attacker_name
            FROM spy_reports r
            JOIN users d ON r.defender_id = d.id 
            JOIN users a ON r.attacker_id = a.id
            WHERE r.attacker_id = ? 
            ORDER BY r.id DESC 
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

    public function findReportsByDefenderId(int $defenderId): array
    {
        // CHANGED: ORDER BY r.id DESC
        $sql = "
            SELECT r.*, d.character_name as defender_name, a.character_name as attacker_name
            FROM spy_reports r
            JOIN users d ON r.defender_id = d.id
            JOIN users a ON r.attacker_id = a.id
            WHERE r.defender_id = ? 
            ORDER BY r.id DESC 
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

    public function findReportById(int $reportId, int $viewerId): ?SpyReport
    {
        $sql = "
            SELECT r.*, d.character_name as defender_name, a.character_name as attacker_name
            FROM spy_reports r
            JOIN users d ON r.defender_id = d.id
            JOIN users a ON r.attacker_id = a.id
            WHERE r.id = ? AND (r.attacker_id = ? OR r.defender_id = ?)
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$reportId, $viewerId, $viewerId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    public function findLatestDefenseByAlliance(int $allianceId): ?array
    {
        $sql = "
            SELECT r.*, 
                   d.character_name as defender_name, 
                   a.character_name as attacker_name,
                   TIMESTAMPDIFF(SECOND, r.created_at, NOW()) as seconds_ago
            FROM spy_reports r
            JOIN users d ON r.defender_id = d.id
            JOIN users a ON r.attacker_id = a.id
            WHERE d.alliance_id = ?
            ORDER BY r.created_at DESC
            LIMIT 1
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$allianceId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function hydrate(array $data): SpyReport
    {
        return new SpyReport(
            id: (int)$data['id'],
            attacker_id: (int)$data['attacker_id'],
            defender_id: (int)$data['defender_id'],
            created_at: $data['created_at'],
            operation_result: $data['operation_result'],
            spies_sent: (int)$data['spies_sent'],
            spies_lost_attacker: (int)$data['spies_lost_attacker'],
            sentries_lost_defender: (int)$data['sentries_lost_defender'],
            defender_workers_lost: (int)($data['defender_workers_lost'] ?? 0), // New
            defender_total_sentries: (int)($data['defender_total_sentries'] ?? 0),
            credits_seen: isset($data['credits_seen']) ? (int)$data['credits_seen'] : null,
            gemstones_seen: isset($data['gemstones_seen']) ? (int)$data['gemstones_seen'] : null,
            workers_seen: isset($data['workers_seen']) ? (int)$data['workers_seen'] : null,
            soldiers_seen: isset($data['soldiers_seen']) ? (int)$data['soldiers_seen'] : null,
            guards_seen: isset($data['guards_seen']) ? (int)$data['guards_seen'] : null,
            spies_seen: isset($data['spies_seen']) ? (int)$data['spies_seen'] : null,
            sentries_seen: isset($data['sentries_seen']) ? (int)$data['sentries_seen'] : null,
            economy_upgrade_level_seen: isset($data['economy_upgrade_level_seen']) ? (int)$data['economy_upgrade_level_seen'] : null,
            population_level_seen: isset($data['population_level_seen']) ? (int)$data['population_level_seen'] : null,
            armory_level_seen: isset($data['armory_level_seen']) ? (int)$data['armory_level_seen'] : null,
            defender_name: $data['defender_name'] ?? null,
            attacker_name: $data['attacker_name'] ?? null
        );
    }
}