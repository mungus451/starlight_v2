<?php

namespace App\Models\Repositories;

use App\Models\Entities\AllianceStructureDefinition;
use PDO;

/**
 * Handles all database operations for the 'alliance_structures_definitions' table.
 * This repository is read-only.
 */
class AllianceStructureDefinitionRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Finds a structure definition by its primary key.
     *
     * @param string $structureKey
     * @return AllianceStructureDefinition|null
     */
    public function findByKey(string $structureKey): ?AllianceStructureDefinition
    {
        $stmt = $this->db->prepare("SELECT * FROM alliance_structures_definitions WHERE structure_key = ?");
        $stmt->execute([$structureKey]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Gets all available structure definitions, ordered by cost.
     *
     * @return AllianceStructureDefinition[]
     */
    public function getAllDefinitions(): array
    {
        $stmt = $this->db->query("SELECT * FROM alliance_structures_definitions ORDER BY base_cost ASC");
        
        $definitions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $definitions[] = $this->hydrate($row);
        }
        return $definitions;
    }

    /**
     * Helper method to convert a database row into an AllianceStructureDefinition entity.
     *
     * @param array $data
     * @return AllianceStructureDefinition
     */
    private function hydrate(array $data): AllianceStructureDefinition
    {
        return new AllianceStructureDefinition(
            structure_key: $data['structure_key'],
            name: $data['name'],
            description: $data['description'],
            base_cost: (int)$data['base_cost'],
            cost_multiplier: (float)$data['cost_multiplier'],
            bonus_text: $data['bonus_text'],
            bonuses_json: $data['bonuses_json']
        );
    }
}