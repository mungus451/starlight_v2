<?php

/**
* Black Market Configuration
* Defines costs and logic for The Void Syndicate features.
*/

return [
'costs' => [
'stat_respec' => 250000.0, // Crystals
'turn_refill' => 10000.0, // Crystals
'citizen_package' => 250000.0, // Crystals
'void_container' => 10000.0, // Crystals
'shadow_contract' => 5000000.0, // Crystals
'radar_jamming' => 50000.0, // Crystals
'safehouse' => 100000.0, // Crystals
],

'rates' => [
    'laundering' => 1.15, // 1.15 Credits per 1 Chip
],

'quantities' => [
'turn_refill_amount' => 50, // Turns restored
'citizen_package_amount' => 500, // Citizens added
],

// Loot Box Probabilities (Weights determine rarity relative to total sum)
// Current Total Weight: 100
'void_container_loot' => [
// --- GOOD OUTCOMES (74%) ---
'credits_medium' => [
'weight' => 20,
'type' => 'credits',
'min' => 10000,
'max' => 500000,
'label' => 'Cache of Credits'
],
'credits_high' => [
'weight' => 23,
'type' => 'credits',
'min' => 500000,
'max' => 7500000,
'label' => 'Vault of Credits'
],
'soldiers' => [
'weight' => 17,
'type' => 'unit',
'unit' => 'soldiers',
'min' => 500,
'max' => 2000,
'label' => 'Mercenary Platoon'
],
'spies' => [
'weight' => 13,
'type' => 'unit',
'unit' => 'spies',
'min' => 10,
'max' => 1500,
'label' => 'Covert Operatives'
],
'jackpot' => [
'weight' => 1,
'type' => 'crystals',
'min' => 500000,
'max' => 100000000,
'label' => 'JACKPOT! Naquadah Cache'
],

// --- NEUTRAL OUTCOMES (13%) ---
'space_dust' => [
'weight' => 8,
'type' => 'neutral',
'label' => 'Space Dust',
'text' => 'You open the container... it contains nothing but cosmic dust.'
],
'scrap_metal' => [
'weight' => 5,
'type' => 'neutral',
'label' => 'Rusted Scrap',
'text' => 'The container is filled with useless rusted metal shards.'
],

// --- BAD OUTCOMES (13%) ---
'trap_credits' => [
'weight' => 4,
'type' => 'credits_loss',
'min' => 50000,
'max' => 200000000,
'label' => 'Credit Siphon Trap',
'text' => 'IT\'S A TRAP! A hacking algorithm drains your account.'
],
'ambush_spies' => [
'weight' => 7,
'type' => 'unit_loss',
'unit' => 'spies',
'min' => 10,
'max' => 2000,
'label' => 'The Double Cross',
'text' => 'IT\'S A TRAP! A traitor turned you into their sentries. Casualties sustained.'
],
'ambush_soldiers' => [
'weight' => 2,
'type' => 'unit_loss',
'unit' => 'soldiers',
'min' => 10,
'max' => 1000,
'label' => 'Void Ambush',
'text' => 'The container was rigged with explosives! Casualties sustained.'
]
]
];