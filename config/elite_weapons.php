<?php

return [
    'warlord_blade' => [
        'name' => 'Plasma-Rend Greatsword',
        'archetype' => 'Warlord',
        'description' => 'Adds massive Offense Power, but reduces your army\'s total Defense.',
        'cost' => [
            'credits' => 5000000,
            'naquadah_crystals' => 1000,
            'dark_matter' => 100
        ],
        'modifiers' => [
            'flat_offense' => 50000,
            'global_defense_mult' => 0.8, // 20% Reduction
        ]
    ],
    'sentinel_aegis' => [
        'name' => 'Aegis Field Generator',
        'archetype' => 'Sentinel',
        'description' => 'Adds massive Defense Power and Shield HP, but reduces Offense.',
        'cost' => [
            'credits' => 5000000,
            'naquadah_crystals' => 1000,
            'dark_matter' => 100
        ],
        'modifiers' => [
            'flat_defense' => 50000,
            'flat_shield' => 20000,
            'global_offense_mult' => 0.8, // 20% Reduction
        ]
    ],
    'raider_gauntlet' => [
        'name' => 'Matter-Siphon Gauntlet',
        'archetype' => 'Raider',
        'description' => 'Increases Plunder Capacity significantly, but provides no combat stats.',
        'cost' => [
            'credits' => 5000000,
            'naquadah_crystals' => 1000,
            'dark_matter' => 100
        ],
        'modifiers' => [
            'plunder_capacity_mult' => 1.5, // +50% Plunder
            // No combat stats
        ]
    ],
    'tactician_link' => [
        'name' => 'Neural Command Link',
        'archetype' => 'Tactician',
        'description' => 'Reduces casualties taken in battle by optimizing unit movements.',
        'cost' => [
            'credits' => 7500000,
            'naquadah_crystals' => 2000,
            'dark_matter' => 500
        ],
        'modifiers' => [
            'casualty_reduction_mult' => 0.85, // 15% Reduction in losses
            'upkeep_cost' => ['energy' => 100] // Placeholder for future mechanic
        ]
    ]
];
