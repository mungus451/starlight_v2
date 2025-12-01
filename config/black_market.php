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
],

'quantities' => [
'turn_refill_amount' => 50, // Turns restored
'citizen_package_amount' => 500, // Citizens added
],

// Loot Box Probabilities (Weights determine rarity relative to total sum)
// Current Total Weight: 100
'void_container_loot' => [
// --- GOOD OUTCOMES (81%) ---
'credits_medium' => [
'weight' => 25,
'type' => 'credits',
'min' => 10000,
'max' => 500000,
'label' => 'Cache of Credits'
],
'credits_high' => [
'weight' => 20,
'type' => 'credits',
'min' => 100000,
'max' => 2500000,
'label' => 'Vault of Credits'
],
'soldiers' => [
'weight' => 15,
'type' => 'unit',
'unit' => 'soldiers',
'min' => 500,
'max' => 4000,
'label' => 'Mercenary Platoon'
],
'spies' => [
'weight' => 20,
'type' => 'unit',
'unit' => 'spies',
'min' => 50,
'max' => 1500,
'label' => 'Covert Operatives'
],
'jackpot' => [
'weight' => 1,
'type' => 'crystals',
'min' => 50000,
'max' => 1000000,
'label' => 'JACKPOT! Naquadah Cache'
],

// --- NEUTRAL OUTCOMES (15%) ---
'space_dust' => [
'weight' => 10,
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

// --- BAD OUTCOMES (4%) ---
'trap_credits' => [
'weight' => 2,
'type' => 'credits_loss',
'min' => 500000,
'max' => 2000000,
'label' => 'Credit Siphon Trap',
'text' => 'IT\'S A TRAP! A hacking algorithm drains your account.'
],
'ambush_soldiers' => [
'weight' => 2,
'type' => 'unit_loss',
'unit' => 'soldiers',
'min' => 50,
'max' => 200,
'label' => 'Void Ambush',
'text' => 'The container was rigged with explosives! Casualties sustained.'
]
]
];