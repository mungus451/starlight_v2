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
        // Using named parameters ensures PDO correctly maps the values 
        // and avoids the 'Invalid parameter number' error common with 
        // ON DUPLICATE KEY UPDATE on some MariaDB/PHP configurations.
        $sql = "
            INSERT INTO alliance_structures (alliance_id, structure_key, level)
            VALUES (:aid, :key, :lvl)
            ON DUPLICATE KEY UPDATE
                level = :lvl_update
        ";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'aid' => $allianceId,
            'key' => $structureKey,
            'lvl' => $newLevel,
            'lvl_update' => $newLevel
        ]);
    }

    /**
     * Finds structures that are eligible to be war objectives.
     *
     * @param int $allianceId
     * @param array $eligibleKeys
     * @param int $minLevel
     * @return AllianceStructure[]
     */
    public function findEligibleWarObjectives(int $allianceId, array $eligibleKeys, int $minLevel): array
    {
        if (empty($eligibleKeys)) {
            return [];
        }

        $inClause = implode(',', array_fill(0, count($eligibleKeys), '?'));
        $sql = "
            SELECT * FROM alliance_structures 
            WHERE alliance_id = ? 
            AND structure_key IN ({$inClause})
            AND level >= ?
        ";
        
        $params = array_merge([$allianceId], $eligibleKeys, [$minLevel]);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $structures = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $structures[] = $this->hydrate($row);
        }
        return $structures;
    }

    /**
     * Sets a specific structure as a war objective.
     *
     * @param int $structureId
     * @return bool
     */
    public function setAsWarObjective(int $structureId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE alliance_structures SET is_war_objective = 1 WHERE id = ?"
        );
        return $stmt->execute([$structureId]);
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
            is_war_objective: (bool)$data['is_war_objective'],
            created_at: $data['created_at'],
            updated_at: $data['updated_at']
        );
    }
}