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

// Loot Box Probabilities (Weights determine rarity relative to total sum)
'void_container_loot' => [
// --- GOOD OUTCOMES ---
'credits_medium' => [
'weight' => 20,
'type' => 'credits',
'min' => 10000,
'max' => 500000,
'label' => 'Cache of Credits'
],
'credits_high' => [
'weight' => 20,
'type' => 'credits',
'min' => 500000,
'max' => 7500000,
'label' => 'Vault of Credits'
],
'soldiers' => [
'weight' => 12,
'type' => 'unit',
'unit' => 'soldiers',
'min' => 500,
'max' => 2000,
'label' => 'Mercenary Platoon'
],
'guards' => [
'weight' => 12,
'type' => 'unit',
'unit' => 'guards',
'min' => 500,
'max' => 2000,
'label' => 'Elite Guard Detail'
],
'spies' => [
'weight' => 8,
'type' => 'unit',
'unit' => 'spies',
'min' => 10,
'max' => 1500,
'label' => 'Covert Operatives'
],
'sentries' => [
'weight' => 8,
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
'weight' => 5,
'type' => 'dark_matter',
'min' => 50,
'max' => 500,
'label' => 'Extracted Dark Matter'
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
]
]
];