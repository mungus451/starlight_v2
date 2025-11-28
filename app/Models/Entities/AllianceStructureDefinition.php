<?php

namespace App\Models\Entities;

/**
 * Represents a single row from the 'alliance_structures_definitions' table.
 * This is a "dumb" data object defining a purchasable structure.
 */
readonly class AllianceStructureDefinition
{
    /**
     * @param string $structure_key (e.g., 'citadel_shield')
     * @param string $name (e.g., 'Citadel Shield Array')
     * @param string $description
     * @param int $base_cost
     * @param float $cost_multiplier
     * @param string $bonus_text (e.g., '+10% Defense')
     * @param string $bonuses_json (The raw JSON string of bonuses)
     */
    public function __construct(
        public readonly string $structure_key,
        public readonly string $name,
        public readonly string $description,
        public readonly int $base_cost,
        public readonly float $cost_multiplier,
        public readonly string $bonus_text,
        public readonly string $bonuses_json
    ) {
    }

    /**
     * Helper method to decode the bonus JSON.
     * @return array
     */
    public function getBonuses(): array
    {
        return json_decode($this->bonuses_json, true) ?? [];
    }
}