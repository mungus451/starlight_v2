<?php

/**
 * Game Balance Configuration
 *
 * This file stores all the "magic numbers" for game balance,
 * such as unit costs, build times, etc.
 */

return [
    'training' => [
        // Unit costs are [ 'credits' => X, 'citizens' => Y ]
        'workers'  => ['credits' => 100, 'citizens' => 1],
        'soldiers' => ['credits' => 1000, 'citizens' => 1],
        'guards'   => ['credits' => 2500, 'citizens' => 1],
        'spies'    => ['credits' => 10000, 'citizens' => 1],
        'sentries' => ['credits' => 5000, 'citizens' => 1],
    ],

    // --- NEW: Phase 5 ---
    // Structure costs are calculated as: base_cost * (multiplier ^ (level - 1))
    // The key (e.g., 'fortification') MUST match the column name minus '_level'
    'structures' => [
        'fortification' => [
            'name' => 'Fortification',
            'base_cost' => 100000,
            'multiplier' => 1.8
        ],
        'offense_upgrade' => [
            'name' => 'Offense Upgrade',
            'base_cost' => 50000,
            'multiplier' => 2.0
        ],
        'defense_upgrade' => [
            'name' => 'Defense Upgrade',
            'base_cost' => 50000,
            'multiplier' => 2.0
        ],
        'spy_upgrade' => [
            'name' => 'Spy Upgrade',
            'base_cost' => 75000,
            'multiplier' => 1.9
        ],
        'economy_upgrade' => [
            'name' => 'Economy Upgrade',
            'base_cost' => 200000,
            'multiplier' => 1.7
        ],
        'population' => [
            'name' => 'Population',
            'base_cost' => 150000,
            'multiplier' => 1.6
        ],
        'armory' => [
            'name' => 'Armory',
            'base_cost' => 120000,
            'multiplier' => 2.1
        ],
    ]
];