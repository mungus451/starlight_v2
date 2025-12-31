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
                    'pulse_rifle' => ['name' => 'Pulse Rifle', 'attack' => 40, 'cost' => 80000, 'notes' => 'Basic, reliable.'],
                    'railgun' => ['name' => 'Railgun', 'attack' => 80, 'cost' => 160000, 'notes' => 'High penetration, slower fire.', 'requires' => 'pulse_rifle', 'armory_level_req' => 10],
                    'plasma_minigun' => ['name' => 'Plasma Minigun', 'attack' => 160, 'cost' => 240000, 'notes' => 'Rapid fire, slightly inaccurate.', 'requires' => 'railgun', 'armory_level_req' => 20],
                    'arc_cannon' => ['name' => 'Arc Cannon', 'attack' => 320, 'cost' => 320000, 'notes' => 'Chains to nearby enemies.', 'requires' => 'plasma_minigun', 'armory_level_req' => 30],
                    'antimatter_launcher' => ['name' => 'Antimatter Launcher', 'attack' => 640, 'cost' => 400000, 'notes' => 'Extremely strong, high cost.', 'requires' => 'arc_cannon', 'armory_level_req' => 40],
                    // Tier 6-10 Expansion
                    'photon_lance' => ['name' => 'Photon Lance', 'attack' => 1280, 'cost' => 480000, 'cost_crystals' => 10, 'notes' => 'Pierces through multiple targets.', 'requires' => 'antimatter_launcher', 'armory_level_req' => 50],
                    'singularity_cannon' => ['name' => 'Singularity Cannon', 'attack' => 2560, 'cost' => 560000, 'cost_crystals' => 50, 'notes' => 'Creates mini black holes.', 'requires' => 'photon_lance', 'armory_level_req' => 60],
                    'void_disintegrator' => ['name' => 'Void Disintegrator', 'attack' => 5120, 'cost' => 640000, 'cost_crystals' => 250, 'cost_dark_matter' => 1, 'notes' => 'Erases targets from existence.', 'requires' => 'singularity_cannon', 'armory_level_req' => 70],
                    'temporal_blaster' => ['name' => 'Temporal Blaster', 'attack' => 10240, 'cost' => 720000, 'cost_crystals' => 1000, 'cost_dark_matter' => 10, 'notes' => 'Hits targets before you fire.', 'requires' => 'void_disintegrator', 'armory_level_req' => 80],
                    'reality_shatter_gun' => ['name' => 'Reality Shatter Gun', 'attack' => 20480, 'cost' => 1000000, 'cost_crystals' => 5000, 'cost_dark_matter' => 100, 'notes' => 'Breaks the laws of physics.', 'requires' => 'temporal_blaster', 'armory_level_req' => 90],
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
                    // Tier 6-10 Expansion
                    'plasma_sidearm' => ['name' => 'Plasma Sidearm', 'attack' => 640, 'cost' => 300000, 'cost_crystals' => 5, 'notes' => 'Hot enough to melt steel.', 'requires' => 'photon_revolver', 'armory_level_req' => 49],
                    'nano_stinger' => ['name' => 'Nano Stinger', 'attack' => 1280, 'cost' => 350000, 'cost_crystals' => 25, 'notes' => 'Injects deadly nanobots.', 'requires' => 'plasma_sidearm', 'armory_level_req' => 59],
                    'phase_pistol' => ['name' => 'Phase Pistol', 'attack' => 2560, 'cost' => 400000, 'cost_crystals' => 100, 'cost_dark_matter' => 1, 'notes' => 'Shoots through walls.', 'requires' => 'nano_stinger', 'armory_level_req' => 69],
                    'neutron_blaster' => ['name' => 'Neutron Blaster', 'attack' => 5120, 'cost' => 450000, 'cost_crystals' => 500, 'cost_dark_matter' => 5, 'notes' => 'Radiates enemies.', 'requires' => 'phase_pistol', 'armory_level_req' => 79],
                    'entropy_hand_cannon' => ['name' => 'Entropy Hand Cannon', 'attack' => 10240, 'cost' => 600000, 'cost_crystals' => 2500, 'cost_dark_matter' => 50, 'notes' => 'Decays matter on impact.', 'requires' => 'neutron_blaster', 'armory_level_req' => 89],
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
                    // Tier 6-10 Expansion
                    'monomolecular_blade' => ['name' => 'Monomolecular Blade', 'attack' => 320, 'cost' => 120000, 'cost_crystals' => 10, 'notes' => 'Sharp enough to cut atoms.', 'requires' => 'plasma_sword', 'armory_level_req' => 48],
                    'void_edge' => ['name' => 'Void Edge', 'attack' => 640, 'cost' => 140000, 'cost_crystals' => 50, 'notes' => 'Consume light around the blade.', 'requires' => 'monomolecular_blade', 'armory_level_req' => 58],
                    'singularity_dagger' => ['name' => 'Singularity Dagger', 'attack' => 1280, 'cost' => 160000, 'cost_crystals' => 250, 'cost_dark_matter' => 1, 'notes' => 'Heavy as a star.', 'requires' => 'void_edge', 'armory_level_req' => 68],
                    'temporal_sword' => ['name' => 'Temporal Sword', 'attack' => 2560, 'cost' => 180000, 'cost_crystals' => 1000, 'cost_dark_matter' => 10, 'notes' => 'Strikes in the past and future.', 'requires' => 'singularity_dagger', 'armory_level_req' => 78],
                    'entropy_blade' => ['name' => 'Entropy Blade', 'attack' => 5120, 'cost' => 200000, 'cost_crystals' => 5000, 'cost_dark_matter' => 100, 'notes' => 'Unmakes whatever it touches.', 'requires' => 'temporal_sword', 'armory_level_req' => 88],
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
                    // Tier 6-10 Expansion
                    'target_link_hud' => ['name' => 'Target Link HUD', 'attack' => 160, 'cost' => 60000, 'cost_crystals' => 5, 'notes' => 'Auto-aim assist.', 'requires' => 'cloak_hood', 'armory_level_req' => 47],
                    'predator_visor' => ['name' => 'Predator Visor', 'attack' => 320, 'cost' => 70000, 'cost_crystals' => 25, 'notes' => 'Thermal and heartbeat sensors.', 'requires' => 'target_link_hud', 'armory_level_req' => 57],
                    'omni_sight' => ['name' => 'Omni-Sight', 'attack' => 640, 'cost' => 80000, 'cost_crystals' => 100, 'cost_dark_matter' => 1, 'notes' => 'See through walls.', 'requires' => 'predator_visor', 'armory_level_req' => 67],
                    'temporal_eye' => ['name' => 'Temporal Eye', 'attack' => 1280, 'cost' => 90000, 'cost_crystals' => 500, 'cost_dark_matter' => 5, 'notes' => 'Predicts enemy movements.', 'requires' => 'omni_sight', 'armory_level_req' => 77],
                    'reality_scanner' => ['name' => 'Reality Scanner', 'attack' => 2560, 'cost' => 100000, 'cost_crystals' => 2500, 'cost_dark_matter' => 50, 'notes' => 'See the code of the universe.', 'requires' => 'temporal_eye', 'armory_level_req' => 87],
                ]
            ],
            'explosives' => [
                'title' => 'Explosives',
                'slots' => 1,
                'items' => [
                    'frag_grenade' => ['name' => 'Frag Grenade', 'attack' => 30, 'cost' => 60000, 'notes' => 'Basic explosive.'],
                    'plasma_grenade' => ['name' => 'Plasma Grenade', 'attack' => 60, 'cost' => 120000, 'notes' => 'Sticks to targets.', 'requires' => 'frag_grenade', 'armory_level_req' => 6],
                    'emp_charge' => ['name' => 'EMP Charge', 'attack' => 120, 'cost' => 180000, 'notes' => 'Weakens shields/tech.', 'requires' => 'plasma_grenade', 'armory_level_req' => 16],
                    'nano_cluster_bomb' => ['name' => 'Nano Cluster Bomb', 'attack' => 240, 'cost' => 240000, 'notes' => 'Drone swarms shred troops.', 'requires' => 'emp_charge', 'armory_level_req' => 26],
                    'void_charge' => ['name' => 'Void Charge', 'attack' => 480, 'cost' => 300000, 'notes' => 'Creates a gravity implosion, devastating AoE.', 'requires' => 'nano_cluster_bomb', 'armory_level_req' => 36],
                    // Tier 6-10 Expansion
                    'antimatter_grenade' => ['name' => 'Antimatter Grenade', 'attack' => 960, 'cost' => 360000, 'cost_crystals' => 20, 'notes' => 'Pure destruction.', 'requires' => 'void_charge', 'armory_level_req' => 46],
                    'black_hole_bomb' => ['name' => 'Black Hole Bomb', 'attack' => 1920, 'cost' => 420000, 'cost_crystals' => 100, 'notes' => 'Miniature event horizon.', 'requires' => 'antimatter_grenade', 'armory_level_req' => 56],
                    'dimensional_charge' => ['name' => 'Dimensional Charge', 'attack' => 3840, 'cost' => 480000, 'cost_crystals' => 500, 'cost_dark_matter' => 2, 'notes' => 'Rips space-time.', 'requires' => 'black_hole_bomb', 'armory_level_req' => 66],
                    'time_bomb' => ['name' => 'Time Bomb', 'attack' => 7680, 'cost' => 540000, 'cost_crystals' => 2000, 'cost_dark_matter' => 20, 'notes' => 'Detonates in the past.', 'requires' => 'dimensional_charge', 'armory_level_req' => 76],
                    'reality_bomb' => ['name' => 'Reality Bomb', 'attack' => 15360, 'cost' => 600000, 'cost_crystals' => 10000, 'cost_dark_matter' => 200, 'notes' => 'Ends everything.', 'requires' => 'time_bomb', 'armory_level_req' => 86],
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
                    // Tier 6-10 Expansion
                    'neutronium_plate' => ['name' => 'Neutronium Plate', 'defense' => 1280, 'cost' => 480000, 'cost_crystals' => 10, 'notes' => 'Ultra-dense protection.', 'requires' => 'aegis_shield_suit', 'armory_level_req' => 45],
                    'quantum_barrier_suit' => ['name' => 'Quantum Barrier Suit', 'defense' => 2560, 'cost' => 560000, 'cost_crystals' => 50, 'notes' => 'Phases in and out of reality to dodge.', 'requires' => 'neutronium_plate', 'armory_level_req' => 55],
                    'dark_matter_weave' => ['name' => 'Dark Matter Weave', 'defense' => 5120, 'cost' => 640000, 'cost_crystals' => 250, 'cost_dark_matter' => 1, 'notes' => 'Absorbs nearly all energy.', 'requires' => 'quantum_barrier_suit', 'armory_level_req' => 65],
                    'temporal_shield_armor' => ['name' => 'Temporal Shield Armor', 'defense' => 10240, 'cost' => 720000, 'cost_crystals' => 1000, 'cost_dark_matter' => 10, 'notes' => 'Reverses damage taken.', 'requires' => 'dark_matter_weave', 'armory_level_req' => 75],
                    'event_horizon_vest' => ['name' => 'Event Horizon Vest', 'defense' => 20480, 'cost' => 1000000, 'cost_crystals' => 5000, 'cost_dark_matter' => 100, 'notes' => 'Nothing escapes its protection.', 'requires' => 'temporal_shield_armor', 'armory_level_req' => 85],
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
                    // Tier 6-10 Expansion
                    'hard_light_shield' => ['name' => 'Hard Light Shield', 'defense' => 480, 'cost' => 180000, 'cost_crystals' => 5, 'notes' => 'Solid photon barrier.', 'requires' => 'nano_healing_pod', 'armory_level_req' => 44],
                    'void_dampener' => ['name' => 'Void Dampener', 'defense' => 960, 'cost' => 210000, 'cost_crystals' => 25, 'notes' => 'Reduces all incoming damage.', 'requires' => 'hard_light_shield', 'armory_level_req' => 54],
                    'singularity_sink' => ['name' => 'Singularity Sink', 'defense' => 1920, 'cost' => 240000, 'cost_crystals' => 100, 'cost_dark_matter' => 1, 'notes' => 'Absorbs projectile mass.', 'requires' => 'void_dampener', 'armory_level_req' => 64],
                    'temporal_buffer' => ['name' => 'Temporal Buffer', 'defense' => 3840, 'cost' => 270000, 'cost_crystals' => 500, 'cost_dark_matter' => 5, 'notes' => 'Delays damage intake.', 'requires' => 'singularity_sink', 'armory_level_req' => 74],
                    'entropy_negator' => ['name' => 'Entropy Negator', 'defense' => 7680, 'cost' => 300000, 'cost_crystals' => 2500, 'cost_dark_matter' => 50, 'notes' => 'Prevents matter decay.', 'requires' => 'temporal_buffer', 'armory_level_req' => 84],
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
                    // Tier 6-10 Expansion
                    'plasma_shield_spike' => ['name' => 'Plasma Shield Spike', 'defense' => 320, 'cost' => 120000, 'cost_crystals' => 5, 'notes' => 'Damages attacker on block.', 'requires' => 'photon_barrier_blade', 'armory_level_req' => 43],
                    'void_parry_module' => ['name' => 'Void Parry Module', 'defense' => 640, 'cost' => 140000, 'cost_crystals' => 25, 'notes' => 'Auto-parries using gravity.', 'requires' => 'plasma_shield_spike', 'armory_level_req' => 53],
                    'gravity_deflector' => ['name' => 'Gravity Deflector', 'defense' => 1280, 'cost' => 160000, 'cost_crystals' => 100, 'cost_dark_matter' => 1, 'notes' => 'Bends attacks around you.', 'requires' => 'void_parry_module', 'armory_level_req' => 63],
                    'time_riposte_system' => ['name' => 'Time Riposte System', 'defense' => 2560, 'cost' => 180000, 'cost_crystals' => 500, 'cost_dark_matter' => 5, 'notes' => 'Counters before the strike.', 'requires' => 'gravity_deflector', 'armory_level_req' => 73],
                    'reality_anchor' => ['name' => 'Reality Anchor', 'defense' => 5120, 'cost' => 200000, 'cost_crystals' => 2500, 'cost_dark_matter' => 50, 'notes' => 'Cannot be moved or broken.', 'requires' => 'time_riposte_system', 'armory_level_req' => 83],
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
                    // Tier 6-10 Expansion
                    'nano_weave_helm' => ['name' => 'Nano-Weave Helm', 'defense' => 160, 'cost' => 60000, 'cost_crystals' => 5, 'notes' => 'Self-repairing helmet.', 'requires' => 'aegis_helm', 'armory_level_req' => 42],
                    'void_gaze_helm' => ['name' => 'Void Gaze Helm', 'defense' => 320, 'cost' => 70000, 'cost_crystals' => 25, 'notes' => 'See into the void.', 'requires' => 'nano_weave_helm', 'armory_level_req' => 52],
                    'singularity_crown' => ['name' => 'Singularity Crown', 'defense' => 640, 'cost' => 80000, 'cost_crystals' => 100, 'cost_dark_matter' => 1, 'notes' => 'Absorbs headshots.', 'requires' => 'void_gaze_helm', 'armory_level_req' => 62],
                    'temporal_guard_helm' => ['name' => 'Temporal Guard Helm', 'defense' => 1280, 'cost' => 90000, 'cost_crystals' => 500, 'cost_dark_matter' => 5, 'notes' => 'Time slows on impact.', 'requires' => 'singularity_crown', 'armory_level_req' => 72],
                    'eternal_helm' => ['name' => 'Eternal Helm', 'defense' => 2560, 'cost' => 100000, 'cost_crystals' => 2500, 'cost_dark_matter' => 50, 'notes' => 'Indestructible.', 'requires' => 'temporal_guard_helm', 'armory_level_req' => 82],
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
                    // Tier 6-10 Expansion
                    'hard_light_wall' => ['name' => 'Hard Light Wall', 'defense' => 960, 'cost' => 360000, 'cost_crystals' => 20, 'notes' => 'Impassable barrier.', 'requires' => 'fortress_dome_generator', 'armory_level_req' => 51],
                    'void_bunker_projector' => ['name' => 'Void Bunker Projector', 'defense' => 1920, 'cost' => 420000, 'cost_crystals' => 100, 'notes' => 'Instant heavy cover.', 'requires' => 'hard_light_wall', 'armory_level_req' => 61],
                    'singularity_barrier' => ['name' => 'Singularity Barrier', 'defense' => 3840, 'cost' => 480000, 'cost_crystals' => 500, 'cost_dark_matter' => 2, 'notes' => 'Consumes incoming fire.', 'requires' => 'void_bunker_projector', 'armory_level_req' => 71],
                    'stasis_field' => ['name' => 'Stasis Field', 'defense' => 7680, 'cost' => 540000, 'cost_crystals' => 2000, 'cost_dark_matter' => 20, 'notes' => 'Freezes area in time.', 'requires' => 'singularity_barrier', 'armory_level_req' => 81],
                    'invincible_bastion' => ['name' => 'Invincible Bastion', 'defense' => 15360, 'cost' => 600000, 'cost_crystals' => 10000, 'cost_dark_matter' => 200, 'notes' => 'The ultimate defense.', 'requires' => 'stasis_field', 'armory_level_req' => 91],
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
                    // Tier 6-10 Expansion
                    'shadow_caster' => ['name' => 'Shadow Caster', 'attack' => 200, 'cost' => 380000, 'cost_crystals' => 15, 'notes' => 'Fires projectiles that absorb light.', 'requires' => 'spectre_rifle', 'armory_level_req' => 51],
                    'void_projector' => ['name' => 'Void Projector', 'attack' => 240, 'cost' => 440000, 'cost_crystals' => 75, 'notes' => 'Launches spheres of pure nothingness.', 'requires' => 'shadow_caster', 'armory_level_req' => 61],
                    'singularity_silencer' => ['name' => 'Singularity Silencer', 'attack' => 280, 'cost' => 500000, 'cost_crystals' => 300, 'cost_dark_matter' => 1, 'notes' => 'Implodes targets quietly.', 'requires' => 'void_projector', 'armory_level_req' => 71],
                    'temporal_phaser' => ['name' => 'Temporal Phaser', 'attack' => 320, 'cost' => 560000, 'cost_crystals' => 1500, 'cost_dark_matter' => 10, 'notes' => 'Disrupts target chronal stability.', 'requires' => 'singularity_silencer', 'armory_level_req' => 81],
                    'reality_blur_gun' => ['name' => 'Reality Blur Gun', 'attack' => 400, 'cost' => 700000, 'cost_crystals' => 7500, 'cost_dark_matter' => 150, 'notes' => 'Makes targets cease to exist.', 'requires' => 'temporal_phaser', 'armory_level_req' => 91],
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
                    // Tier 6-10 Expansion
                    'void_shroud' => ['name' => 'Void Shroud', 'attack' => 60, 'cost' => 120000, 'cost_crystals' => 10, 'notes' => 'Hides user in a pocket dimension.', 'requires' => 'shadow_cloak', 'armory_level_req' => 52],
                    'singularity_cloak' => ['name' => 'Singularity Cloak', 'attack' => 70, 'cost' => 140000, 'cost_crystals' => 50, 'notes' => 'Bends light and gravity around user.', 'requires' => 'void_shroud', 'armory_level_req' => 62],
                    'temporal_displacement' => ['name' => 'Temporal Displacement', 'attack' => 80, 'cost' => 160000, 'cost_crystals' => 250, 'cost_dark_matter' => 1, 'notes' => 'Moves user through time.', 'requires' => 'singularity_cloak', 'armory_level_req' => 72],
                    'reality_disguise' => ['name' => 'Reality Disguise', 'attack' => 90, 'cost' => 180000, 'cost_crystals' => 1000, 'cost_dark_matter' => 10, 'notes' => 'Can perfectly mimic anyone.', 'requires' => 'temporal_displacement', 'armory_level_req' => 82],
                    'entropy_cloak' => ['name' => 'Entropy Cloak', 'attack' => 100, 'cost' => 200000, 'cost_crystals' => 5000, 'cost_dark_matter' => 100, 'notes' => 'Erases user's presence from all records.', 'requires' => 'reality_disguise', 'armory_level_req' => 92],
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
                    // Tier 6-10 Expansion
                    'neural_blade' => ['name' => 'Neural Blade', 'attack' => 90, 'cost' => 180000, 'cost_crystals' => 15, 'notes' => 'Overloads enemy nervous systems.', 'requires' => 'void_blade', 'armory_level_req' => 53],
                    'void_fang' => ['name' => 'Void Fang', 'attack' => 105, 'cost' => 210000, 'cost_crystals' => 75, 'notes' => 'Drains life from target.', 'requires' => 'neural_blade', 'armory_level_req' => 63],
                    'singularity_edge' => ['name' => 'Singularity Edge', 'attack' => 120, 'cost' => 240000, 'cost_crystals' => 300, 'cost_dark_matter' => 1, 'notes' => 'Creates miniature gravitational fields.', 'requires' => 'void_fang', 'armory_level_req' => 73],
                    'temporal_shiv' => ['name' => 'Temporal Shiv', 'attack' => 135, 'cost' => 270000, 'cost_crystals' => 1500, 'cost_dark_matter' => 10, 'notes' => 'Wounds echo through time.', 'requires' => 'singularity_edge', 'armory_level_req' => 83],
                    'reality_rend' => ['name' => 'Reality Rend', 'attack' => 150, 'cost' => 300000, 'cost_crystals' => 7500, 'cost_dark_matter' => 150, 'notes' => 'Tears target's molecular bonds.', 'requires' => 'temporal_shiv', 'armory_level_req' => 93],
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
                    // Tier 6-10 Expansion
                    'void_observer' => ['name' => 'Void Observer', 'attack' => 30, 'cost' => 60000, 'cost_crystals' => 5, 'notes' => 'Monitors astral plane.', 'requires' => 'oracle_interface', 'armory_level_req' => 54],
                    'quantum_analyzer' => ['name' => 'Quantum Analyzer', 'attack' => 35, 'cost' => 70000, 'cost_crystals' => 25, 'notes' => 'Analyzes all possible futures.', 'requires' => 'void_observer', 'armory_level_req' => 64],
                    'temporal_slicer' => ['name' => 'Temporal Slicer', 'attack' => 40, 'cost' => 80000, 'cost_crystals' => 100, 'cost_dark_matter' => 1, 'notes' => 'Extracts data from the past.', 'requires' => 'quantum_analyzer', 'armory_level_req' => 74],
                    'reality_mapper' => ['name' => 'Reality Mapper', 'attack' => 45, 'cost' => 90000, 'cost_crystals' => 500, 'cost_dark_matter' => 5, 'notes' => 'Maps the fabric of reality.', 'requires' => 'temporal_slicer', 'armory_level_req' => 84],
                    'god_eye_interface' => ['name' => 'God Eye Interface', 'attack' => 50, 'cost' => 100000, 'cost_crystals' => 2500, 'cost_dark_matter' => 50, 'notes' => 'Omniscient view of the battlefield.', 'requires' => 'reality_mapper', 'armory_level_req' => 94],
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
                    // Tier 6-10 Expansion
                    'void_beacon' => ['name' => 'Void Beacon', 'attack' => 60, 'cost' => 120000, 'cost_crystals' => 10, 'notes' => 'Creates a temporary portal to the void.', 'requires' => 'teleporter', 'armory_level_req' => 55],
                    'quantum_phaser' => ['name' => 'Quantum Phaser', 'attack' => 70, 'cost' => 140000, 'cost_crystals' => 50, 'notes' => 'Phases through solid objects.', 'requires' => 'void_beacon', 'armory_level_req' => 65],
                    'temporal_manipulator' => ['name' => 'Temporal Manipulator', 'attack' => 80, 'cost' => 160000, 'cost_crystals' => 250, 'cost_dark_matter' => 1, 'notes' => 'Slows time in a small area.', 'requires' => 'quantum_phaser', 'armory_level_req' => 75],
                    'reality_bender' => ['name' => 'Reality Bender', 'attack' => 90, 'cost' => 180000, 'cost_crystals' => 1000, 'cost_dark_matter' => 10, 'notes' => 'Alters local reality.', 'requires' => 'temporal_manipulator', 'armory_level_req' => 85],
                    'omni_tool' => ['name' => 'Omni-Tool', 'attack' => 100, 'cost' => 200000, 'cost_crystals' => 5000, 'cost_dark_matter' => 100, 'notes' => 'Can do anything.', 'requires' => 'reality_bender', 'armory_level_req' => 95],
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
                    // Tier 6-10 Expansion
                    'hard_light_shield_sentry' => ['name' => 'Hard Light Shield (Sentry)', 'defense' => 500, 'cost' => 600000, 'cost_crystals' => 20, 'notes' => 'Solid photon barrier for sentry.', 'requires' => 'bulwark_shield', 'armory_level_req' => 56],
                    'void_shield' => ['name' => 'Void Shield', 'defense' => 750, 'cost' => 700000, 'cost_crystals' => 100, 'notes' => 'Absorbs energy attacks.', 'requires' => 'hard_light_shield_sentry', 'armory_level_req' => 66],
                    'singularity_aegis' => ['name' => 'Singularity Aegis', 'defense' => 1000, 'cost' => 800000, 'cost_crystals' => 500, 'cost_dark_matter' => 5, 'notes' => 'Creates a localized gravity well.', 'requires' => 'void_shield', 'armory_level_req' => 76],
                    'temporal_barrier' => ['name' => 'Temporal Barrier', 'defense' => 1250, 'cost' => 900000, 'cost_crystals' => 2000, 'cost_dark_matter' => 20, 'notes' => 'Phases attacks out of time.', 'requires' => 'singularity_aegis', 'armory_level_req' => 86],
                    'reality_wall' => ['name' => 'Reality Wall', 'defense' => 1500, 'cost' => 1200000, 'cost_crystals' => 10000, 'cost_dark_matter' => 200, 'notes' => 'Indestructible defense.', 'requires' => 'temporal_barrier', 'armory_level_req' => 96],
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
                    // Tier 6-10 Expansion
                    'void_projector_sentry' => ['name' => 'Void Projector (Sentry)', 'defense' => 120, 'cost' => 240000, 'cost_crystals' => 10, 'notes' => 'Creates localized void pockets to absorb damage.', 'requires' => 'fortress_protocol', 'armory_level_req' => 57],
                    'quantum_emitter' => ['name' => 'Quantum Emitter', 'defense' => 150, 'cost' => 280000, 'cost_crystals' => 50, 'notes' => 'Emits quantum entanglement to disrupt enemy attacks.', 'requires' => 'void_projector_sentry', 'armory_level_req' => 67],
                    'temporal_field_generator' => ['name' => 'Temporal Field Generator', 'defense' => 180, 'cost' => 320000, 'cost_crystals' => 250, 'cost_dark_matter' => 1, 'notes' => 'Slows projectiles in an area.', 'requires' => 'quantum_emitter', 'armory_level_req' => 77],
                    'reality_distortion_unit' => ['name' => 'Reality Distortion Unit', 'defense' => 210, 'cost' => 360000, 'cost_crystals' => 1000, 'cost_dark_matter' => 10, 'notes' => 'Makes enemy attacks less effective.', 'requires' => 'temporal_field_generator', 'armory_level_req' => 87],
                    'omni_defense_matrix' => ['name' => 'Omni-Defense Matrix', 'defense' => 250, 'cost' => 400000, 'cost_crystals' => 5000, 'cost_dark_matter' => 100, 'notes' => 'Provides all-encompassing protection.', 'requires' => 'reality_distortion_unit', 'armory_level_req' => 97],
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
                    // Tier 6-10 Expansion
                    'void_recoil' => ['name' => 'Void Recoil', 'defense' => 90, 'cost' => 180000, 'cost_crystals' => 15, 'notes' => 'Teleports melee attackers away.', 'requires' => 'sentinels_wrath', 'armory_level_req' => 58],
                    'quantum_impact' => ['name' => 'Quantum Impact', 'defense' => 105, 'cost' => 210000, 'cost_crystals' => 75, 'notes' => 'Disrupts molecular cohesion of melee attackers.', 'requires' => 'void_recoil', 'armory_level_req' => 68],
                    'temporal_stasis_field' => ['name' => 'Temporal Stasis Field', 'defense' => 120, 'cost' => 240000, 'cost_crystals' => 300, 'cost_dark_matter' => 1, 'notes' => 'Freezes melee attackers in time.', 'requires' => 'quantum_impact', 'armory_level_req' => 78],
                    'reality_shockwave' => ['name' => 'Reality Shockwave', 'defense' => 135, 'cost' => 270000, 'cost_crystals' => 1500, 'cost_dark_matter' => 10, 'notes' => 'Generates a shockwave that distorts reality.', 'requires' => 'temporal_stasis_field', 'armory_level_req' => 88],
                    'entropy_pulse' => ['name' => 'Entropy Pulse', 'defense' => 150, 'cost' => 300000, 'cost_crystals' => 7500, 'cost_dark_matter' => 150, 'notes' => 'Decays melee attackers.', 'requires' => 'reality_shockwave', 'armory_level_req' => 98],
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
                    // Tier 6-10 Expansion
                    'void_helm' => ['name' => 'Void Helm', 'defense' => 60, 'cost' => 120000, 'cost_crystals' => 10, 'notes' => 'Phases head through attacks.', 'requires' => 'praetorian_helm', 'armory_level_req' => 59],
                    'quantum_helm' => ['name' => 'Quantum Helm', 'defense' => 70, 'cost' => 140000, 'cost_crystals' => 50, 'notes' => 'Absorbs energy to repair.', 'requires' => 'void_helm', 'armory_level_req' => 69],
                    'temporal_helm' => ['name' => 'Temporal Helm', 'defense' => 80, 'cost' => 160000, 'cost_crystals' => 250, 'cost_dark_matter' => 1, 'notes' => 'Predicts incoming blows.', 'requires' => 'quantum_helm', 'armory_level_req' => 79],
                    'reality_helm' => ['name' => 'Reality Helm', 'defense' => 90, 'cost' => 180000, 'cost_crystals' => 1000, 'cost_dark_matter' => 10, 'notes' => 'Distorts reality to deflect.', 'requires' => 'temporal_helm', 'armory_level_req' => 89],
                    'omni_helm' => ['name' => 'Omni-Helm', 'defense' => 100, 'cost' => 200000, 'cost_crystals' => 5000, 'cost_dark_matter' => 100, 'notes' => 'All-protecting, ultimate defense.', 'requires' => 'reality_helm', 'armory_level_req' => 99],
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
                    // Tier 6-10 Expansion
                    'hard_light_fortification' => ['name' => 'Hard Light Fortification', 'defense' => 210, 'cost' => 420000, 'cost_crystals' => 20, 'notes' => 'Creates a solid light barrier.', 'requires' => 'fortress', 'armory_level_req' => 50],
                    'void_bastion' => ['name' => 'Void Bastion', 'defense' => 245, 'cost' => 490000, 'cost_crystals' => 100, 'notes' => 'Sucks in incoming fire.', 'requires' => 'hard_light_fortification', 'armory_level_req' => 60],
                    'singularity_fortress' => ['name' => 'Singularity Fortress', 'defense' => 280, 'cost' => 560000, 'cost_crystals' => 500, 'cost_dark_matter' => 2, 'notes' => 'Generates a crushing gravity well.', 'requires' => 'void_bastion', 'armory_level_req' => 70],
                    'temporal_outpost' => ['name' => 'Temporal Outpost', 'defense' => 315, 'cost' => 630000, 'cost_crystals' => 2000, 'cost_dark_matter' => 20, 'notes' => 'Phases out of time.', 'requires' => 'singularity_fortress', 'armory_level_req' => 80],
                    'reality_citadel' => ['name' => 'Reality Citadel', 'defense' => 350, 'cost' => 700000, 'cost_crystals' => 10000, 'cost_dark_matter' => 200, 'notes' => 'Unmakes whatever attacks it.', 'requires' => 'temporal_outpost', 'armory_level_req' => 90],
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
                    // Tier 6-10 Expansion
                    'antimatter_drill' => ['name' => 'Antimatter Drill', 'credit_bonus' => 1000, 'cost' => 480000, 'cost_crystals' => 20, 'notes' => 'Drills through anything.', 'requires' => 'terraforming_beam', 'armory_level_req' => 25],
                    'matter_condenser' => ['name' => 'Matter Condenser', 'credit_bonus' => 1200, 'cost' => 560000, 'cost_crystals' => 100, 'notes' => 'Condenses rare elements.', 'requires' => 'antimatter_drill', 'armory_level_req' => 35],
                    'gravity_well_extractor' => ['name' => 'Gravity Well Extractor', 'credit_bonus' => 1500, 'cost' => 640000, 'cost_crystals' => 500, 'cost_dark_matter' => 5, 'notes' => 'Pulls resources from deep space.', 'requires' => 'matter_condenser', 'armory_level_req' => 45],
                    'quantum_synthesizer' => ['name' => 'Quantum Synthesizer', 'credit_bonus' => 1800, 'cost' => 720000, 'cost_crystals' => 2000, 'cost_dark_matter' => 20, 'notes' => 'Synthesizes gold from nothing.', 'requires' => 'gravity_well_extractor', 'armory_level_req' => 55],
                    'reality_fabricator' => ['name' => 'Reality Fabricator', 'credit_bonus' => 2500, 'cost' => 1000000, 'cost_crystals' => 10000, 'cost_dark_matter' => 200, 'notes' => 'Prints resources into existence.', 'requires' => 'quantum_synthesizer', 'armory_level_req' => 65],
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
                    // Tier 6-10 Expansion
                    'void_harvester' => ['name' => 'Void Harvester', 'credit_bonus' => 300, 'cost' => 45000, 'cost_crystals' => 10, 'notes' => 'Harvests from alternate dimensions.', 'requires' => 'genesis_device', 'armory_level_req' => 26],
                    'quantum_extractor' => ['name' => 'Quantum Extractor', 'credit_bonus' => 350, 'cost' => 52500, 'cost_crystals' => 50, 'notes' => 'Extracts subatomic particles.', 'requires' => 'void_harvester', 'armory_level_req' => 36],
                    'temporal_collector' => ['name' => 'Temporal Collector', 'credit_bonus' => 400, 'cost' => 60000, 'cost_crystals' => 250, 'cost_dark_matter' => 5, 'notes' => 'Collects resources from future.', 'requires' => 'quantum_extractor', 'armory_level_req' => 46],
                    'reality_well' => ['name' => 'Reality Well', 'credit_bonus' => 450, 'cost' => 67500, 'cost_crystals' => 1000, 'cost_dark_matter' => 20, 'notes' => 'Spawns resources into existence.', 'requires' => 'temporal_collector', 'armory_level_req' => 56],
                    'omni_harvester' => ['name' => 'Omni-Harvester', 'credit_bonus' => 500, 'cost' => 75000, 'cost_crystals' => 5000, 'cost_dark_matter' => 100, 'notes' => 'Harvests all known resource types.', 'requires' => 'reality_well', 'armory_level_req' => 66],
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
                    // Tier 6-10 Expansion
                    'void_suit' => ['name' => 'Void Suit', 'credit_bonus' => 500, 'cost' => 85000, 'cost_crystals' => 15, 'notes' => 'Phases out of danger.', 'requires' => 'power_armor', 'armory_level_req' => 27],
                    'quantum_weave_armor' => ['name' => 'Quantum Weave Armor', 'credit_bonus' => 600, 'cost' => 92500, 'cost_crystals' => 75, 'notes' => 'Disperses energy impacts.', 'requires' => 'void_suit', 'armory_level_req' => 37],
                    'temporal_exo_suit' => ['name' => 'Temporal Exo-Suit', 'credit_bonus' => 700, 'cost' => 100000, 'cost_crystals' => 300, 'cost_dark_matter' => 5, 'notes' => 'Rewinds minor damage.', 'requires' => 'quantum_weave_armor', 'armory_level_req' => 47],
                    'reality_anchor_suit' => ['name' => 'Reality Anchor Suit', 'credit_bonus' => 800, 'cost' => 107500, 'cost_crystals' => 1500, 'cost_dark_matter' => 25, 'notes' => 'Makes user immovable.', 'requires' => 'temporal_exo_suit', 'armory_level_req' => 57],
                    'entropy_harness' => ['name' => 'Entropy Harness', 'credit_bonus' => 900, 'cost' => 115000, 'cost_crystals' => 7500, 'cost_dark_matter' => 150, 'notes' => 'Decays incoming threats.', 'requires' => 'reality_anchor_suit', 'armory_level_req' => 67],
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
                    // Tier 6-10 Expansion
                    'void_sensor' => ['name' => 'Void Sensor', 'credit_bonus' => 400, 'attack' => 0, 'cost' => 60000, 'cost_crystals' => 10, 'notes' => 'Detects disturbances in the void.', 'requires' => 'omni_scanner', 'armory_level_req' => 28],
                    'quantum_radar' => ['name' => 'Quantum Radar', 'credit_bonus' => 500, 'attack' => 0, 'cost' => 70000, 'cost_crystals' => 50, 'notes' => 'Detects quantum fluctuations.', 'requires' => 'void_sensor', 'armory_level_req' => 38],
                    'temporal_sensor' => ['name' => 'Temporal Sensor', 'credit_bonus' => 600, 'attack' => 0, 'cost' => 80000, 'cost_crystals' => 250, 'cost_dark_matter' => 5, 'notes' => 'Detects echoes from past/future.', 'requires' => 'quantum_radar', 'armory_level_req' => 48],
                    'reality_lens' => ['name' => 'Reality Lens', 'credit_bonus' => 700, 'attack' => 0, 'cost' => 90000, 'cost_crystals' => 1000, 'cost_dark_matter' => 25, 'notes' => 'Reveals hidden aspects of reality.', 'requires' => 'temporal_sensor', 'armory_level_req' => 58],
                    'god_sight_scanner' => ['name' => 'God Sight Scanner', 'credit_bonus' => 800, 'attack' => 0, 'cost' => 100000, 'cost_crystals' => 5000, 'cost_dark_matter' => 100, 'notes' => 'Universal perception.', 'requires' => 'reality_lens', 'armory_level_req' => 68],
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
                    // Tier 6-10 Expansion
                    'void_drone' => ['name' => 'Void Drone', 'credit_bonus' => 200, 'attack' => 0, 'cost' => 30000, 'cost_crystals' => 10, 'notes' => 'Operates in zero-point energy.', 'requires' => 'replicator_drone', 'armory_level_req' => 29],
                    'quantum_drone' => ['name' => 'Quantum Drone', 'credit_bonus' => 250, 'attack' => 0, 'cost' => 35000, 'cost_crystals' => 50, 'notes' => 'Builds using quantum tunneling.', 'requires' => 'void_drone', 'armory_level_req' => 39],
                    'temporal_drone' => ['name' => 'Temporal Drone', 'credit_bonus' => 300, 'attack' => 0, 'cost' => 40000, 'cost_crystals' => 250, 'cost_dark_matter' => 5, 'notes' => 'Accelerates construction time.', 'requires' => 'quantum_drone', 'armory_level_req' => 49],
                    'reality_drone' => ['name' => 'Reality Drone', 'credit_bonus' => 350, 'attack' => 0, 'cost' => 45000, 'cost_crystals' => 1000, 'cost_dark_matter' => 25, 'notes' => 'Fabricates objects from raw data.', 'requires' => 'temporal_drone', 'armory_level_req' => 59],
                    'omni_drone' => ['name' => 'Omni-Drone', 'credit_bonus' => 400, 'attack' => 0, 'cost' => 50000, 'cost_crystals' => 5000, 'cost_dark_matter' => 100, 'notes' => 'All-purpose construction and repair.', 'requires' => 'reality_drone', 'armory_level_req' => 69],
                ]
            ]
        ]
    ]
];
