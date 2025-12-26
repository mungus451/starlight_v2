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
'void_container_loot' => [
// --- GOOD OUTCOMES ---
'credits_medium' => [
'weight' => 20,
'type' => 'credits',
'min' => 10000,
'max' => 5000000,
'label' => 'Cache of Credits'
],
'credits_high' => [
'weight' => 20,
'type' => 'credits',
'min' => 50000000,
'max' => 750000000,
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
'min' => 100,
'max' => 2500,
'label' => 'Covert Operatives'
],
'sentries' => [
'weight' => 8,
'type' => 'unit',
'unit' => 'sentries',
'min' => 100,
'max' => 2500,
'label' => 'Security Detail'
],
'jackpot' => [
'weight' => 1,
'type' => 'crystals',
'min' => 500000,
'max' => 1000000000,
'label' => 'JACKPOT! Naquadah Cache'
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
'min' => 500,
'max' => 20000000,
'label' => 'Credit Siphon Trap',
'text' => 'IT\'S A TRAP! A hacking algorithm drains your account.'
],
'ambush_spies' => [
'weight' => 3,
'type' => 'unit_loss',
'unit' => 'spies',
'min' => 100,
'max' => 5000,
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