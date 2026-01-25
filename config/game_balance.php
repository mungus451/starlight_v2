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
        'workers'  => ['credits' => 10000, 'citizens' => 1],
        'soldiers' => ['credits' => 15000, 'citizens' => 1],
        'guards'   => ['credits' => 25000, 'citizens' => 1],
        'spies'    => ['credits' => 100000, 'citizens' => 1],
        'sentries' => ['credits' => 50000, 'citizens' => 1],
        
        // New: Cloning Vats Logic
        'cloning_vats_discount_per_level' => 0.01, // 1% discount per level
        'cloning_vats_max_discount' => 0.40, // Max 40% discount
    ],
    // --- Phase 5 ---
    'structures' => [
        // Structure costs are calculated as: base_cost * (multiplier ^ (level - 1))
        'economy_upgrade' => [
            'name' => 'Economy Upgrade',
            'base_cost' => 2000,
            'multiplier' => 1.47,
            'category' => 'Economy',
            'description' => 'Increases your passive credit income generated each turn.'
        ],
        'population' => [
            'name' => 'Population',
            'base_cost' => 1500,
            'multiplier' => 1.52,
            'category' => 'Economy',
            'description' => 'Increases the number of untrained citizens that arrive each turn.'
        ],
        'armory' => [
            'name' => 'Armory',
            'base_cost' => 92000,
            'multiplier' => 1.51,
            'category' => 'Offense',
            'description' => 'Unlocks and improves advanced schematic for military units.'
        ],
        'planetary_shield' => [
            'name' => 'Planetary Shield',
            'base_cost' => 50000,
            'multiplier' => 1.15,
            'category' => 'Super Defense',
            'description' => 'Creates a powerful shield that must be depleted before your assets can be plundered.'
        ],
        'mercenary_outpost' => [
            'name' => 'Mercenary Outpost',
            'base_cost' => 100000,
            'multiplier' => 1.65,
            'category' => 'Military',
            'description' => 'Allows for the instant emergency drafting of units.'
        ],
        'neural_uplink' => [
            'name' => 'Neural Uplink',
            'base_cost' => 35000,
            'multiplier' => 1.5,
            'category' => 'Intel',
            'description' => 'Increases the counter-espionage efficiency of your Sentries.'
        ],
        'subspace_scanner' => [
            'name' => 'Subspace Scanner',
            'base_cost' => 45000,
            'multiplier' => 1.54,
            'category' => 'Intel',
            'description' => 'Improves the accuracy of incoming attack notifications.'
        ],
    ],
    'upkeep' => [
        'general' => [
        ],

    ],
    // --- Phase 7 ---
    'spy' => [
        'attack_turn_cost' => 1,
        'cost_per_spy' => 0, // Credits (Now 0 as per new balance)
        'base_power_per_spy' => 1.0, 
        'base_power_per_sentry' => 1.0, 
        'base_success_multiplier' => 1.5,
        'base_success_chance_floor' => 0.05,
        'base_success_chance_cap' => 0.95,
        'base_counter_spy_multiplier' => 0.5,
        'base_counter_spy_chance_cap' => 0.50,
        'offense_power_per_level' => 0.1,
        'defense_power_per_level' => 0.1,
        'spies_lost_percent_min' => 0.1,
        'spies_lost_percent_max' => 0.3,
        'sentries_lost_percent_min' => 0.15,
        'sentries_lost_percent_max' => 0.35,
        
        // New: Neural Uplink
        'neural_uplink_bonus_per_level' => 0.02, // 2% per level
    ],
    // --- Phase 8 ---
    'attack' => [
        'attack_turn_cost' => 1,
        'global_casualty_scalar' => 0.1,
        'power_per_soldier' => 1.0,
        'power_per_offense_level' => 0.1,
        'power_per_strength_point' => 0.1,
        'power_per_guard' => 1.0,
        'power_per_fortification_level' => 0.1,
        'power_per_defense_level' => 0.1,
        'power_per_constitution_point' => 0.1,
        'winner_loss_percent_min' => 0.05,
        'winner_loss_percent_max' => 0.15,
        'loser_loss_percent_min' => 0.20,
        'loser_loss_percent_max' => 0.40,
        'plunder_percent' => 0.10,
        'net_worth_steal_percent' => 0.05,
        'experience_gain_base' => 500,
        'war_prestige_gain_base' => 5,

        // Worker Casualties
        'worker_casualty_rate_base' => 0.02, // 2% of workers lost on defeat
        'worker_casualty_damage_scalar' => 0.05, // Additional loss scalar based on guard losses

        'shield_hp_per_level' => 25,

    ],
    // --- Phase 9 ---
    'level_up' => [
        'cost_per_point' => 1
    ],
    'armory' => [
        'discount_per_charisma' => 0.01,
        'max_discount' => 0.75
    ],
    // --- Phase 10 ---
    'turn_processor' => [
        'bank_interest_rate' => 0.0003,
        'credit_income_per_econ_level' => 100000,
        'credit_income_per_worker' => 100,
        'credit_bonus_per_wealth_point' => 0.01,
        'citizen_growth_per_pop_level' => 10,
    ],
    // --- Phase 11 ---
    'alliance' => [
        'creation_cost' => 50000000 
    ],
    'alliance_treasury' => [
        'battle_tax_rate' => 0.03,
        'tribute_tax_rate' => 0.05,
        'bank_interest_rate' => 0.005
    ],
    'xp' => [
        'base_xp' => 1000,
        'exponent' => 1.5,
        'rewards' => [
            'battle_win' => 250,
            'battle_loss' => 50,
            'battle_stalemate' => 100,
            'battle_defense_win' => 150,
            'battle_defense_loss' => 25,
            'spy_success' => 100,
            'spy_fail_survived' => 25,
            'spy_caught' => 10,
            'defense_caught_spy' => 75
        ]
    ],
    // New: Banking
    'bank' => [
    ],

    'war' => [
        'strategic_objective_points' => 2500,
        'eligible_strategic_targets' => [
            'armory',
            'planetary_shield',
        ],
        'min_level_for_strategic_targets' => 5
    ],
];