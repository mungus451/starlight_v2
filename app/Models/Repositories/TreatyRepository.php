<?php

namespace App\Models\Repositories;

use App\Models\Entities\Treaty;
use PDO;

/**
 * Handles all database operations for the 'treaties' table.
 */
class TreatyRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Finds a treaty by its ID.
     *
     * @param int $treatyId
     * @return Treaty|null
     */
    public function findById(int $treatyId): ?Treaty
    {
        $stmt = $this->db->prepare("SELECT * FROM treaties WHERE id = ?");
        $stmt->execute([$treatyId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Finds all treaties (both proposed and received) for a specific alliance.
     *
     * @param int $allianceId
     * @return Treaty[]
     */
    public function findByAllianceId(int $allianceId): array
    {
        $sql = "
            SELECT 
                t.*,
                a1.name as alliance1_name,
                a2.name as alliance2_name
            FROM treaties t
            JOIN alliances a1 ON t.alliance1_id = a1.id
            JOIN alliances a2 ON t.alliance2_id = a2.id
            WHERE t.alliance1_id = ? OR t.alliance2_id = ?
            ORDER BY t.status = 'proposed' DESC, t.updated_at DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$allianceId, $allianceId]);
        
        $treaties = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $treaties[] = $this->hydrate($row);
        }
        return $treaties;
    }

    /**
     * Creates a new treaty proposal.
     *
     * @param int $alliance1Id (Proposer)
     * @param int $alliance2Id (Target)
     * @param string $treatyType
     * @param string $terms
     * @return int The ID of the new treaty
     */
    public function createTreaty(int $alliance1Id, int $alliance2Id, string $treatyType, string $terms): int
    {
        $sql = "
            INSERT INTO treaties 
                (alliance1_id, alliance2_id, treaty_type, terms, status)
            VALUES
                (?, ?, ?, ?, 'proposed')
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$alliance1Id, $alliance2Id, $treatyType, $terms]);
        
        return (int)$this->db->lastInsertId();
    }

    /**
     * Updates the status of an existing treaty.
     *
     * @param int $treatyId
     * @param string $newStatus ('active', 'declined', 'broken', 'expired')
     * @return bool
     */
    public function updateTreatyStatus(int $treatyId, string $newStatus): bool
    {
        $sql = "UPDATE treaties SET status = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$newStatus, $treatyId]);
    }

    /**
     * Helper method to convert a database row into a Treaty entity.
     *
     * @param array $data
     * @return Treaty
     */
    private function hydrate(array $data): Treaty
    {
        return new Treaty(
            id: (int)$data['id'],
            alliance1_id: (int)$data['alliance1_id'],
            alliance2_id: (int)$data['alliance2_id'],
            treaty_type: $data['treaty_type'],
            status: $data['status'],
            terms: $data['terms'] ?? null,
            expiration_date: $data['expiration_date'] ?? null,
            created_at: $data['created_at'],
            updated_at: $data['updated_at'],
            alliance1_name: $data['alliance1_name'] ?? null,
            alliance2_name: $data['alliance2_name'] ?? null
        );
    }
}