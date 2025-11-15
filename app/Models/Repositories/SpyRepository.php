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

    /**
     * Creates a new spy report.
     * This is a large query that takes all intel data.
     *
     * @return int The ID of the new report
     */
    public function createReport(
        int $attackerId,
        int $defenderId,
        string $result,
        int $spiesSent,
        int $spiesLost,
        int $sentriesLost,
        ?int $credits, ?int $gemstones, ?int $workers, ?int $soldiers, ?int $guards, ?int $spies, ?int $sentries,
        ?int $fortLevel, ?int $offenseLevel, ?int $defenseLevel, ?int $spyLevel, ?int $econLevel, ?int $popLevel, ?int $armoryLevel
    ): int {
        $sql = "
            INSERT INTO spy_reports 
                (attacker_id, defender_id, operation_result, spies_sent, spies_lost_attacker, sentries_lost_defender,
                 credits_seen, gemstones_seen, workers_seen, soldiers_seen, guards_seen, spies_seen, sentries_seen,
                 fortification_level_seen, offense_upgrade_level_seen, defense_upgrade_level_seen, 
                 spy_upgrade_level_seen, economy_upgrade_level_seen, population_level_seen, armory_level_seen)
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $attackerId, $defenderId, $result, $spiesSent, $spiesLost, $sentriesLost,
            $credits, $gemstones, $workers, $soldiers, $guards, $spies, $sentries,
            $fortLevel, $offenseLevel, $defenseLevel, $spyLevel, $econLevel, $popLevel, $armoryLevel
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * --- MODIFIED METHOD ---
     * Finds the 50 most recent reports for an attacker.
     *
     * @param int $attackerId
     * @return SpyReport[]
     */
    public function findReportsByAttackerId(int $attackerId): array
    {
        $sql = "
            SELECT r.*, d.character_name as defender_name, a.character_name as attacker_name
            FROM spy_reports r
            JOIN users d ON r.defender_id = d.id 
            JOIN users a ON r.attacker_id = a.id
            WHERE r.attacker_id = ? 
            ORDER BY r.created_at DESC 
            LIMIT 50
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$attackerId]);
        
        $reports = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // --- THIS IS THE FIX ---
            $reports[] = $this->hydrate($row);
        }
        return $reports;
    }

    /**
     * --- NEW METHOD ---
     * Finds the 50 most recent reports for a defender.
     *
     * @param int $defenderId
     * @return SpyReport[]
     */
    public function findReportsByDefenderId(int $defenderId): array
    {
        $sql = "
            SELECT r.*, d.character_name as defender_name, a.character_name as attacker_name
            FROM spy_reports r
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
     * --- MODIFIED METHOD ---
     * Finds a single report by its ID, ensuring the viewer is the attacker or defender.
     *
     * @param int $reportId
     * @param int $viewerId
     * @return SpyReport|null
     */
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

        // --- THIS IS THE FIX ---
        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Helper method to convert a database row into a SpyReport entity.
     *
     * @param array $data
     * @return SpyReport
     */
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
            credits_seen: isset($data['credits_seen']) ? (int)$data['credits_seen'] : null,
            gemstones_seen: isset($data['gemstones_seen']) ? (int)$data['gemstones_seen'] : null,
            workers_seen: isset($data['workers_seen']) ? (int)$data['workers_seen'] : null,
            soldiers_seen: isset($data['soldiers_seen']) ? (int)$data['soldiers_seen'] : null,
            guards_seen: isset($data['guards_seen']) ? (int)$data['guards_seen'] : null,
            spies_seen: isset($data['spies_seen']) ? (int)$data['spies_seen'] : null,
            sentries_seen: isset($data['sentries_seen']) ? (int)$data['sentries_seen'] : null,
            fortification_level_seen: isset($data['fortification_level_seen']) ? (int)$data['fortification_level_seen'] : null,
            offense_upgrade_level_seen: isset($data['offense_upgrade_level_seen']) ? (int)$data['offense_upgrade_level_seen'] : null,
            defense_upgrade_level_seen: isset($data['defense_upgrade_level_seen']) ? (int)$data['defense_upgrade_level_seen'] : null,
            spy_upgrade_level_seen: isset($data['spy_upgrade_level_seen']) ? (int)$data['spy_upgrade_level_seen'] : null,
            economy_upgrade_level_seen: isset($data['economy_upgrade_level_seen']) ? (int)$data['economy_upgrade_level_seen'] : null,
            population_level_seen: isset($data['population_level_seen']) ? (int)$data['population_level_seen'] : null,
            armory_level_seen: isset($data['armory_level_seen']) ? (int)$data['armory_level_seen'] : null,
            defender_name: $data['defender_name'] ?? null,
            attacker_name: $data['attacker_name'] ?? null
        );
    }
}