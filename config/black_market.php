<?php

/**
* Black Market Configuration
* Defines costs and logic for The Void Syndicate features.
*/

return [
'costs' => [
'stat_respec' => 250000.0, // Crystals
'turn_refill' => 10000.0, // Crystals
'citizen_package' => 500000000.0, // Crystals
'void_container' => 100000.0, // Crystals
'shadow_contract' => 5000000.0, // Crystals
        'radar_jamming' => 50000000.0, // Crystals
        'safehouse' => 100000000.0, // Crystals
        'high_risk_buff' => 50000000.0, // Crystals
        'safehouse_cracker' => 75000000.0, // Crystals
    ],
'rates' => [
'laundering' => 1.15, // 1.15 Credits per 1 Chip
],

'quantities' => [
'turn_refill_amount' => 50, // Turns restored
'citizen_package_amount' => 50000, // Citizens added
'safehouse_cracker_amount' => 1, // Attacks allowed
],

'durations' => [
    'safehouse_active' => 240, // 4 Hours (Protection)
    'safehouse_cooldown' => 720, // 12 Hours (Total cycle: 4h Safe + 8h Vuln)
],

'mercenary_outpost' => [
    'limit_per_level' => 500, // Max 500 units can be drafted per level of the outpost
    'costs' => [
        'soldiers' => ['dark_matter' => 0.75],
        'guards'   => ['dark_matter' => 1.25],
        'spies'    => ['dark_matter' => 5.0],
        'sentries' => ['dark_matter' => 2.5],
    ],
],

    // Loot Box Probabilities (Weights determine rarity relative to total sum)
    'void_container_loot' => [
        // --- GOOD OUTCOMES ---
        'credits_medium' => [
            'weight' => 15,
            'type' => 'credits',
            'min' => 10000,
            'max' => 500000,
            'label' => 'Cache of Credits'
        ],
        'credits_high' => [
            'weight' => 10,
            'type' => 'credits',
            'min' => 500000,
            'max' => 7500000,
            'label' => 'Vault of Credits'
        ],
        'soldiers' => [
            'weight' => 8,
            'type' => 'unit',
            'unit' => 'soldiers',
            'min' => 500,
            'max' => 2000,
            'label' => 'Mercenary Platoon'
        ],
        'guards' => [
            'weight' => 8,
            'type' => 'unit',
            'unit' => 'guards',
            'min' => 500,
            'max' => 2000,
            'label' => 'Elite Guard Detail'
        ],
        'spies' => [
            'weight' => 5,
            'type' => 'unit',
            'unit' => 'spies',
            'min' => 10,
            'max' => 1500,
            'label' => 'Covert Operatives'
        ],
        'sentries' => [
            'weight' => 5,
            'type' => 'unit',
            'unit' => 'sentries',
            'min' => 10,
            'max' => 1500,
            'label' => 'Security Detail'
        ],
        'jackpot' => [
            'weight' => 1,
            'type' => 'crystals',
            'min' => 500000,
            'max' => 100000000,
            'label' => 'JACKPOT! Naquadah Cache'
        ],
        'dark_matter_drop' => [
            'weight' => 4,
            'type' => 'dark_matter',
            'min' => 50,
            'max' => 500,
            'label' => 'Extracted Dark Matter'
        ],
        'protoform_drop' => [
            'weight' => 4,
            'type' => 'protoform',
            'min' => 25,
            'max' => 250,
            'label' => 'Protoform Canisters'
        ],
        'research_data_drop' => [
            'weight' => 4,
            'type' => 'research_data',
            'min' => 100,
            'max' => 5000,
            'label' => 'Encrypted Data Chips'
        ],

        // --- PROGRESSION & ACTIVITY ---
        'xp_drop_medium' => [
            'weight' => 8,
            'type' => 'xp',
            'min' => 100,
            'max' => 500,
            'label' => 'Combat Data Log'
        ],
        'xp_drop_high' => [
            'weight' => 4,
            'type' => 'xp',
            'min' => 1000,
            'max' => 5000,
            'label' => 'Ancient War Strategy'
        ],
        'turns_drop' => [
            'weight' => 5,
            'type' => 'turns',
            'min' => 25,
            'max' => 100,
            'label' => 'Neural Stim-Pack'
        ],

        // --- BUFFS ---
        'offense_buff' => [
            'weight' => 4,
            'type' => 'buff',
            'buff_key' => 'void_offense_boost',
            'duration' => 1440, // 24 Hours
            'label' => 'Adrenaline Injectors',
            'text' => 'Combat capabilities enhanced. +10% Offense for 24 hours.'
        ],
        'resource_buff' => [
            'weight' => 4,
            'type' => 'buff',
            'buff_key' => 'void_resource_boost',
            'duration' => 360, // 6 Hours
            'label' => 'Nanite Overclock',
            'text' => 'Production systems supercharged. +25% Resource Generation for 6 hours.'
        ],
        'quantum_scrambler' => [
            'weight' => 4,
            'type' => 'buff',
            'buff_key' => 'quantum_scrambler',
            'duration' => 480, // 8 Hours
            'label' => 'Quantum Scrambler',
            'text' => 'Counter-intelligence systems active. +50% Spy Defense for 8 hours.'
        ],
        'cooldown_clear' => [
            'weight' => 2,
            'type' => 'buff', // Using buff handler for positive outcome
            'buff_key' => 'action_clear_safehouse', // Special key
            'label' => 'Safehouse Reboot Key',
            'text' => 'System override detected. Safehouse cooldowns instantly reset.'
        ],

        // --- CURSED LOOT (High Risk, High Reward) ---
        'cursed_crystals' => [
            'weight' => 3,
            'type' => 'cursed',
            'resource' => 'crystals',
            'min' => 1000000,
            'max' => 5000000,
            'debuff_key' => 'radiation_sickness',
            'duration' => 240, // 4 Hours
            'label' => 'Radioactive Isotope Cache',
            'text' => 'You secured the Crystals, but the container was leaking radiation! -20% Income for 4 hours.'
        ],
        'cursed_dark_matter' => [
            'weight' => 2,
            'type' => 'cursed',
            'resource' => 'dark_matter',
            'min' => 200,
            'max' => 1000,
            'debuff_key' => 'radiation_sickness',
            'duration' => 240, // 4 Hours
            'label' => 'Unstable Dark Matter',
            'text' => 'Dark Matter secured, but containment failed! Radiation Sickness: -20% Income for 4 hours.'
        ],

        // --- NEUTRAL OUTCOMES ---
        'space_dust' => [
            'weight' => 5,
            'type' => 'neutral',
            'label' => 'Space Dust',
            'text' => 'You open the container... it contains nothing but cosmic dust.'
        ],
        'scrap_metal' => [
            'weight' => 4,
            'type' => 'neutral',
            'label' => 'Rusted Scrap',
            'text' => 'The container is filled with useless rusted metal shards.'
        ],

        // --- BAD OUTCOMES ---
        'trap_credits' => [
            'weight' => 4,
            'type' => 'credits_loss',
            'min' => 50000,
            'max' => 200000000,
            'label' => 'Credit Siphon Trap',
            'text' => 'IT\'S A TRAP! A hacking algorithm drains your account.'
        ],
        'ambush_spies' => [
            'weight' => 3,
            'type' => 'unit_loss',
            'unit' => 'spies',
            'min' => 10,
            'max' => 2000,
            'label' => 'The Double Cross',
            'text' => 'IT\'S A TRAP! A traitor turned you into their sentries. Casualties sustained.'
        ],
        'ambush_soldiers' => [
            'weight' => 3,
            'type' => 'unit_loss',
            'unit' => 'soldiers',
            'min' => 10,
            'max' => 1000,
            'label' => 'Void Ambush',
            'text' => 'The container was rigged with explosives! Casualties sustained.'
        ],
        'defense_debuff' => [
            'weight' => 5,
            'type' => 'debuff',
            'buff_key' => 'void_defense_penalty',
            'duration' => 360, // 6 Hours
            'label' => 'Shield Disruptor Virus',
            'text' => 'Defensive systems compromised! -30% Defense for 6 hours.'
        ],
        'safehouse_block' => [
            'weight' => 4,
            'type' => 'debuff',
            'buff_key' => 'safehouse_block',
            'duration' => 1440, // 24 Hours
            'label' => 'Safehouse Beacon',
            'text' => 'You\'ve been tagged! Safehouse protocols locked for 24 hours.'
        ]
    ]
];