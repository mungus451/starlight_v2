<?php

namespace App\Models\Repositories;

use App\Models\Entities\War;
use PDO;

/**
 * Handles all database operations for the 'wars' table.
 */
class WarRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Finds a war by its ID.
     *
     * @param int $warId
     * @return War|null
     */
    public function findById(int $warId): ?War
    {
        $stmt = $this->db->prepare("SELECT * FROM wars WHERE id = ?");
        $stmt->execute([$warId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Finds any active war between two specific alliances.
     *
     * @param int $alliance1Id
     * @param int $alliance2Id
     * @return War|null
     */
    public function findActiveWarBetween(int $alliance1Id, int $alliance2Id): ?War
    {
        $sql = "
            SELECT * FROM wars 
            WHERE status = 'active' 
            AND (
                (declarer_alliance_id = ? AND declared_against_alliance_id = ?) OR
                (declarer_alliance_id = ? AND declared_against_alliance_id = ?)
            )
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$alliance1Id, $alliance2Id, $alliance2Id, $alliance1Id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Finds the first active war an alliance is involved in.
     *
     * @param int $allianceId
     * @return War|null
     */
    public function findActiveWarByAllianceId(int $allianceId): ?War
    {
        $sql = "
            SELECT 
                w.*, 
                a1.name as declarer_name, 
                a2.name as defender_name
            FROM wars w
            JOIN alliances a1 ON w.declarer_alliance_id = a1.id
            JOIN alliances a2 ON w.declared_against_alliance_id = a2.id
            WHERE w.status = 'active' 
            AND (w.declarer_alliance_id = ? OR w.declared_against_alliance_id = ?)
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$allianceId, $allianceId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Creates a new war declaration.
     *
     * @param string $name
     * @param int $declarerId
     * @param int $defenderId
     * @param string $casusBelli
     * @param string $goalKey
     * @param int $goalThreshold
     * @return int The ID of the new war
     */
    public function createWar(
        string $name,
        int $declarerId,
        int $defenderId,
        string $casusBelli,
        string $goalKey,
        int $goalThreshold
    ): int {
        $sql = "
            INSERT INTO wars 
                (name, declarer_alliance_id, declared_against_alliance_id, casus_belli, 
                 status, goal_key, goal_threshold)
            VALUES
                (?, ?, ?, ?, 'active', ?, ?)
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$name, $declarerId, $defenderId, $casusBelli, $goalKey, $goalThreshold]);
        
        return (int)$this->db->lastInsertId();
    }

    /**
     * Atomically updates the score for one side of a war.
     *
     * @param int $warId
     * @param bool $isDeclarer
     * @param int $scoreGained
     * @return bool
     */
    public function updateWarScore(int $warId, bool $isDeclarer, int $scoreGained): bool
    {
        $fieldToUpdate = $isDeclarer ? 'declarer_score' : 'defender_score';
        
        $sql = "
            UPDATE wars 
            SET {$fieldToUpdate} = {$fieldToUpdate} + ?
            WHERE id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$scoreGained, $warId]);
    }

    /**
     * Helper method to convert a database row into a War entity.
     *
     * @param array $data
     * @return War
     */
    private function hydrate(array $data): War
    {
        return new War(
            id: (int)$data['id'],
            name: $data['name'],
            war_type: $data['war_type'],
            declarer_alliance_id: (int)$data['declarer_alliance_id'],
            declared_against_alliance_id: (int)$data['declared_against_alliance_id'],
            casus_belli: $data['casus_belli'] ?? null,
            status: $data['status'],
            goal_key: $data['goal_key'],
            goal_threshold: (int)$data['goal_threshold'],
            declarer_score: (int)$data['declarer_score'],
            defender_score: (int)$data['defender_score'],
            winner_alliance_id: isset($data['winner_alliance_id']) ? (int)$data['winner_alliance_id'] : null,
            start_time: $data['start_time'],
            end_time: $data['end_time'] ?? null,
            declarer_name: $data['declarer_name'] ?? null,
            defender_name: $data['defender_name'] ?? null
        );
    }
}