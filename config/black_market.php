<?php

/**
* Black Market Configuration
* Defines costs and logic for The Void Syndicate features.
*/

return [
'costs' => [
'stat_respec' => 50000000.0, // Crystals
'turn_refill' => 10000.0, // Crystals
'citizen_package' => 250000.0, // Crystals
'void_container' => 10000.0, // Crystals
'shadow_contract' => 5000000.0, // Crystals
],

'quantities' => [
'turn_refill_amount' => 50, // Turns restored
'citizen_package_amount' => 500, // Citizens added
],

// Loot Box Probabilities (Weights should sum to roughly 100)
'void_container_loot' => [
'credits_medium' => [
'weight' => 50,
'type' => 'credits',
'min' => 1000000,
'max' => 5000000,
'label' => 'Cache of Credits'
],
'credits_high' => [
'weight' => 25,
'type' => 'credits',
'min' => 10000000,
'max' => 25000000,
'label' => 'Vault of Credits'
],
'soldiers' => [
'weight' => 15,
'type' => 'unit',
'unit' => 'soldiers',
'min' => 500,
'max' => 1000,
'label' => 'Mercenary Platoon'
],
'spies' => [
'weight' => 9,
'type' => 'unit',
'unit' => 'spies',
'min' => 50,
'max' => 150,
'label' => 'Covert Operatives'
],
'jackpot' => [
'weight' => 1,
'type' => 'crystals',
'min' => 500,
'max' => 1000,
'label' => 'Jackpot! Naquadah Cache'
]
]
];