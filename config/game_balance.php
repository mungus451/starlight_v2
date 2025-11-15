<?php

/**
 * Game Balance Configuration
 *
 * This file stores all the "magic numbers" for game balance,
 * such as unit costs, build times, etc.
 */

return [
    // --- Phase 4 ---
    'training' => [
        // Unit costs are [ 'credits' => X, 'citizens' => Y ]
        'workers'  => ['credits' => 100, 'citizens' => 1],
        'soldiers' => ['credits' => 1000, 'citizens' => 1],
        'guards'   => ['credits' => 2500, 'citizens' => 1],
        'spies'    => ['credits' => 10000, 'citizens' => 1],
        'sentries' => ['credits' => 5000, 'citizens' => 1],
    ],
    // --- Phase 5 ---
    'structures' => [
        // Structure costs are calculated as: base_cost * (multiplier ^ (level - 1))
        'fortification' => [
            'name' => 'Fortification',
            'base_cost' => 100000,
            'multiplier' => 1.8,
            'category' => 'Defense',
            'description' => 'Increases the base power of your Guards and overall structural integrity.'
        ],
        'offense_upgrade' => [
            'name' => 'Offense Upgrade',
            'base_cost' => 50000,
            'multiplier' => 2.0,
            'category' => 'Offense',
            'description' => 'Increases the base power of your Soldiers in offensive operations.'
        ],
        'defense_upgrade' => [
            'name' => 'Defense Upgrade',
            'base_cost' => 50000,
            'multiplier' => 2.0,
            'category' => 'Defense',
            'description' => 'Increases the effectiveness of your Guards and Fortifications on defense.'
        ],
        'spy_upgrade' => [
            'name' => 'Spy Upgrade',
            'base_cost' => 75000,
            'multiplier' => 1.9,
            'category' => 'Intel',
            'description' => 'Improves spy success rates and counter-espionage capabilities.'
        ],
        'economy_upgrade' => [
            'name' => 'Economy Upgrade',
            'base_cost' => 200000,
            'multiplier' => 1.7,
            'category' => 'Economy',
            'description' => 'Increases your passive credit income generated each turn.'
        ],
        'population' => [
            'name' => 'Population',
            'base_cost' => 150000,
            'multiplier' => 1.6,
            'category' => 'Economy',
            'description' => 'Increases the number of untrained citizens that arrive each turn.'
        ],
        'armory' => [
            'name' => 'Armory',
            'base_cost' => 120000,
            'multiplier' => 2.1,
            'category' => 'Offense',
            'description' => 'Unlocks and improves advanced schematic for military units.'
        ],
    ],
    // --- Phase 7 ---
    'spy' => [
        'attack_turn_cost' => 1,
        'cost_per_spy' => 1000, // Credits
        'base_power_per_spy' => 1.0, 
        'base_power_per_sentry' => 1.0, 
        'base_success_multiplier' => 1.5,
        'base_success_chance_floor' => 0.05, // 5% min chance
        'base_success_chance_cap' => 0.95,   // 95% max chance
        'base_counter_spy_multiplier' => 0.5,
        'base_counter_spy_chance_cap' => 0.50, // 50% max chance
        'offense_power_per_level' => 0.1, // 10% bonus per spy_upgrade_level
        'defense_power_per_level' => 0.1, // 10% bonus per spy_upgrade_level
        'spies_lost_percent_min' => 0.1,  // 10%
        'spies_lost_percent_max' => 0.3,  // 30%
        'sentries_lost_percent_min' => 0.15, // 15%
        'sentries_lost_percent_max' => 0.35, // 35%
    ],
    // --- Phase 8 ---
    'attack' => [
        'attack_turn_cost' => 1,
        'power_per_soldier' => 1.0,
        'power_per_offense_level' => 0.1,
        'power_per_strength_point' => 0.05,
        'power_per_guard' => 1.0,
        'power_per_fortification_level' => 0.1,
        'power_per_defense_level' => 0.1,
        'power_per_constitution_point' => 0.05,
        'winner_loss_percent_min' => 0.10,
        'winner_loss_percent_max' => 0.20,
        'loser_loss_percent_min' => 0.30,
        'loser_loss_percent_max' => 0.50,
        'plunder_percent' => 0.10,
        'net_worth_steal_percent' => 0.05,
        'experience_gain_base' => 500,
        'war_prestige_gain_base' => 5,
    ],
    // --- Phase 9 ---
    'level_up' => [
        'cost_per_point' => 1
    ],

    // --- Phase 10 ---
    'turn_processor' => [
        // 0.5% interest per turn
        'bank_interest_rate' => 0.005,
        
        // 1,000 credits per turn, per level of Economy Upgrade
        'credit_income_per_econ_level' => 1000,
        
        // --- NEW: Income from workers ---
        'credit_income_per_worker' => 100,

        // --- NEW: Bonus from stats ---
        'credit_bonus_per_wealth_point' => 0.005, // 0.5% bonus to econ/worker income
        
        // 10 new citizens per turn, per level of Population
        'citizen_growth_per_pop_level' => 10
    ],

    // --- Phase 11 ---
    'alliance' => [
        'creation_cost' => 500000 // 500,000 Credits, not 50 Million
    ]
];