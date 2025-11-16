<?php

namespace App\Models\Repositories;

use App\Models\Entities\Rivalry;
use PDO;

/**
 * Handles all database operations for the 'rivalries' table.
 */
class RivalryRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Finds all rivalries for a specific alliance.
     *
     * @param int $allianceId
     * @return Rivalry[]
     */
    public function findByAllianceId(int $allianceId): array
    {
        $sql = "
            SELECT 
                r.*,
                a1.name as alliance_a_name,
                a2.name as alliance_b_name
            FROM rivalries r
            JOIN alliances a1 ON r.alliance_a_id = a1.id
            JOIN alliances a2 ON r.alliance_b_id = a2.id
            WHERE r.alliance_a_id = ? OR r.alliance_b_id = ?
            ORDER BY r.heat_level DESC, r.last_attack_date DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$allianceId, $allianceId]);
        
        $rivalries = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rivalries[] = $this->hydrate($row);
        }
        return $rivalries;
    }

    /**
     * Creates or updates a rivalry (increases heat level).
     *
     * @param int $alliance1Id
     * @param int $alliance2Id
     * @return bool
     */
    public function createOrUpdateRivalry(int $alliance1Id, int $alliance2Id): bool
    {
        // Normalize IDs to ensure a < b
        $allianceA = min($alliance1Id, $alliance2Id);
        $allianceB = max($alliance1Id, $alliance2Id);

        $sql = "
            INSERT INTO rivalries (alliance_a_id, alliance_b_id, heat_level, last_attack_date)
            VALUES (?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE
                heat_level = heat_level + 1,
                last_attack_date = NOW()
        ";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$allianceA, $allianceB]);
    }

    /**
     * Helper method to convert a database row into a Rivalry entity.
     *
     * @param array $data
     * @return Rivalry
     */
    private function hydrate(array $data): Rivalry
    {
        return new Rivalry(
            id: (int)$data['id'],
            alliance_a_id: (int)$data['alliance_a_id'],
            alliance_b_id: (int)$data['alliance_b_id'],
            heat_level: (int)$data['heat_level'],
            last_attack_date: $data['last_attack_date'] ?? null,
            created_at: $data['created_at'],
            alliance_a_name: $data['alliance_a_name'] ?? null,
            alliance_b_name: $data['alliance_b_name'] ?? null
        );
    }
}