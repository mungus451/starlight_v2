<?php

namespace App\Models\Repositories;

use App\Models\Entities\AllianceStructure;
use PDO;

/**
 * Handles all database operations for the 'alliance_structures' table.
 */
class AllianceStructureRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Finds all structures owned by a specific alliance.
     *
     * @param int $allianceId
     * @return array [structure_key => AllianceStructure]
     */
    public function findByAllianceId(int $allianceId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM alliance_structures WHERE alliance_id = ?");
        $stmt->execute([$allianceId]);
        
        $structures = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $structures[$row['structure_key']] = $this->hydrate($row);
        }
        return $structures;
    }

    /**
     * Finds a single structure owned by an alliance.
     *
     * @param int $allianceId
     * @param string $structureKey
     * @return AllianceStructure|null
     */
    public function findByAllianceAndKey(int $allianceId, string $structureKey): ?AllianceStructure
    {
        $stmt = $this->db->prepare("SELECT * FROM alliance_structures WHERE alliance_id = ? AND structure_key = ?");
        $stmt->execute([$allianceId, $structureKey]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Atomically creates a new structure row (at level 1) or updates its level.
     *
     * @param int $allianceId
     * @param string $structureKey
     * @param int $newLevel
     * @return bool
     */
    public function createOrUpgrade(int $allianceId, string $structureKey, int $newLevel): bool
    {
        $sql = "
            INSERT INTO alliance_structures (alliance_id, structure_key, level)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
                level = VALUES(level)
        ";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$allianceId, $structureKey, $newLevel]);
    }

    /**
     * Helper method to convert a database row into an AllianceStructure entity.
     *
     * @param array $data
     * @return AllianceStructure
     */
    private function hydrate(array $data): AllianceStructure
    {
        return new AllianceStructure(
            id: (int)$data['id'],
            alliance_id: (int)$data['alliance_id'],
            structure_key: $data['structure_key'],
            level: (int)$data['level'],
            created_at: $data['created_at'],
            updated_at: $data['updated_at']
        );
    }
}