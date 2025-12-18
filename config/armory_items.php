<?php

/**
 * Armory & Loadout Configuration
 *
 * This file stores all data for armory items, including costs, stats,
 * and upgrade (manufacturing) requirements.
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
                    'pulse_rifle' => ['name' => 'Pulse Rifle', 'attack' => 40, 'cost' => 80000, 'notes' => 'Basic, reliable.'],
                    'railgun' => ['name' => 'Railgun', 'attack' => 80, 'cost' => 160000, 'notes' => 'High penetration, slower fire.', 'requires' => 'pulse_rifle', 'armory_level_req' => 10],
                    'plasma_minigun' => ['name' => 'Plasma Minigun', 'attack' => 160, 'cost' => 240000, 'notes' => 'Rapid fire, slightly inaccurate.', 'requires' => 'railgun', 'armory_level_req' => 20],
                    'arc_cannon' => ['name' => 'Arc Cannon', 'attack' => 320, 'cost' => 320000, 'notes' => 'Chains to nearby enemies.', 'requires' => 'plasma_minigun', 'armory_level_req' => 30],
                    'antimatter_launcher' => ['name' => 'Antimatter Launcher', 'attack' => 640, 'cost' => 400000, 'notes' => 'Extremely strong, high cost.', 'requires' => 'arc_cannon', 'armory_level_req' => 40],
                ]
            ],
            'sidearm' => [
                'title' => 'Sidearms',
                'slots' => 1,
                'items' => [
                    'laser_pistol' => ['name' => 'Laser Pistol', 'attack' => 25, 'cost' => 50000, 'notes' => 'Basic energy sidearm.'],
                    'stun_blaster' => ['name' => 'Stun Blaster', 'attack' => 50, 'cost' => 100000, 'notes' => 'Weak but disables shields briefly.', 'requires' => 'laser_pistol', 'armory_level_req' => 9],
                    'needler_pistol' => ['name' => 'Needler Pistol', 'attack' => 100, 'cost' => 150000, 'notes' => 'Seeking rounds, bonus vs. light armor.', 'requires' => 'stun_blaster', 'armory_level_req' => 19],
                    'compact_rail_smg' => ['name' => 'Compact Rail SMG', 'attack' => 200, 'cost' => 200000, 'notes' => 'Burst damage, close range.', 'requires' => 'needler_pistol', 'armory_level_req' => 29],
                    'photon_revolver' => ['name' => 'Photon Revolver', 'attack' => 400, 'cost' => 250000, 'notes' => 'High crit chance, slower reload.', 'requires' => 'compact_rail_smg', 'armory_level_req' => 39],
                ]
            ],
            'melee' => [
                'title' => 'Melee Weapons',
                'slots' => 1,
                'items' => [
                    'combat_dagger' => ['name' => 'Combat Dagger', 'attack' => 10, 'cost' => 20000, 'notes' => 'Quick, cheap.'],
                    'shock_baton' => ['name' => 'Shock Baton', 'attack' => 20, 'cost' => 40000, 'notes' => 'Stuns briefly, low raw damage.', 'requires' => 'combat_dagger', 'armory_level_req' => 8],
                    'energy_blade' => ['name' => 'Energy Blade', 'attack' => 40, 'cost' => 60000, 'notes' => 'Ignores armor.', 'requires' => 'shock_baton', 'armory_level_req' => 18],
                    'vibro_axe' => ['name' => 'Vibro Axe', 'attack' => 80, 'cost' => 80000, 'notes' => 'Heavy, great vs. fortifications.', 'requires' => 'energy_blade', 'armory_level_req' => 28],
                    'plasma_sword' => ['name' => 'Plasma Sword', 'attack' => 160, 'cost' => 100000, 'notes' => 'High damage, rare.', 'requires' => 'vibro_axe', 'armory_level_req' => 38],
                ]
            ],
            'headgear' => [
                'title' => 'Head Gear',
                'slots' => 1,
                'items' => [
                    'tactical_goggles' => ['name' => 'Tactical Goggles', 'attack' => 5, 'cost' => 10000, 'notes' => 'Accuracy boost.'],
                    'scout_visor' => ['name' => 'Scout Visor', 'attack' => 10, 'cost' => 20000, 'notes' => 'Detects stealth.', 'requires' => 'tactical_goggles', 'armory_level_req' => 7],
                    'heavy_helmet' => ['name' => 'Heavy Helmet', 'attack' => 20, 'cost' => 30000, 'notes' => 'Defense bonus, slight weight penalty.', 'requires' => 'scout_visor', 'armory_level_req' => 17],
                    'neural_uplink' => ['name' => 'Neural Uplink', 'attack' => 40, 'cost' => 40000, 'notes' => 'Faster reactions, boosts all attacks slightly.', 'requires' => 'heavy_helmet', 'armory_level_req' => 27],
                    'cloak_hood' => ['name' => 'Cloak Hood', 'attack' => 80, 'cost' => 50000, 'notes' => 'Stealth advantage, minimal armor.', 'requires' => 'neural_uplink', 'armory_level_req' => 37],
                ]
            ],
            'explosives' => [
                'title' => 'Explosives',
                'slots' => 1,
                'items' => [
                    'frag_grenade' => ['name' => 'Frag Grenade', 'attack' => 30, 'cost' => 60000, 'notes' => 'Basic explosive.'],
                    'plasma_grenade' => ['name' => 'Plasma Grenade', 'attack' => 60, 'cost' => 1200000, 'notes' => 'Sticks to targets.', 'requires' => 'frag_grenade', 'armory_level_req' => 6],
                    'emp_charge' => ['name' => 'EMP Charge', 'attack' => 120, 'cost' => 180000, 'notes' => 'Weakens shields/tech.', 'requires' => 'plasma_grenade', 'armory_level_req' => 16],
                    'nano_cluster_bomb' => ['name' => 'Nano Cluster Bomb', 'attack' => 240, 'cost' => 240000, 'notes' => 'Drone swarms shred troops.', 'requires' => 'emp_charge', 'armory_level_req' => 26],
                    'void_charge' => ['name' => 'Void Charge', 'attack' => 580, 'cost' => 300000, 'notes' => 'Creates a gravity implosion, devastating AoE.', 'requires' => 'nano_cluster_bomb', 'armory_level_req' => 36],
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
                    'light_combat_suit' => ['name' => 'Light Combat Suit', 'defense' => 40, 'cost' => 80000, 'notes' => 'Basic protection, minimal weight.'],
                    'titanium_plated_armor' => ['name' => 'Titanium Plated Armor', 'defense' => 80, 'cost' => 160000, 'notes' => 'Strong vs. kinetic weapons.', 'requires' => 'light_combat_suit', 'armory_level_req' => 5],
                    'reactive_nano_suit' => ['name' => 'Reactive Nano Suit', 'defense' => 160, 'cost' => 240000, 'notes' => 'Reduces energy damage, self-repairs slowly.', 'requires' => 'titanium_plated_armor', 'armory_level_req' => 15],
                    'bulwark_exo_frame' => ['name' => 'Bulwark Exo-Frame', 'defense' => 240, 'cost' => 320000, 'notes' => 'Heavy, extreme damage reduction.', 'requires' => 'reactive_nano_suit', 'armory_level_req' => 25],
                    'aegis_shield_suit' => ['name' => 'Aegis Shield Suit', 'defense' => 320, 'cost' => 400000, 'notes' => 'Generates energy shield, top-tier defense.', 'requires' => 'bulwark_exo_frame', 'armory_level_req' => 35],
                ]
            ],
            'secondary_defense' => [
                'title' => 'Defensive Side Devices (Secondary Defenses)',
                'slots' => 1,
                'items' => [
                    'kinetic_dampener' => ['name' => 'Kinetic Dampener', 'defense' => 15, 'cost' => 30000, 'notes' => 'Reduces ballistic damage.'],
                    'energy_diffuser' => ['name' => 'Energy Diffuser', 'defense' => 30, 'cost' => 60000, 'notes' => 'Lowers laser/plasma damage.', 'requires' => 'kinetic_dampener', 'armory_level_req' => 4],
                    'deflector_module' => ['name' => 'Deflector Module', 'defense' => 60, 'cost' => 90000, 'notes' => 'Partial shield that recharges slowly.', 'requires' => 'energy_diffuser', 'armory_level_req' => 14],
                    'auto_turret_drone' => ['name' => 'Auto-Turret Drone', 'defense' => 120, 'cost' => 120000, 'notes' => 'Assists defense, counters attackers.', 'requires' => 'deflector_module', 'armory_level_req' => 24],
                    'nano_healing_pod' => ['name' => 'Nano-Healing Pod', 'defense' => 240, 'cost' => 150000, 'notes' => 'Heals user periodically during battle.', 'requires' => 'auto_turret_drone', 'armory_level_req' => 34],
                ]
            ],
            'melee_counter' => [
                'title' => 'Melee Countermeasures',
                'slots' => 1,
                'items' => [
                    'combat_knife_parry_kit' => ['name' => 'Combat Knife Parry Kit', 'defense' => 10, 'cost' => 20000, 'notes' => 'Minimal, last-ditch block.'],
                    'shock_shield' => ['name' => 'Shock Shield', 'defense' => 20, 'cost' => 40000, 'notes' => 'Electrocutes melee attackers.', 'requires' => 'combat_knife_parry_kit', 'armory_level_req' => 3],
                    'vibro_blade_guard' => ['name' => 'Vibro Blade Guard', 'defense' => 40, 'cost' => 60000, 'notes' => 'Defensive melee stance, reduces melee damage.', 'requires' => 'shock_shield', 'armory_level_req' => 13],
                    'energy_buckler' => ['name' => 'Energy Buckler', 'defense' => 80, 'cost' => 80000, 'notes' => 'Small but strong energy shield.', 'requires' => 'vibro_blade_guard', 'armory_level_req' => 23],
                    'photon_barrier_blade' => ['name' => 'Photon Barrier Blade', 'defense' => 160, 'cost' => 100000, 'notes' => 'Creates a light shield, blocks most melee hits.', 'requires' => 'energy_buckler', 'armory_level_req' => 33],
                ]
            ],
            'defensive_headgear' => [
                'title' => 'Head Gear (Defensive Helmets)',
                'slots' => 1,
                'items' => [
                    'recon_helmet' => ['name' => 'Recon Helmet', 'defense' => 5, 'cost' => 10000, 'notes' => 'Basic head protection.'],
                    'carbon_fiber_visor' => ['name' => 'Carbon Fiber Visor', 'defense' => 10, 'cost' => 20000, 'notes' => 'Lightweight and strong.', 'requires' => 'recon_helmet', 'armory_level_req' => 2],
                    'reinforced_helmet' => ['name' => 'Reinforced Helmet', 'defense' => 20, 'cost' => 30000, 'notes' => 'Excellent impact resistance.', 'requires' => 'carbon_fiber_visor', 'armory_level_req' => 12],
                    'neural_guard_mask' => ['name' => 'Neural Guard Mask', 'defense' => 40, 'cost' => 40000, 'notes' => 'Protects against psychic/EMP effects.', 'requires' => 'reinforced_helmet', 'armory_level_req' => 22],
                    'aegis_helm' => ['name' => 'Aegis Helm', 'defense' => 80, 'cost' => 50000, 'notes' => 'High-tier head defense.', 'requires' => 'neural_guard_mask', 'armory_level_req' => 32],
                ]
            ],
            'defensive_deployable' => [
                'title' => 'Defensive Deployables',
                'slots' => 1,
                'items' => [
                    'basic_shield_generator' => ['name' => 'Basic Shield Generator', 'defense' => 30, 'cost' => 60000, 'notes' => 'Small personal barrier.'],
                    'plasma_wall_projector' => ['name' => 'Plasma Wall Projector', 'defense' => 60, 'cost' => 120000, 'notes' => 'Deployable energy wall.', 'requires' => 'basic_shield_generator', 'armory_level_req' => 1],
                    'emp_scrambler' => ['name' => 'EMP Scrambler', 'defense' => 120, 'cost' => 180000, 'notes' => 'Nullifies enemy EMP attacks.', 'requires' => 'plasma_wall_projector', 'armory_level_req' => 21],
                    'nano_repair_beacon' => ['name' => 'Nano Repair Beacon', 'defense' => 240, 'cost' => 240000, 'notes' => 'Repairs nearby allies and structures.', 'requires' => 'emp_scrambler', 'armory_level_req' => 31],
                    'fortress_dome_generator' => ['name' => 'Fortress Dome Generator', 'defense' => 480, 'cost' => 300000, 'notes' => 'Creates a temporary invulnerable dome.', 'requires' => 'nano_repair_beacon', 'armory_level_req' => 41],
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
                    'suppressed_pistol' => ['name' => 'Suppressed Pistol', 'attack' => 30, 'cost' => 60000, 'notes' => 'Standard issue spy sidearm.'],
                    'needle_gun' => ['name' => 'Needle Gun', 'attack' => 60, 'cost' => 120000, 'notes' => 'Fires silent, poisoned darts.', 'requires' => 'suppressed_pistol', 'armory_level_req' => 1],
                    'shock_rifle' => ['name' => 'Shock Rifle', 'attack' => 90, 'cost' => 180000, 'notes' => 'Can disable enemy electronics.', 'requires' => 'needle_gun', 'armory_level_req' => 21],
                    'ghost_rifle' => ['name' => 'Ghost Rifle', 'attack' => 120, 'cost' => 240000, 'notes' => 'Fires rounds that phase through cover.', 'requires' => 'shock_rifle', 'armory_level_req' => 31],
                    'spectre_rifle' => ['name' => 'Spectre Rifle', 'attack' => 160, 'cost' => 320000, 'notes' => 'The ultimate stealth weapon.', 'requires' => 'ghost_rifle', 'armory_level_req' => 41],
                ]
            ],
            'cloaking_disruption' => [
                'title' => 'Cloaking & Disruption Devices',
                'slots' => 1,
                'items' => [
                    'stealth_field_generator' => ['name' => 'Stealth Field Generator', 'attack' => 10, 'cost' => 20000, 'notes' => 'Makes the user harder to detect.'],
                    'chameleon_suit' => ['name' => 'Chameleon Suit', 'attack' => 20, 'cost' => 40000, 'notes' => 'Changes color to match the environment.', 'requires' => 'stealth_field_generator', 'armory_level_req' => 1],
                    'holographic_projector' => ['name' => 'Holographic Projector', 'attack' => 30, 'cost' => 60000, 'notes' => 'Creates a duplicate of the user to confuse enemies.', 'requires' => 'chameleon_suit', 'armory_level_req' => 22],
                    'phase_shifter' => ['name' => 'Phase Shifter', 'attack' => 40, 'cost' => 80000, 'notes' => 'Allows the user to temporarily phase through objects.', 'requires' => 'holographic_projector', 'armory_level_req' => 32],
                    'shadow_cloak' => ['name' => 'Shadow Cloak', 'attack' => 50, 'cost' => 100000, 'notes' => 'Renders the user nearly invisible.', 'requires' => 'phase_shifter', 'armory_level_req' => 42],
                ]
            ],
            'concealed_blades' => [
                'title' => 'Melee Weapons (Concealed Blades)',
                'slots' => 1,
                'items' => [
                    'hidden_blade' => ['name' => 'Hidden Blade', 'attack' => 15, 'cost' => 30000, 'notes' => 'A small, concealed blade.'],
                    'poisoned_dagger' => ['name' => 'Poisoned Dagger', 'attack' => 30, 'cost' => 60000, 'notes' => 'Deals damage over time.', 'requires' => 'hidden_blade', 'armory_level_req' => 1],
                    'vibroblade' => ['name' => 'Vibroblade', 'attack' => 45, 'cost' => 90000, 'notes' => 'Can cut through most armor.', 'requires' => 'poisoned_dagger', 'armory_level_req' => 23],
                    'shadow_blade' => ['name' => 'Shadow Blade', 'attack' => 60, 'cost' => 120000, 'notes' => 'A blade made of pure darkness.', 'requires' => 'vibroblade', 'armory_level_req' => 33],
                    'void_blade' => ['name' => 'Void Blade', 'attack' => 75, 'cost' => 150000, 'notes' => 'A blade that can cut through reality itself.', 'requires' => 'shadow_blade', 'armory_level_req' => 43],
                ]
            ],
            'intel_suite' => [
                'title' => 'Spy Headgear (Intel Suite)',
                'slots' => 1,
                'items' => [
                    'recon_visor' => ['name' => 'Recon Visor', 'attack' => 5, 'cost' => 10000, 'notes' => 'Provides basic intel on enemy positions.'],
                    'threat_detector' => ['name' => 'Threat Detector', 'attack' => 10, 'cost' => 20000, 'notes' => 'Highlights nearby threats.', 'requires' => 'recon_visor', 'armory_level_req' => 1],
                    'neural_interface' => ['name' => 'Neural Interface', 'attack' => 15, 'cost' => 30000, 'notes' => 'Allows the user to hack enemy systems.', 'requires' => 'threat_detector', 'armory_level_req' => 24],
                    'mind_scanner' => ['name' => 'Mind Scanner', 'attack' => 20, 'cost' => 40000, 'notes' => 'Can read the thoughts of nearby enemies.', 'requires' => 'neural_interface', 'armory_level_req' => 34],
                    'oracle_interface' => ['name' => 'Oracle Interface', 'attack' => 25, 'cost' => 50000, 'notes' => 'Can predict enemy movements.', 'requires' => 'mind_scanner', 'armory_level_req' => 44],
                ]
            ],
            'infiltration_gadgets' => [
                'title' => 'Infiltration Gadgets',
                'slots' => 1,
                'items' => [
                    'grappling_hook' => ['name' => 'Grappling Hook', 'attack' => 10, 'cost' => 20000, 'notes' => 'Allows the user to reach high places.'],
                    'smoke_bomb' => ['name' => 'Smoke Bomb', 'attack' => 20, 'cost' => 40000, 'notes' => 'Creates a cloud of smoke to obscure vision.', 'requires' => 'grappling_hook', 'armory_level_req' => 1],
                    'emp_grenade' => ['name' => 'EMP Grenade', 'attack' => 30, 'cost' => 60000, 'notes' => 'Disables enemy electronics.', 'requires' => 'smoke_bomb', 'armory_level_req' => 25],
                    'decoy' => ['name' => 'Decoy', 'attack' => 40, 'cost' => 80000, 'notes' => 'Creates a holographic decoy to distract enemies.', 'requires' => 'emp_grenade', 'armory_level_req' => 5],
                    'teleporter' => ['name' => 'Teleporter', 'attack' => 50, 'cost' => 100000, 'notes' => 'Allows the user to teleport short distances.', 'requires' => 'decoy', 'armory_level_req' => 45],
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
                    'ballistic_shield' => ['name' => 'Ballistic Shield', 'defense' => 50, 'cost' => 100000, 'notes' => 'Standard issue shield.'],
                    'tower_shield' => ['name' => 'Tower Shield', 'defense' => 100, 'cost' => 200000, 'notes' => 'Heavy, but provides excellent cover.', 'requires' => 'ballistic_shield', 'armory_level_req' => 1],
                    'riot_shield' => ['name' => 'Riot Shield', 'defense' => 150, 'cost' => 300000, 'notes' => 'Wider, better for holding a line.', 'requires' => 'tower_shield', 'armory_level_req' => 6],
                    'garrison_shield' => ['name' => 'Garrison Shield', 'defense' => 200, 'cost' => 400000, 'notes' => 'Can be deployed as temporary cover.', 'requires' => 'riot_shield', 'armory_level_req' => 36],
                    'bulwark_shield' => ['name' => 'Bulwark Shield', 'defense' => 250, 'cost' => 500000, 'notes' => 'Nearly impenetrable frontal defense.', 'requires' => 'garrison_shield', 'armory_level_req' => 46],
                ]
            ],
            'secondary_defensive_systems' => [
                'title' => 'Secondary Defensive Systems',
                'slots' => 1,
                'items' => [
                    'point_defense_system' => ['name' => 'Point Defense System', 'defense' => 20, 'cost' => 40000, 'notes' => 'Intercepts incoming projectiles.'],
                    'aegis_aura' => ['name' => 'Aegis Aura', 'defense' => 40, 'cost' => 80000, 'notes' => 'Provides a small damage shield to nearby allies.', 'requires' => 'point_defense_system', 'armory_level_req' => 1],
                    'guardian_protocol' => ['name' => 'Guardian Protocol', 'defense' => 60, 'cost' => 120000, 'notes' => 'Automatically diverts power to shields when hit.', 'requires' => 'aegis_aura', 'armory_level_req' => 7],
                    'bastion_mode' => ['name' => 'Bastion Mode', 'defense' => 80, 'cost' => 160000, 'notes' => 'Greatly increases defense when stationary.', 'requires' => 'guardian_protocol', 'armory_level_req' => 37],
                    'fortress_protocol' => ['name' => 'Fortress Protocol', 'defense' => 100, 'cost' => 200000, 'notes' => 'Links with other sentries to create a powerful shield wall.', 'requires' => 'bastion_mode', 'armory_level_req' => 47],
                ]
            ],
            'shield_bash' => [
                'title' => 'Melee Countermeasures (Shield Bash)',
                'slots' => 1,
                'items' => [
                    'concussive_blast' => ['name' => 'Concussive Blast', 'defense' => 15, 'cost' => 30000, 'notes' => 'Knocks back melee attackers.'],
                    'kinetic_ram' => ['name' => 'Kinetic Ram', 'defense' => 30, 'cost' => 60000, 'notes' => 'A powerful forward shield bash.', 'requires' => 'concussive_blast', 'armory_level_req' => 1],
                    'repulsor_field' => ['name' => 'Repulsor Field', 'defense' => 45, 'cost' => 90000, 'notes' => 'Pushes away all nearby enemies.', 'requires' => 'kinetic_ram', 'armory_level_req' => 28],
                    'overcharge' => ['name' => 'Overcharge', 'defense' => 60, 'cost' => 120000, 'notes' => 'Releases a powerful EMP blast on shield break.', 'requires' => 'repulsor_field', 'armory_level_req' => 38],
                    'sentinels_wrath' => ['name' => 'Sentinel\'s Wrath', 'defense' => 75, 'cost' => 150000, 'notes' => 'A devastating shield slam that stuns enemies.', 'requires' => 'overcharge', 'armory_level_req' => 48],
                ]
            ],
            'helmets' => [
                'title' => 'Defensive Headgear (Helmets)',
                'slots' => 1,
                'items' => [
                    'sentry_helmet' => ['name' => 'Sentry Helmet', 'defense' => 10, 'cost' => 20000, 'notes' => 'Standard issue helmet.'],
                    'reinforced_visor' => ['name' => 'Reinforced Visor', 'defense' => 20, 'cost' => 40000, 'notes' => 'Provides extra protection against headshots.', 'requires' => 'sentry_helmet', 'armory_level_req' => 1],
                    'commanders_helm' => ['name' => 'Commander\'s Helm', 'defense' => 30, 'cost' => 60000, 'notes' => 'Increases the effectiveness of nearby units.', 'requires' => 'reinforced_visor', 'armory_level_req' => 9],
                    'juggernaut_helm' => ['name' => 'Juggernaut Helm', 'defense' => 40, 'cost' => 80000, 'notes' => 'Heavy, but provides unmatched protection.', 'requires' => 'commanders_helm', 'armory_level_req' => 39],
                    'praetorian_helm' => ['name' => 'Praetorian Helm', 'defense' => 50, 'cost' => 100000, 'notes' => 'The ultimate in defensive headgear.', 'requires' => 'juggernaut_helm', 'armory_level_req' => 49],
                ]
            ],
            'fortifications' => [
                'title' => 'Defensive Deployables (Fortifications)',
                'slots' => 1,
                'items' => [
                    'deployable_cover' => ['name' => 'Deployable Cover', 'defense' => 35, 'cost' => 70000, 'notes' => 'Creates a small piece of cover.'],
                    'barricade' => ['name' => 'Barricade', 'defense' => 70, 'cost' => 140000, 'notes' => 'A larger, more durable piece of cover.', 'requires' => 'deployable_cover', 'armory_level_req' => 1],
                    'watchtower' => ['name' => 'Watchtower', 'defense' => 105, 'cost' => 210000, 'notes' => 'Provides a better vantage point and increased range.', 'requires' => 'barricade', 'armory_level_req' => 20],
                    'bunker' => ['name' => 'Bunker', 'defense' => 140, 'cost' => 280000, 'notes' => 'A heavily fortified structure.', 'requires' => 'watchtower', 'armory_level_req' => 30],
                    'fortress' => ['name' => 'Fortress', 'defense' => 175, 'cost' => 350000, 'notes' => 'A massive, nearly indestructible fortification.', 'requires' => 'bunker', 'armory_level_req' => 40],
                ]
            ]
        ]
    ],
    'worker' => [
        'title' => 'Worker Utility Loadout',
        'unit' => 'workers',
        'categories' => [
            'mining_lasers_drills' => [
                'title' => 'Utility Main Equipment (Mining Lasers & Drills)',
                'slots' => 1,
                'items' => [
                    'mining_laser' => ['name' => 'Mining Laser', 'credit_bonus' => 80, 'cost' => 40000, 'notes' => 'Can be used as a makeshift weapon.'],
                    'heavy_drill' => ['name' => 'Heavy Drill', 'credit_bonus' => 160, 'cost' => 80000, 'notes' => 'Can break through tough materials.', 'requires' => 'mining_laser', 'armory_level_req' => 1],
                    'plasma_cutter' => ['name' => 'Plasma Cutter', 'credit_bonus' => 320, 'cost' => 120000, 'notes' => 'Can cut through almost anything.', 'requires' => 'heavy_drill', 'armory_level_req' => 5],
                    'seismic_charge' => ['name' => 'Seismic Charge', 'credit_bonus' => 640, 'cost' => 160000, 'notes' => 'Can create powerful explosions.', 'requires' => 'plasma_cutter', 'armory_level_req' => 10],
                    'terraforming_beam' => ['name' => 'Terraforming Beam', 'credit_bonus' => 800, 'cost' => 220000, 'notes' => 'Can reshape the very earth.', 'requires' => 'seismic_charge', 'armory_level_req' => 15],
                ]
            ],
            'resource_enhancement' => [
                'title' => 'Resource Enhancement Tools',
                'slots' => 1,
                'items' => [
                    'resource_scanner' => ['name' => 'Resource Scanner', 'credit_bonus' => 15, 'cost' => 7500, 'notes' => 'Finds hidden resource deposits.'],
                    'geological_analyzer' => ['name' => 'Geological Analyzer', 'credit_bonus' => 30, 'cost' => 15000, 'notes' => 'Identifies the best places to mine.', 'requires' => 'resource_scanner', 'armory_level_req' => 2],
                    'harvester_drone' => ['name' => 'Harvester Drone', 'credit_bonus' => 60, 'cost' => 22500, 'notes' => 'Automatically collects nearby resources.', 'requires' => 'geological_analyzer', 'armory_level_req' => 6],
                    'matter_converter' => ['name' => 'Matter Converter', 'credit_bonus' => 120, 'cost' => 30000, 'notes' => 'Converts raw materials into credits.', 'requires' => 'harvester_drone', 'armory_level_req' => 11],
                    'genesis_device' => ['name' => 'Genesis Device', 'credit_bonus' => 240, 'cost' => 37500, 'notes' => 'Creates new resources from nothing.', 'requires' => 'matter_converter', 'armory_level_req' => 16],
                ]
            ],
            'exo_rig_plating' => [
                'title' => 'Defensive Gear (Exo-Rig Plating)',
                'slots' => 1,
                'items' => [
                    'worker_harness' => ['name' => 'Worker Harness', 'credit_bonus' => 25, 'cost' => 17500, 'notes' => 'Provides basic protection.'],
                    'reinforced_plating' => ['name' => 'Reinforced Plating', 'credit_bonus' => 50, 'cost' => 35000, 'notes' => 'Protects against workplace accidents.', 'requires' => 'worker_harness', 'armory_level_req' => 3],
                    'hazard_suit' => ['name' => 'Hazard Suit', 'credit_bonus' => 100, 'cost' => 42500, 'notes' => 'Protects against environmental hazards.', 'requires' => 'reinforced_plating', 'armory_level_req' => 7],
                    'blast_shield' => ['name' => 'Blast Shield', 'credit_bonus' => 200, 'cost' => 60000, 'notes' => 'Protects against explosions.', 'requires' => 'hazard_suit', 'armory_level_req' => 12],
                    'power_armor' => ['name' => 'Power Armor', 'credit_bonus' => 400, 'cost' => 77500, 'notes' => 'The ultimate in worker protection.', 'requires' => 'blast_shield', 'armory_level_req' => 17],
                ]
            ],
            'scanners' => [
                'title' => 'Utility Headgear (Scanners)',
                'slots' => 1,
                'items' => [
                    'geiger_counter' => ['name' => 'Geiger Counter', 'credit_bonus' => 20, 'attack' => 0, 'cost' => 10000, 'notes' => 'Detects radiation.'],
                    'mineral_scanner' => ['name' => 'Mineral Scanner', 'credit_bonus' => 40, 'attack' => 0, 'cost' => 20000, 'notes' => 'Detects valuable minerals.', 'requires' => 'geiger_counter', 'armory_level_req' => 4],
                    'lifeform_scanner' => ['name' => 'Lifeform Scanner', 'credit_bonus' => 80, 'attack' => 0, 'cost' => 30000, 'notes' => 'Detects nearby lifeforms.', 'requires' => 'mineral_scanner', 'armory_level_req' => 8],
                    'energy_scanner' => ['name' => 'Energy Scanner', 'credit_bonus' => 160, 'attack' => 0, 'cost' => 40000, 'notes' => 'Detects energy signatures.', 'requires' => 'lifeform_scanner', 'armory_level_req' => 13],
                    'omni_scanner' => ['name' => 'Omni-Scanner', 'credit_bonus' => 320, 'attack' => 0, 'cost' => 50000, 'notes' => 'Detects everything.', 'requires' => 'energy_scanner', 'armory_level_req' => 18],
                ]
            ],
            'drones' => [
                'title' => 'Construction & Repair Drones',
                'slots' => 1,
                'items' => [
                    'repair_drone' => ['name' => 'Repair Drone', 'credit_bonus' => 10, 'attack' => 0, 'cost' => 5000, 'notes' => 'Can repair damaged structures.'],
                    'construction_drone' => ['name' => 'Construction Drone', 'credit_bonus' => 20, 'attack' => 0, 'cost' => 1000, 'notes' => 'Can build new structures.', 'requires' => 'repair_drone', 'armory_level_req' => 5],
                    'salvage_drone' => ['name' => 'Salvage Drone', 'credit_bonus' => 40, 'attack' => 0, 'cost' => 15000, 'notes' => 'Can salvage materials from wreckage.', 'requires' => 'construction_drone', 'armory_level_req' => 9],
                    'fabricator_drone' => ['name' => 'Fabricator Drone', 'credit_bonus' => 80, 'attack' => 0, 'cost' => 20000, 'notes' => 'Can create new items from raw materials.', 'requires' => 'salvage_drone', 'armory_level_req' => 14],
                    'replicator_drone' => ['name' => 'Replicator Drone', 'credit_bonus' => 160, 'attack' => 0, 'cost' => 25000, 'notes' => 'Can create anything.', 'requires' => 'fabricator_drone', 'armory_level_req' => 19],
                ]
            ]
        ]
    ]
];
