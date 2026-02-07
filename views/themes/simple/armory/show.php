<?php
// Armory Show (Command Bridge Layout)
// Drop-in replacement for /views/armory/show.php
//
// NOTE: No inline CSS. All styling must be handled via external stylesheets.
//
// Keeps existing JS hooks/classes used by /js/armory.js:
// - .slot-card, .config-select, .config-qty
// - .item-stats-row, .item-description-text, .item-cost-display, .additional-costs, .prereq-info, .item-owned-display
// - .manufacture-config-form, .equip-config-form, .btn-config-max, .btn-config-buy, .btn-config-equip, .btn-config-unequip
//
// Expected variables from ArmoryController (via ArmoryPresenter):
//  - $armoryConfig, $manufacturingData, $inventory, $loadouts
//  - $userResources, $userStructures, $discountPercent, $hasDiscount, $csrf_token

/* @var \App\Models\Entities\UserResource $userResources */
/* @var \App\Models\Entities\UserStructure $userStructures */
/* @var array $armoryConfig */
/* @var array $manufacturingData */
/* @var array $inventory */
/* @var array $loadouts */
/* @var float $discountPercent */
/* @var bool $hasDiscount */

$unitIcons = [
    'soldier' => 'fas fa-crosshairs',
    'guard'   => 'fas fa-shield-alt',
    'spy'     => 'fas fa-user-secret',
    'sentry'  => 'fas fa-binoculars',
    'worker'  => 'fas fa-hammer',
];

$categoryIcons = [
    'main_weapon' => 'fas fa-gun',
    'sidearm'     => 'fas fa-bolt',
    'melee'       => 'fas fa-hand-fist',
    'headgear'    => 'fas fa-mask',
    'explosives'  => 'fas fa-bomb',

    // Defensive / specialized keys (safe fallbacks if present in config)
    'armor_suit'            => 'fas fa-vest',
    'secondary_defense'     => 'fas fa-microchip',
    'melee_counter'         => 'fas fa-user-shield',
    'defensive_headgear'    => 'fas fa-hard-hat',
    'defensive_deployable'  => 'fas fa-dungeon',

    // Spy / infiltration keys
    'silenced_projectors'   => 'fas fa-wind',
    'cloaking_disruption'   => 'fas fa-ghost',
    'concealed_blades'      => 'fas fa-cut',
    'intel_suite'           => 'fas fa-brain',
    'infiltration_gadgets'  => 'fas fa-tools',

    // Sentry keys
    'shields'                    => 'fas fa-shield-virus',
    'secondary_defensive_systems'=> 'fas fa-cogs',
    'shield_bash'                => 'fas fa-hand-rock',
    'helmets'                    => 'fas fa-hard-hat',
    'fortifications'             => 'fas fa-archway',

    // Worker keys
    'mining_lasers_drills'  => 'fas fa-hammer',
    'resource_enhancement'  => 'fas fa-magnet',
    'exo_rig_plating'       => 'fas fa-robot',
    'scanners'              => 'fas fa-search-plus',
    'drones'                => 'fas fa-helicopter',
];

// Global equipped coverage: % of slots equipped across all units
$globalSlots = 0;
$globalEquipped = 0;
foreach ($armoryConfig as $uKey => $uData) {
    foreach (($uData['categories'] ?? []) as $cKey => $cData) {
        $globalSlots++;
        if (!empty(($loadouts[$uKey][$cKey] ?? null))) {
            $globalEquipped++;
        }
    }
}
$globalEquippedPct = $globalSlots > 0 ? (int)round(($globalEquipped / $globalSlots) * 100) : 0;

// Placeholder for a future true uplift metric (wire to presenter later if/when available)
$equipmentUpliftPct = 0;
?>

<div class="armory-bridge">

    <!-- COMMAND HEADER (Status Bar) -->
    <header class="armory-bridge__header">
        <div class="armory-bridge__title">
            <h1 class="armory-bridge__h1">Armory</h1>
            <div class="armory-bridge__sub">Requisitions // Loadouts // Manufacturing</div>
        </div>

        <div class="armory-bridge__statusbar">
            <div class="armory-metric">
                <div class="armory-metric__label">Credits on Hand</div>
                <div class="armory-metric__value text-warning">
                    <?= number_format((int)$userResources->credits) ?>
                </div>
            </div>

            <div class="armory-metric">
                <div class="armory-metric__label">Armory Level</div>
                <div class="armory-metric__value text-neon-blue">
                    Level <?= (int)$userStructures->armory_level ?>
                </div>
            </div>

            <div class="armory-metric">
                <div class="armory-metric__label">Global Equipment Uplift</div>
                <div class="armory-metric__value text-success">
                    <?= number_format((float)$equipmentUpliftPct, 0) ?>%
                </div>
            </div>

            <div class="armory-metric">
                <div class="armory-metric__label">Slots Equipped</div>
                <div class="armory-metric__value">
                    <?= (int)$globalEquippedPct ?>%
                </div>
            </div>

            <div class="armory-metric">
                <div class="armory-metric__label">Charisma Bonus</div>
                <div class="armory-metric__value <?= $hasDiscount ? 'text-success' : 'text-muted' ?>">
                    <?= number_format(((float)$discountPercent) * 100, 1) ?>% Discount
                </div>
            </div>
        </div>
    </header>

    <!-- UNIT TABS -->
    <nav class="tabs-nav armory-bridge__tabs">
        <?php $firstUnit = array_key_first($armoryConfig); ?>
        <?php foreach ($armoryConfig as $unitKey => $unitData): ?>
            <a class="tab-link <?= $unitKey === $firstUnit ? 'active' : '' ?>"
               data-tab="tab-<?= htmlspecialchars($unitKey) ?>">
                <i class="<?= $unitIcons[$unitKey] ?? 'fas fa-user' ?>"></i>
                <?= htmlspecialchars($unitData['title'] ?? ucfirst($unitKey)) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- UNIT PANES -->
    <?php foreach ($armoryConfig as $unitKey => $unitData): ?>
        <?php
            $categories = $unitData['categories'] ?? [];
            $unitSlots = count($categories);

            $unitEquipped = 0;
            foreach ($categories as $catKey => $catData) {
                if (!empty(($loadouts[$unitKey][$catKey] ?? null))) {
                    $unitEquipped++;
                }
            }
            $unitEquippedPct = $unitSlots > 0 ? (int)round(($unitEquipped / $unitSlots) * 100) : 0;

            $unitTiers = $manufacturingData[$unitKey] ?? [];
        ?>

        <section id="tab-<?= htmlspecialchars($unitKey) ?>"
                 class="tab-content <?= $unitKey === $firstUnit ? 'active' : '' ?> armory-bridge__pane">

            <!-- UNIT SNAPSHOT BAR -->
            <div class="armory-unitbar">
                <div class="armory-unitbar__left">
                    <div class="armory-unitbar__badge">
                        <i class="<?= $unitIcons[$unitKey] ?? 'fas fa-user' ?>"></i>
                        <?= htmlspecialchars($unitData['title'] ?? ucfirst($unitKey)) ?>
                    </div>

                    <div class="armory-unitbar__meta">
                        <span class="text-muted">Slots:</span> <strong><?= (int)$unitSlots ?></strong>
                        <span class="text-muted ms-2">Equipped:</span> <strong><?= (int)$unitEquippedPct ?>%</strong>
                    </div>
                </div>

                <div class="armory-unitbar__right">
                    <a class="armory-unitbar__link" href="/armory/mobile/loadout/<?= htmlspecialchars($unitKey) ?>">Mobile Loadout</a>
                    <a class="armory-unitbar__link" href="/armory/mobile/manage/<?= htmlspecialchars($unitKey) ?>/1">Mobile Tier Mgmt</a>
                </div>
            </div>

            <div class="armory-layout">

                <!-- LEFT: LOADOUT SLOTS -->
                <div class="armory-left">
                    <div class="armory-panel-header">
                        <div class="armory-panel-title">Loadout</div>
                        <div class="armory-panel-sub text-muted">Configure active slot assignments and requisitions.</div>
                    </div>

                    <div class="armory-slots">
                        <?php foreach ($categories as $catKey => $catData): ?>
                            <article class="slot-card armory-slot"
                                     data-unit="<?= htmlspecialchars($unitKey) ?>"
                                     data-category="<?= htmlspecialchars($catKey) ?>">

                                <div class="armory-slot__header">
                                    <div class="armory-slot__icon">
                                        <i class="<?= $categoryIcons[$catKey] ?? 'fas fa-box' ?>"></i>
                                    </div>
                                    <div class="armory-slot__title">
                                        <div class="armory-slot__kicker">Slot</div>
                                        <div class="armory-slot__name"><?= htmlspecialchars($catData['title'] ?? $catKey) ?></div>
                                    </div>
                                </div>

                                <div class="armory-slot__body">
                                    <?php
                                        $equippedKey = $loadouts[$unitKey][$catKey] ?? null;
                                        $equippedItem = $equippedKey ? ($catData['items'][$equippedKey] ?? null) : null;
                                    ?>

                                    <div class="armory-slot__equipped">
                                        <div class="armory-label">Currently Equipped</div>
                                        <div class="equipped-item-display">
                                            <strong class="text-neon-blue current-equipped-name">
                                                <?= $equippedItem ? htmlspecialchars($equippedItem['name']) : 'None' ?>
                                            </strong>
                                        </div>
                                    </div>

                                    <div class="armory-slot__select">
                                        <label class="armory-label" for="sel-<?= htmlspecialchars($unitKey) ?>-<?= htmlspecialchars($catKey) ?>">Requisition Catalog</label>
                                        <select id="sel-<?= htmlspecialchars($unitKey) ?>-<?= htmlspecialchars($catKey) ?>"
                                                class="form-select config-select"
                                                data-unit="<?= htmlspecialchars($unitKey) ?>"
                                                data-category="<?= htmlspecialchars($catKey) ?>">
                                            <?php foreach (($catData['items'] ?? []) as $itemKey => $item): ?>
                                                <?php $owned = (int)($inventory[$itemKey] ?? 0); ?>
                                                <option value="<?= htmlspecialchars($itemKey) ?>" <?= $equippedKey === $itemKey ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($item['name']) ?> (Owned: <?= number_format($owned) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Dynamic Info Area (populated by JS) -->
                                    <div class="armory-iteminfo">
                                        <div class="item-stats-row armory-iteminfo__stats"><!-- JS --></div>

                                        <p class="item-description-text armory-iteminfo__desc"><!-- JS --></p>

                                        <div class="armory-iteminfo__costs">
                                            <div class="flex-between">
                                                <span>Credit Cost</span>
                                                <strong class="text-warning item-cost-display">0</strong>
                                            </div>

                                            <div class="additional-costs"><!-- JS --></div>

                                            <div class="prereq-info"><!-- JS --></div>

                                            <div class="flex-between">
                                                <span>In Inventory</span>
                                                <strong class="text-info item-owned-display">0</strong>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="armory-slot__actions">

                                        <form class="manufacture-config-form" method="POST" action="/armory/manufacture">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                            <input type="hidden" name="item_key" class="dynamic-item-key">

                                            <div class="armory-slot__buyrow">
                                                <input type="number" name="quantity" class="form-control config-qty" value="1" min="1">
                                                <button type="button" class="btn btn-secondary btn-sm btn-config-max">MAX</button>
                                                <button type="submit" class="btn btn-primary btn-config-buy">
                                                    <i class="fas fa-shopping-cart"></i>
                                                    Manufacture
                                                </button>
                                            </div>

                                            <div class="transaction-preview is-hidden">
                                                <div class="transaction-preview__title">Requisition Preview</div>
                                                <div class="preview-costs"><!-- JS --></div>
                                            </div>
                                        </form>

                                        <form class="equip-config-form" method="POST" action="/armory/equip">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                            <input type="hidden" name="unit_key" value="<?= htmlspecialchars($unitKey) ?>">
                                            <input type="hidden" name="category_key" value="<?= htmlspecialchars($catKey) ?>">
                                            <input type="hidden" name="item_key" class="dynamic-item-key">

                                            <div class="armory-slot__equiprow">
                                                <button type="submit" class="btn btn-outline-info btn-config-equip">
                                                    <i class="fas fa-check-circle"></i>
                                                    Equip
                                                </button>
                                                <button type="button" class="btn btn-outline-warning btn-config-unequip" title="Unequip Slot">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </form>

                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- RIGHT: CATALOG + STRATEGIC CONTROLS -->
                <aside class="armory-right">
                    <div class="armory-panel-header">
                        <div class="armory-panel-title">Manufacturing Catalog</div>
                        <div class="armory-panel-sub text-muted">Browse by tier. Use the slot panel to requisition and equip.</div>
                    </div>

                    <?php if (!empty($unitTiers)): ?>
                        <?php foreach ($unitTiers as $tier => $items): ?>
                            <?php $tier = (int)$tier; ?>
                            <div class="armory-tier">
                                <div class="armory-tier__header">
                                    <div class="armory-tier__title">Tier <?= $tier ?> Equipment</div>
                                    <div class="armory-tier__meta text-muted"><?= count($items) ?> blueprints</div>
                                </div>

                                <div class="armory-tier__grid">
                                    <?php foreach ($items as $it): ?>
                                        <?php
                                            $name = $it['name'] ?? ($it['item_name'] ?? 'Unknown Item');
                                            $owned = (int)($inventory[$it['item_key']] ?? 0);
                                            $cost  = (int)($it['effective_cost'] ?? 0);
                                            $badges = $it['stat_badges'] ?? [];
                                            $notes  = $it['notes'] ?? '';
                                        ?>
                                        <div class="armory-itemcard">
                                            <div class="armory-itemcard__top">
                                                <div class="armory-itemcard__name"><?= htmlspecialchars($name) ?></div>
                                                <div class="armory-itemcard__owned text-muted">Owned: <?= number_format($owned) ?></div>
                                            </div>

                                            <div class="armory-itemcard__badges">
                                                <?php foreach (array_slice($badges, 0, 3) as $b): ?>
                                                    <span class="stat-pill <?= htmlspecialchars($b['type'] ?? 'utility') ?>">
                                                        <?= htmlspecialchars($b['label'] ?? '') ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>

                                            <?php if ($notes): ?>
                                                <div class="armory-itemcard__desc text-muted"><?= htmlspecialchars($notes) ?></div>
                                            <?php endif; ?>

                                            <div class="armory-itemcard__cost">
                                                <span class="text-muted">Cost</span>
                                                <strong class="text-warning"><?= number_format($cost) ?></strong>
                                            </div>

                                            <div class="armory-itemcard__hint text-muted">
                                                Select this item in the matching slot to requisition or equip.
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <?php if ($tier >= 2): ?>
                                    <div class="armory-tier__lock text-muted">
                                        Higher tiers may require prerequisite items and armory levels.
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="armory-empty text-muted">No manufacturing data available for this unit type.</div>
                    <?php endif; ?>

                    <div class="armory-summary">
                        <div class="armory-summary__title">Session Summary</div>

                        <div class="armory-summary__row">
                            <span class="text-muted">Total Credits Spent</span>
                            <strong>0</strong>
                        </div>
                        <div class="armory-summary__row">
                            <span class="text-muted">Total Power Gained</span>
                            <strong>0</strong>
                        </div>
                        <div class="armory-summary__row">
                            <span class="text-muted">Readiness Change</span>
                            <strong class="text-muted">UNCHANGED</strong>
                        </div>

                        <div class="armory-summary__modes">
                            <div class="armory-mode is-active">Manual</div>
                            <div class="armory-mode">Sustain</div>
                            <div class="armory-mode">Surge</div>
                        </div>

                        <div class="armory-summary__note text-muted">
                            Sustain/Surge are UI-forward modes for future automation. Current actions are manual requisitions.
                        </div>
                    </div>
                </aside>

            </div>
        </section>
    <?php endforeach; ?>

</div>

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
