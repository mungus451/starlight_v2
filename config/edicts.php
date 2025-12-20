<?php

/**
 * Edict Definitions
 * 
 * Defines all available planetary directives.
 * Keys are used for database storage.
 */

return [
    'ferengi_principle' => [
        'name' => 'The Ferengi Principle',
        'description' => '+15% Credit Income, -10% Defense Power.',
        'lore' => 'Greed is eternal.',
        'type' => 'economic',
        'modifiers' => [
            'credit_income_percent' => 0.15,
            'defense_power_percent' => -0.10
        ],
        'upkeep_cost' => 0,
        'upkeep_resource' => 'credits'
    ],
    'matter_reorganization' => [
        'name' => 'Matter Reorganization',
        'description' => '-20% Structure Upgrade Costs, -50% Citizen Growth.',
        'lore' => 'Divert power from life support to the Nanite Forges.',
        'type' => 'economic',
        'modifiers' => [
            'structure_cost_percent' => -0.20,
            'citizen_growth_percent' => -0.50
        ],
        'upkeep_cost' => 0,
        'upkeep_resource' => 'credits'
    ],
    'synthetic_integration' => [
        'name' => 'Synthetic Integration',
        'description' => '+20% Resource Production, +500 Credits Upkeep per Worker.',
        'lore' => 'Replace biological workers with reliable droids.',
        'type' => 'economic',
        'modifiers' => [
            'resource_production_percent' => 0.20,
            'worker_upkeep_flat' => 500
        ],
        'upkeep_cost' => 0,
        'upkeep_resource' => 'credits'
    ],
    'prime_directive' => [
        'name' => 'The Prime Directive',
        'description' => '+20% Defense Power, +10% Shield HP. Cannot attack.',
        'lore' => 'Non-interference requires absolute defense.',
        'type' => 'military',
        'modifiers' => [
            'defense_power_percent' => 0.20,
            'shield_hp_percent' => 0.10,
            'flag_cannot_attack' => true
        ],
        'upkeep_cost' => 0,
        'upkeep_resource' => 'credits'
    ],
    'klingon_doctrine' => [
        'name' => 'Klingon Doctrine',
        'description' => '+15% Offense Power, +20% Casualties taken.',
        'lore' => 'Today is a good day to die.',
        'type' => 'military',
        'modifiers' => [
            'offense_power_percent' => 0.15,
            'casualties_percent' => 0.20
        ],
        'upkeep_cost' => 0,
        'upkeep_resource' => 'credits'
    ],
    'total_war_economy' => [
        'name' => 'Total War Economy',
        'description' => '-30% Unit Training Costs, 0% Bank Interest.',
        'lore' => 'All industry serves the fleet.',
        'type' => 'military',
        'modifiers' => [
            'unit_cost_percent' => -0.30,
            'bank_interest_mult' => 0.0
        ],
        'upkeep_cost' => 0,
        'upkeep_resource' => 'credits'
    ],
    'section_31_protocols' => [
        'name' => 'Section 31 Protocols',
        'description' => '+25% Spy Success Rate, -20% War Prestige gains.',
        'lore' => 'Exist in the shadows.',
        'type' => 'espionage',
        'modifiers' => [
            'spy_success_percent' => 0.25,
            'war_prestige_percent' => -0.20
        ],
        'upkeep_cost' => 0,
        'upkeep_resource' => 'credits'
    ],
    'babel_treaty' => [
        'name' => 'The Babel Treaty',
        'description' => 'Alliance Taxes reduced by 50%, -10% Spy Defense.',
        'lore' => 'Open borders encourage cooperation.',
        'type' => 'special',
        'modifiers' => [
            'alliance_tax_percent' => -0.50,
            'spy_defense_percent' => -0.10
        ],
        'upkeep_cost' => 0,
        'upkeep_resource' => 'credits'
    ],
    'project_genesis' => [
        'name' => 'Project Genesis',
        'description' => 'Double Citizen Growth per turn. Costs 5 Energy per turn.',
        'lore' => 'Accelerate terraforming.',
        'type' => 'special',
        'modifiers' => [
            'citizen_growth_mult' => 2.0
        ],
        'upkeep_cost' => 5,
        'upkeep_resource' => 'energy'
    ],
    'scorched_earth' => [
        'name' => 'Scorched Earth',
        'description' => 'Attackers plunder 50% less loot. -10% Total Income.',
        'lore' => 'If we can\'t have it, no one can.',
        'type' => 'special',
        'modifiers' => [
            'plunder_defense_percent' => 0.50,
            'total_income_percent' => -0.10
        ],
        'upkeep_cost' => 0,
        'upkeep_resource' => 'credits'
    ]
];
