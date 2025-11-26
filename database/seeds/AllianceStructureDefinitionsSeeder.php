<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class AllianceStructureDefinitionsSeeder extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Populates alliance_structures_definitions with base structures
     */
    public function run(): void
    {
        $data = [
            [
                'structure_key' => 'citadel_shield',
                'name' => 'Citadel Shield Array',
                'description' => 'Grants a global defense bonus to all members of the alliance.',
                'base_cost' => 100000000, // 100M
                'cost_multiplier' => 1.8,
                'bonus_text' => '+10% Defense',
                'bonuses_json' => json_encode([['type' => 'defense_bonus_percent', 'value' => 0.10]])
            ],
            [
                'structure_key' => 'command_nexus',
                'name' => 'Command Nexus',
                'description' => 'Provides a bonus to all credit income for all members.',
                'base_cost' => 150000000, // 150M
                'cost_multiplier' => 1.7,
                'bonus_text' => '+5% Income/Turn',
                'bonuses_json' => json_encode([['type' => 'income_bonus_percent', 'value' => 0.05]])
            ],
            [
                'structure_key' => 'galactic_research_hub',
                'name' => 'Galactic Research Hub',
                'description' => 'Increases all resource production for alliance members.',
                'base_cost' => 120000000, // 120M
                'cost_multiplier' => 1.75,
                'bonus_text' => '+10% Resources',
                'bonuses_json' => json_encode([['type' => 'resource_bonus_percent', 'value' => 0.10]])
            ],
            [
                'structure_key' => 'orbital_training_grounds',
                'name' => 'Orbital Training Grounds',
                'description' => 'Grants a global offense bonus to all members of the alliance.',
                'base_cost' => 100000000, // 100M
                'cost_multiplier' => 1.8,
                'bonus_text' => '+5% Offense',
                'bonuses_json' => json_encode([['type' => 'offense_bonus_percent', 'value' => 0.05]])
            ],
            [
                'structure_key' => 'population_habitat',
                'name' => 'Population Habitat',
                'description' => 'Provides a flat boost to citizen growth per turn for all members.',
                'base_cost' => 50000000, // 50M
                'cost_multiplier' => 1.6,
                'bonus_text' => '+5 Citizens/Turn',
                'bonuses_json' => json_encode([['type' => 'citizen_growth_flat', 'value' => 5]])
            ],
            [
                'structure_key' => 'warlords_throne',
                'name' => "Warlord's Throne",
                'description' => 'A monument to your alliance\'s power. Greatly boosts all other structure bonuses.',
                'base_cost' => 500000000, // 500M
                'cost_multiplier' => 2.5,
                'bonus_text' => '+15% to all bonuses',
                'bonuses_json' => json_encode([['type' => 'all_bonus_multiplier', 'value' => 0.15]])
            ],
        ];

        $table = $this->table('alliance_structures_definitions');
        $table->insert($data)->saveData();
    }
}
