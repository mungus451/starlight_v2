<?php

/**
 * Armory & Loadout Configuration
 *
 * This file stores all data for armory items, including costs, stats,
 * and upgrade (manufacturing) requirements.
 * 
 */

return [
    'soldier' => [
        'title' => 'Soldier Offensive Loadout',
        'unit' => 'soldiers',
        'categories' => [
            'main_weapon' => [
                'title' => 'Heavy Main Weapons',
                'slots' => 1,
                'items' => [
                    'pulse_rifle' => ['name' => 'Pulse Rifle', 'offense' => 40, 'cost_credits' => 70000, 'description' => 'Basic, reliable.'],
                    'railgun' => ['name' => 'Railgun', 'offense' => 80, 'cost_credits' => 140000, 'description' => 'High penetration, slower fire.', 'requires' => 'pulse_rifle', 'armory_level_req' => 10],
                    'plasma_minigun' => ['name' => 'Plasma Minigun', 'offense' => 160, 'cost_credits' => 200000, 'description' => 'Rapid fire, slightly inaccurate.', 'requires' => 'railgun', 'armory_level_req' => 20],
                    'arc_cannon' => ['name' => 'Arc Cannon', 'offense' => 320, 'cost_credits' => 280000, 'description' => 'Chains to nearby enemies.', 'requires' => 'plasma_minigun', 'armory_level_req' => 30],
                    'antimatter_launcher' => ['name' => 'Antimatter Launcher', 'offense' => 640, 'cost_credits' => 400000, 'description' => 'Extremely strong, high cost.', 'requires' => 'arc_cannon', 'armory_level_req' => 40],
                ]
            ],
            'sidearm' => [
                'title' => 'Sidearms',
                'slots' => 1,
                'items' => [
                    'laser_pistol' => ['name' => 'Laser Pistol', 'offense' => 25, 'cost_credits' => 50000, 'description' => 'Basic energy sidearm.'],
                    'stun_blaster' => ['name' => 'Stun Blaster', 'offense' => 50, 'cost_credits' => 100000, 'description' => 'Weak but disables shields briefly.', 'requires' => 'laser_pistol', 'armory_level_req' => 9],
                    'needler_pistol' => ['name' => 'Needler Pistol', 'offense' => 100, 'cost_credits' => 150000, 'description' => 'Seeking rounds, bonus vs. light armor.', 'requires' => 'stun_blaster', 'armory_level_req' => 19],
                    'compact_rail_smg' => ['name' => 'Compact Rail SMG', 'offense' => 200, 'cost_credits' => 200000, 'description' => 'Burst damage, close range.', 'requires' => 'needler_pistol', 'armory_level_req' => 29],
                    'photon_revolver' => ['name' => 'Photon Revolver', 'offense' => 400, 'cost_credits' => 250000, 'description' => 'High crit chance, slower reload.', 'requires' => 'compact_rail_smg', 'armory_level_req' => 39],
                ]
            ],
            'headgear' => [
                'title' => 'Head Gear',
                'slots' => 1,
                'items' => [
                    'tactical_goggles' => ['name' => 'Tactical Goggles', 'offense' => 5, 'cost_credits' => 10000, 'description' => 'Accuracy boost.'],
                    'scout_visor' => ['name' => 'Scout Visor', 'offense' => 10, 'cost_credits' => 20000, 'description' => 'Detects stealth.', 'requires' => 'tactical_goggles', 'armory_level_req' => 7],
                    'heavy_helmet' => ['name' => 'Heavy Helmet', 'offense' => 20, 'cost_credits' => 30000, 'description' => 'Defense bonus, slight weight penalty.', 'requires' => 'scout_visor', 'armory_level_req' => 17],
                    'neural_uplink' => ['name' => 'Neural Uplink', 'offense' => 40, 'cost_credits' => 40000, 'description' => 'Faster reactions, boosts all attacks slightly.', 'requires' => 'heavy_helmet', 'armory_level_req' => 27],
                    'cloak_hood' => ['name' => 'Cloak Hood', 'offense' => 80, 'cost_credits' => 50000, 'description' => 'Stealth advantage, minimal armor.', 'requires' => 'neural_uplink', 'armory_level_req' => 37],
                ]
            ]
        ]
    ],
    'guard' => [
        'title' => 'Guard Defensive Loadout',
        'unit' => 'guards',
        'categories' => [
            'armor_suit' => [
                'title' => 'Defensive Main Equipment (Armor Suits)',
                'slots' => 1,
                'items' => [
                    'light_combat_suit' => ['name' => 'Light Combat Suit', 'defense' => 40, 'cost_credits' => 80000, 'description' => 'Basic protection, minimal weight.'],
                    'titanium_plated_armor' => ['name' => 'Titanium Plated Armor', 'defense' => 80, 'cost_credits' => 160000, 'description' => 'Strong vs. kinetic weapons.', 'requires' => 'light_combat_suit', 'armory_level_req' => 5],
                    'reactive_nano_suit' => ['name' => 'Reactive Nano Suit', 'defense' => 160, 'cost_credits' => 240000, 'description' => 'Reduces energy damage, self-repairs slowly.', 'requires' => 'titanium_plated_armor', 'armory_level_req' => 15],
                    'bulwark_exo_frame' => ['name' => 'Bulwark Exo-Frame', 'defense' => 240, 'cost_credits' => 320000, 'description' => 'Heavy, extreme damage reduction.', 'requires' => 'reactive_nano_suit', 'armory_level_req' => 25],
                    'aegis_shield_suit' => ['name' => 'Aegis Shield Suit', 'defense' => 320, 'cost_credits' => 400000, 'description' => 'Generates energy shield, top-tier defense.', 'requires' => 'bulwark_exo_frame', 'armory_level_req' => 35],
                ]
            ],
            'secondary_defense' => [
                'title' => 'Defensive Side Devices (Secondary Defenses)',
                'slots' => 1,
                'items' => [
                    'kinetic_dampener' => ['name' => 'Kinetic Dampener', 'defense' => 15, 'cost_credits' => 30000, 'description' => 'Reduces ballistic damage.'],
                    'energy_diffuser' => ['name' => 'Energy Diffuser', 'defense' => 30, 'cost_credits' => 60000, 'description' => 'Lowers laser/plasma damage.', 'requires' => 'kinetic_dampener', 'armory_level_req' => 4],
                    'deflector_module' => ['name' => 'Deflector Module', 'defense' => 60, 'cost_credits' => 90000, 'description' => 'Partial shield that recharges slowly.', 'requires' => 'energy_diffuser', 'armory_level_req' => 14],
                    'auto_turret_drone' => ['name' => 'Auto-Turret Drone', 'defense' => 120, 'cost_credits' => 120000, 'description' => 'Assists defense, counters attackers.', 'requires' => 'deflector_module', 'armory_level_req' => 24],
                    'nano_healing_pod' => ['name' => 'Nano-Healing Pod', 'defense' => 240, 'cost_credits' => 150000, 'description' => 'Heals user periodically during battle.', 'requires' => 'auto_turret_drone', 'armory_level_req' => 34],
                ]
            ],
            'defensive_headgear' => [
                'title' => 'Head Gear (Defensive Helmets)',
                'slots' => 1,
                'items' => [
                    'recon_helmet' => ['name' => 'Recon Helmet', 'defense' => 5, 'cost_credits' => 10000, 'description' => 'Basic head protection.'],
                    'carbon_fiber_visor' => ['name' => 'Carbon Fiber Visor', 'defense' => 10, 'cost_credits' => 20000, 'description' => 'Lightweight and strong.', 'requires' => 'recon_helmet', 'armory_level_req' => 2],
                    'reinforced_helmet' => ['name' => 'Reinforced Helmet', 'defense' => 20, 'cost_credits' => 30000, 'description' => 'Excellent impact resistance.', 'requires' => 'carbon_fiber_visor', 'armory_level_req' => 12],
                    'neural_guard_mask' => ['name' => 'Neural Guard Mask', 'defense' => 40, 'cost_credits' => 40000, 'description' => 'Protects against psychic/EMP effects.', 'requires' => 'reinforced_helmet', 'armory_level_req' => 22],
                    'aegis_helm' => ['name' => 'Aegis Helm', 'defense' => 80, 'cost_credits' => 50000, 'description' => 'High-tier head defense.', 'requires' => 'neural_guard_mask', 'armory_level_req' => 32],
                ]
            ]
        ]
    ],
    'spy' => [
        'title' => 'Spy Infiltration Loadout',
        'unit' => 'spies',
        'categories' => [
            'silenced_projectors' => [
                'title' => 'Stealth Main Weapons (Silenced Projectors)',
                'slots' => 1,
                'items' => [
                    'suppressed_pistol' => ['name' => 'Suppressed Pistol', 'offense' => 30, 'cost_credits' => 60000, 'description' => 'Standard issue spy sidearm.'],
                    'needle_gun' => ['name' => 'Needle Gun', 'offense' => 60, 'cost_credits' => 120000, 'description' => 'Fires silent, poisoned darts.', 'requires' => 'suppressed_pistol', 'armory_level_req' => 1],
                    'shock_rifle' => ['name' => 'Shock Rifle', 'offense' => 90, 'cost_credits' => 180000, 'description' => 'Can disable enemy electronics.', 'requires' => 'needle_gun', 'armory_level_req' => 21],
                    'ghost_rifle' => ['name' => 'Ghost Rifle', 'offense' => 120, 'cost_credits' => 240000, 'description' => 'Fires rounds that phase through cover.', 'requires' => 'shock_rifle', 'armory_level_req' => 31],
                    'spectre_rifle' => ['name' => 'Spectre Rifle', 'offense' => 160, 'cost_credits' => 320000, 'description' => 'The ultimate stealth weapon.', 'requires' => 'ghost_rifle', 'armory_level_req' => 41],
                ]
            ],
            'concealed_blades' => [
                'title' => 'Melee Weapons (Concealed Blades)',
                'slots' => 1,
                'items' => [
                    'hidden_blade' => ['name' => 'Hidden Blade', 'offense' => 15, 'cost_credits' => 30000, 'description' => 'A small, concealed blade.'],
                    'poisoned_dagger' => ['name' => 'Poisoned Dagger', 'offense' => 30, 'cost_credits' => 60000, 'description' => 'Deals damage over time.', 'requires' => 'hidden_blade', 'armory_level_req' => 1],
                    'vibroblade' => ['name' => 'Vibroblade', 'offense' => 45, 'cost_credits' => 90000, 'description' => 'Can cut through most armor.', 'requires' => 'poisoned_dagger', 'armory_level_req' => 23],
                    'shadow_blade' => ['name' => 'Shadow Blade', 'offense' => 60, 'cost_credits' => 120000, 'description' => 'A blade made of pure darkness.', 'requires' => 'vibroblade', 'armory_level_req' => 33],
                    'void_blade' => ['name' => 'Void Blade', 'offense' => 75, 'cost_credits' => 150000, 'description' => 'A blade that can cut through reality itself.', 'requires' => 'shadow_blade', 'armory_level_req' => 43],
                ]
            ],
            'intel_suite' => [
                'title' => 'Spy Headgear (Intel Suite)',
                'slots' => 1,
                'items' => [
                    'recon_visor' => ['name' => 'Recon Visor', 'offense' => 5, 'cost_credits' => 10000, 'description' => 'Provides basic intel on enemy positions.'],
                    'threat_detector' => ['name' => 'Threat Detector', 'offense' => 10, 'cost_credits' => 20000, 'description' => 'Highlights nearby threats.', 'requires' => 'recon_visor', 'armory_level_req' => 1],
                    'neural_interface' => ['name' => 'Neural Interface', 'offense' => 15, 'cost_credits' => 30000, 'description' => 'Allows the user to hack enemy systems.', 'requires' => 'threat_detector', 'armory_level_req' => 24],
                    'mind_scanner' => ['name' => 'Mind Scanner', 'offense' => 20, 'cost_credits' => 40000, 'description' => 'Can read the thoughts of nearby enemies.', 'requires' => 'neural_interface', 'armory_level_req' => 34],
                    'oracle_interface' => ['name' => 'Oracle Interface', 'offense' => 25, 'cost_credits' => 50000, 'description' => 'Can predict enemy movements.', 'requires' => 'mind_scanner', 'armory_level_req' => 44],
                ]
            ]
        ]
    ],
    'sentry' => [
        'title' => 'Sentry Defensive Loadout',
        'unit' => 'sentries',
        'categories' => [
            'shields' => [
                'title' => 'Defensive Main Equipment (Shields)',
                'slots' => 1,
                'items' => [
                    'ballistic_shield' => ['name' => 'Ballistic Shield', 'defense' => 50, 'cost_credits' => 100000, 'description' => 'Standard issue shield.'],
                    'tower_shield' => ['name' => 'Tower Shield', 'defense' => 100, 'cost_credits' => 200000, 'description' => 'Heavy, but provides excellent cover.', 'requires' => 'ballistic_shield', 'armory_level_req' => 1],
                    'riot_shield' => ['name' => 'Riot Shield', 'defense' => 150, 'cost_credits' => 300000, 'description' => 'Wider, better for holding a line.', 'requires' => 'tower_shield', 'armory_level_req' => 6],
                    'garrison_shield' => ['name' => 'Garrison Shield', 'defense' => 200, 'cost_credits' => 400000, 'description' => 'Can be deployed as temporary cover.', 'requires' => 'riot_shield', 'armory_level_req' => 36],
                    'bulwark_shield' => ['name' => 'Bulwark Shield', 'defense' => 250, 'cost_credits' => 500000, 'description' => 'Nearly impenetrable frontal defense.', 'requires' => 'garrison_shield', 'armory_level_req' => 46],
                ]
            ],
            'secondary_defensive_systems' => [
                'title' => 'Secondary Defensive Systems',
                'slots' => 1,
                'items' => [
                    'point_defense_system' => ['name' => 'Point Defense System', 'defense' => 20, 'cost_credits' => 40000, 'description' => 'Intercepts incoming projectiles.'],
                    'aegis_aura' => ['name' => 'Aegis Aura', 'defense' => 40, 'cost_credits' => 80000, 'description' => 'Provides a small damage shield to nearby allies.', 'requires' => 'point_defense_system', 'armory_level_req' => 1],
                    'guardian_protocol' => ['name' => 'Guardian Protocol', 'defense' => 60, 'cost_credits' => 120000, 'description' => 'Automatically diverts power to shields when hit.', 'requires' => 'aegis_aura', 'armory_level_req' => 7],
                    'bastion_mode' => ['name' => 'Bastion Mode', 'defense' => 80, 'cost_credits' => 160000, 'description' => 'Greatly increases defense when stationary.', 'requires' => 'guardian_protocol', 'armory_level_req' => 37],
                    'fortress_protocol' => ['name' => 'Fortress Protocol', 'defense' => 100, 'cost_credits' => 200000, 'description' => 'Links with other sentries to create a powerful shield wall.', 'requires' => 'bastion_mode', 'armory_level_req' => 47],
                ]
            ],
            'helmets' => [
                'title' => 'Defensive Headgear (Helmets)',
                'slots' => 1,
                'items' => [
                    'sentry_helmet' => ['name' => 'Sentry Helmet', 'defense' => 10, 'cost_credits' => 20000, 'description' => 'Standard issue helmet.'],
                    'reinforced_visor' => ['name' => 'Reinforced Visor', 'defense' => 20, 'cost_credits' => 40000, 'description' => 'Provides extra protection against headshots.', 'requires' => 'sentry_helmet', 'armory_level_req' => 1],
                    'commanders_helm' => ['name' => 'Commander\'s Helm', 'defense' => 30, 'cost_credits' => 60000, 'description' => 'Increases the effectiveness of nearby units.', 'requires' => 'reinforced_visor', 'armory_level_req' => 9],
                    'juggernaut_helm' => ['name' => 'Juggernaut Helm', 'defense' => 40, 'cost_credits' => 80000, 'description' => 'Heavy, but provides unmatched protection.', 'requires' => 'commanders_helm', 'armory_level_req' => 39],
                    'praetorian_helm' => ['name' => 'Praetorian Helm', 'defense' => 50, 'cost_credits' => 100000, 'description' => 'The ultimate in defensive headgear.', 'requires' => 'juggernaut_helm', 'armory_level_req' => 49],
                ]
            ]
        ]
    ]
];
