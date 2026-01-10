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
        'fortification' => [
            'name' => 'Fortification',
            'base_cost' => 3400,
            'multiplier' => 1.54,
            'category' => 'Defense',
            'description' => 'Increases the base power of your Guards and overall structural integrity.'
        ],
        'offense_upgrade' => [
            'name' => 'Offense Upgrade',
            'base_cost' => 5600,
            'multiplier' => 1.51,
            'category' => 'Offense',
            'description' => 'Increases the base power of your Soldiers in offensive operations.'
        ],
        'defense_upgrade' => [
            'name' => 'Defense Upgrade',
            'base_cost' => 5500,
            'multiplier' => 1.53,
            'category' => 'Defense',
            'description' => 'Increases the effectiveness of your Guards and Fortifications on defense.'
        ],
        'spy_upgrade' => [
            'name' => 'Spy Upgrade',
            'base_cost' => 3500,
            'multiplier' => 1.49,
            'category' => 'Intel',
            'description' => 'Improves spy success rates and counter-espionage capabilities.'
        ],
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
        'accounting_firm' => [
            'name' => 'Accounting Firm',
            'base_cost' => 1000, 
            'base_crystal_cost' => 250, 
            'multiplier' => 1.47, 
            'category' => 'Economy',
            'description' => 'Increases passive credit income by 1% per level.'
        ],
        
        // --- Age of Ascension Structures ---
        'quantum_research_lab' => [
            'name' => 'Quantum Research Lab',
            'base_cost' => 5000,
            'base_crystal_cost' => 50,
            'multiplier' => 1.7,
            'category' => 'Intel',
            'description' => 'Generates Research Data each turn, unlocking powerful Doctrines.'
        ],
        'nanite_forge' => [
            'name' => 'Nanite Forge',
            'base_cost' => 7500000,
            'base_crystal_cost' => 20000,
            'multiplier' => 1.48,
            'category' => 'Military',
            'description' => 'Reduces casualties in winning battles, making your armies more efficient.'
        ],
        'dark_matter_siphon' => [
            'name' => 'Dark Matter Siphon',
            'base_cost' => 25000,
            'base_crystal_cost' => 100,
            'multiplier' => 1.47,
            'category' => 'Advanced Industry',
            'description' => 'Generates rare Dark Matter used for constructing superstructures and advanced weaponry.'
        ],
        'planetary_shield' => [
            'name' => 'Planetary Shield',
            'base_cost' => 50000,
            'base_dark_matter_cost' => 50,
            'multiplier' => 1.15,
            'category' => 'Super Defense',
            'description' => 'Creates a powerful shield that must be depleted before your assets can be plundered.'
        ],
        'naquadah_mining_complex' => [
            'name' => 'Naquadah Mining Complex',
            'base_cost' => 10000,
            'base_dark_matter_cost' => 1,
            'multiplier' => 1.47,
            'category' => 'Advanced Industry',
            'description' => 'Generates Naquadah Crystals each turn.'
        ],
        'protoform_vat' => [
            'name' => 'Protoform Vat',
            'base_cost' => 10000,
            'base_crystal_cost' => 200,
            'multiplier' => 1.9,
            'category' => 'Advanced Industry',
            'description' => 'Cultivates Protoform, a biological resource required for elite units.'
        ],
        'weapon_vault' => [
            'name' => 'Weapon Vault',
            'base_cost' => 25000,
            'base_crystal_cost' => 500,
            'multiplier' => 2.1,
            'category' => 'Military',
            'description' => 'Allows the construction of advanced weapons and armor for your Generals.'
        ],
        'embassy' => [
            'name' => 'Embassy',
            'base_cost' => 100000,
            'base_crystal_cost' => 2000,
            'multiplier' => 2.5,
            'category' => 'Intel',
            'description' => 'Unlocks strategic Doctrines that provide powerful, empire-wide bonuses.'
        ],

        // --- NEW EXPANSION STRUCTURES ---
        
        // ECONOMY
        'fusion_plant' => [
            'name' => 'Fusion Plant',
            'base_cost' => 50000,
            'base_crystal_cost' => 500,
            'multiplier' => 1.55,
            'category' => 'Economy',
            'description' => 'Multiplies the output of all resource collectors by 0.5% per level.'
        ],
        'orbital_trade_port' => [
            'name' => 'Orbital Trade Port',
            'base_cost' => 75000,
            'base_crystal_cost' => 1000,
            'multiplier' => 1.6,
            'category' => 'Economy',
            'description' => 'Reduces the Crystal cost of Black Market items.'
        ],
        'banking_datacenter' => [
            'name' => 'Banking Datacenter',
            'base_cost' => 25000,
            'base_crystal_cost' => 250,
            'multiplier' => 1.5,
            'category' => 'Economy',
            'description' => 'Accelerates the regeneration of Bank Deposit Charges.'
        ],

        // MILITARY
        'cloning_vats' => [
            'name' => 'Cloning Vats',
            'base_cost' => 40000,
            'base_crystal_cost' => 400,
            'multiplier' => 1.52,
            'category' => 'Military',
            'description' => 'Reduces the Credit cost of training Soldiers and Guards.'
        ],
        'war_college' => [
            'name' => 'War College',
            'base_cost' => 60000,
            'base_crystal_cost' => 600,
            'multiplier' => 1.58,
            'category' => 'Military',
            'description' => 'Increases the XP gained by your Commander from battles.'
        ],
        'mercenary_outpost' => [
            'name' => 'Mercenary Outpost',
            'base_cost' => 100000,
            'base_dark_matter_cost' => 100,
            'multiplier' => 1.65,
            'category' => 'Military',
            'description' => 'Allows for the instant emergency drafting of units using Dark Matter.'
        ],

        // DEFENSE
        'phase_bunker' => [
            'name' => 'Phase Bunker',
            'base_cost' => 30000,
            'base_crystal_cost' => 300,
            'multiplier' => 1.53,
            'category' => 'Defense',
            'description' => 'Protects a percentage of unbanked resources from plunder.'
        ],
        'ion_cannon_network' => [
            'name' => 'Ion Cannon Network',
            'base_cost' => 80000,
            'base_crystal_cost' => 800,
            'multiplier' => 1.62,
            'category' => 'Defense',
            'description' => 'Deals pre-battle damage to attacking fleets.'
        ],

        // INTEL
        'neural_uplink' => [
            'name' => 'Neural Uplink',
            'base_cost' => 35000,
            'base_crystal_cost' => 350,
            'multiplier' => 1.5,
            'category' => 'Intel',
            'description' => 'Increases the counter-espionage efficiency of your Sentries.'
        ],
        'subspace_scanner' => [
            'name' => 'Subspace Scanner',
            'base_cost' => 45000,
            'base_crystal_cost' => 450,
            'multiplier' => 1.54,
            'category' => 'Intel',
            'description' => 'Improves the accuracy of incoming attack notifications.'
        ],
    ],
    'upkeep' => [
        'general' => [
            'protoform' => 10, 
        ],
        'scientist' => [
            'protoform' => 5, 
        ],
    ],
    // --- Phase 7 ---
    'spy' => [
        'attack_turn_cost' => 1,
        'cost_per_spy' => 1000, // Credits
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
        'crystal_steal_rate' => 0.05,
        'dark_matter_steal_rate' => 0.02,
        'protoform_steal_rate' => 0.01,
        
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

        'nanite_casualty_reduction_per_level' => 0.01,
        'max_nanite_casualty_reduction' => 0.50,
        'shield_hp_per_level' => 25,

        // New: War College
        'war_college_xp_bonus_per_level' => 0.02, // 2% XP bonus per level

        // New: Phase Bunker
        'phase_bunker_protection_per_level' => 0.005, // 0.5% protection per level
        'max_phase_bunker_protection' => 0.20, // Max 20%

        // New: Ion Cannon Network
        'ion_cannon_damage_per_level' => 0.001, // 0.1% of enemy force
        'max_ion_cannon_damage' => 0.05, // Max 5%
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
        'research_data_per_lab_level' => 100,
        'dark_matter_per_siphon_level' => 5.0,
        'dark_matter_production_multiplier' => 1.05,
        'naquadah_per_mining_complex_level' => 10,
        'naquadah_production_multiplier' => 1.01,
        'protoform_per_vat_level' => 5,
        'accounting_firm_base_bonus' => 0.01,
        'accounting_firm_multiplier' => 1.05,

        // New: Fusion Plant
        'fusion_plant_bonus_per_level' => 0.005, // 0.5% bonus per level
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
    'generals' => [
        'base_capacity' => 50000000,
        'capacity_per_general' => 250000
    ],

    // New: Black Market (Generic or specific)
    'black_market' => [
        'orbital_trade_port_discount_per_level' => 0.005, // 0.5%
        'max_orbital_trade_port_discount' => 0.25 // 25%
    ],

    // New: Banking
    'bank' => [
        'banking_datacenter_regen_reduction_minutes' => 10, // 10 minutes per level
        'max_banking_datacenter_reduction_minutes' => 180 // 3 hours
    ],

    'war' => [
        'strategic_objective_points' => 2500,
        'eligible_strategic_targets' => [
            'fortification',
            'offense_upgrade',
            'armory',
            'planetary_shield',
            'ion_cannon_network'
        ],
        'min_level_for_strategic_targets' => 5
    ],
];