<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\UserResource $userResources */
/* @var \App\Models\Entities\UserStructure $userStructures */
/* @var array $armoryConfig */
/* @var array $manufacturingData Prepared DTOs for the view */
/* @var array $inventory */
/* @var array $loadouts */
/* @var float $discountPercent */
/* @var bool $hasDiscount */

// Simple Icon Mapping Helpers
$unitIcons = [
    'soldier' => 'fas fa-fist-raised',
    'guard'   => 'fas fa-shield-alt',
    'spy'     => 'fas fa-user-secret',
    'sentry'  => 'fas fa-satellite-dish',
    'worker'  => 'fas fa-hard-hat'
];

$categoryIcons = [
    'main_weapon' => 'fas fa-gun',
    'sidearm'     => 'fas fa-hand-holding-gun',
    'melee'       => 'fas fa-sword',
    'headgear'    => 'fas fa-head-side-mask',
    'explosives'  => 'fas fa-bomb',
    'armor_suit'  => 'fas fa-vest',
    'secondary_defense' => 'fas fa-microchip',
    'melee_counter' => 'fas fa-user-shield',
    'defensive_headgear' => 'fas fa-helmet-battle',
    'defensive_deployable' => 'fas fa-door-closed',
    'silenced_projectors' => 'fas fa-wind',
    'cloaking_disruption' => 'fas fa-ghost',
    'concealed_blades' => 'fas fa-cut',
    'intel_suite' => 'fas fa-brain',
    'infiltration_gadgets' => 'fas fa-tools',
    'shields' => 'fas fa-shield-virus',
    'secondary_defensive_systems' => 'fas fa-cogs',
    'shield_bash' => 'fas fa-impact--shield',
    'helmets' => 'fas fa-hard-hat',
    'fortifications' => 'fas fa-fort-awesome',
    'mining_lasers_drills' => 'fas fa-hammer',
    'resource_enhancement' => 'fas fa-magnet',
    'exo_rig_plating' => 'fas fa-robot',
    'scanners' => 'fas fa-search-plus',
    'drones' => 'fas fa-helicopter'
];
?>

<div class="advisor-main-content">
    <!-- 1. Context Banner -->
    <div class="card-header-main mb-4 border-radius-8">
        <div class="flex-between w-full align-items-center" style="display:flex; width: 100%; justify-content: space-between;">
            <div class="card-title-group">
                <span class="text-neon-blue">Empire Command</span>
                <h4>Armory Requisitions</h4>
            </div>
            <div class="flex-gap-sm">
                <div class="badge bg-dark border-secondary">
                    Armory Lvl <?= $userStructures->armory_level ?>
                </div>
                <div class="badge bg-dark border-success">
                    <i class="fas fa-tags icon-gold"></i> <?= number_format($discountPercent * 100, 1) ?>% Discount
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Unit Selection Tabs -->
    <div class="tabs-nav mb-4 justify-content-center">
        <?php foreach ($armoryConfig as $unitKey => $unitData): ?>
            <a class="tab-link <?= $unitKey === 'soldier' ? 'active' : '' ?>" data-tab="tab-<?= $unitKey ?>">
                <i class="<?= $unitIcons[$unitKey] ?? 'fas fa-user' ?>" style="margin-right: 8px;"></i> <?= htmlspecialchars($unitKey) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- 3. Configurator Tabs Content -->
    <?php foreach ($armoryConfig as $unitKey => $unitData): ?>
        <div id="tab-<?= $unitKey ?>" class="tab-content <?= $unitKey === 'soldier' ? 'active' : '' ?>">
            <div class="structures-grid">
                <?php foreach ($unitData['categories'] as $catKey => $catData): ?>
                    <div class="structure-card slot-card" 
                         data-unit="<?= $unitKey ?>" 
                         data-category="<?= $catKey ?>">
                        
                        <!-- Header -->
                        <div class="card-header-main">
                            <div class="card-icon">
                                <i class="<?= $categoryIcons[$catKey] ?? 'fas fa-box' ?>"></i>
                            </div>
                            <div class="card-title-group">
                                <span>Slot Configuration</span>
                                <h4><?= htmlspecialchars($catData['title']) ?></h4>
                            </div>
                        </div>

                        <!-- Body -->
                        <div class="card-body-main">
                            <!-- Equipped Status -->
                            <div class="equipped-status mb-3">
                                <label class="text-muted font-07 text-uppercase mb-1" style="display: block;">Currently Equipped</label>
                                <div class="equipped-item-display">
                                    <?php 
                                        $equippedKey = $loadouts[$unitKey][$catKey] ?? null;
                                        $equippedItem = $equippedKey ? ($catData['items'][$equippedKey] ?? null) : null;
                                    ?>
                                    <strong class="text-neon-blue current-equipped-name">
                                        <?= $equippedItem ? htmlspecialchars($equippedItem['name']) : 'None' ?>
                                    </strong>
                                </div>
                            </div>

                            <hr class="opacity-20 mb-3">

                            <!-- Requisition Selection -->
                            <div class="form-group mb-3">
                                <label class="text-muted font-07 text-uppercase mb-1" style="display: block;">Requisition Catalog</label>
                                <select class="form-select config-select w-full bg-dark text-light border-secondary" 
                                        style="padding: 0.5rem; border-radius: 6px;"
                                        data-unit="<?= $unitKey ?>" 
                                        data-category="<?= $catKey ?>">
                                    <?php foreach ($catData['items'] as $itemKey => $item): 
                                        $owned = (int)($inventory[$itemKey] ?? 0);
                                    ?>
                                        <option value="<?= $itemKey ?>" <?= $equippedKey === $itemKey ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($item['name']) ?> (Owned: <?= number_format($owned) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Dynamic Info Area -->
                            <div class="item-info-area p-3 mb-3" style="padding: 1rem;">
                                <div class="item-stats-row mb-3" style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                    <!-- Populated by JS -->
                                </div>
                                
                                <p class="item-description-text font-08 text-muted mb-3" style="min-height: 40px; margin-bottom: 1rem;">
                                    <!-- Populated by JS -->
                                </p>

                                <div class="cost-info font-08">
                                    <div class="flex-between mb-1" style="display:flex; justify-content:space-between;">
                                        <span>Credit Cost:</span>
                                        <strong class="text-warning item-cost-display">0</strong>
                                    </div>
                                    <div class="flex-between mb-1 additional-costs" style="display:flex; flex-direction:column;">
                                        <!-- Crystals/Dark Matter populated by JS -->
                                    </div>
                                    <div class="flex-between" style="display:flex; justify-content:space-between;">
                                        <span>In Inventory:</span>
                                        <strong class="text-info item-owned-display">0</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="card-footer-actions mt-auto">
                                <form class="manufacture-config-form" method="POST" action="/armory/manufacture">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                    <input type="hidden" name="item_key" class="dynamic-item-key">
                                    
                                    <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                                        <input type="number" name="quantity" class="form-control config-qty" value="1" min="1">
                                        <button type="submit" class="btn btn-primary btn-config-buy" style="flex-grow: 1;">
                                            <i class="fas fa-shopping-cart" style="margin-right: 5px;"></i> Buy
                                        </button>
                                    </div>
                                </form>

                                <form class="equip-config-form" method="POST" action="/armory/equip">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                    <input type="hidden" name="unit_key" value="<?= $unitKey ?>">
                                    <input type="hidden" name="category_key" value="<?= $catKey ?>">
                                    <input type="hidden" name="item_key" class="dynamic-item-key">
                                    <div class="d-flex gap-2" style="display:flex; gap: 0.5rem;">
                                        <button type="submit" class="btn btn-outline-info flex-grow-1 btn-config-equip">
                                            <i class="fas fa-check-circle" style="margin-right: 5px;"></i> Equip
                                        </button>
                                        <button type="button" class="btn btn-outline-warning btn-config-unequip" title="Unequip Slot">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Raw Data for JavaScript -->
<script>
    window.ArmoryData = {
        config: <?= json_encode($armoryConfig) ?>,
        manufacturing: <?= json_encode($manufacturingData) ?>,
        inventory: <?= json_encode($inventory) ?>,
        loadouts: <?= json_encode($loadouts) ?>,
        userResources: {
            credits: <?= (int)$userResources->credits ?>,
            crystals: <?= (int)$userResources->naquadah_crystals ?>,
            darkMatter: <?= (int)$userResources->dark_matter ?>
        }
    };
</script>

<script src="/js/armory.js?v=<?= time() ?>"></script>
