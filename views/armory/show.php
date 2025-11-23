<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\UserResource $userResources */
/* @var \App\Models\Entities\UserStructure $userStructures */
/* @var \App\Models\Entities\UserStats $userStats */
/* @var array $armoryConfig */
/* @var array $inventory */
/* @var array $loadouts */
/* @var array $itemLookup */
/* @var array $discountConfig */

// --- Calculate Discount Percentage for View ---
$charisma = $userStats->charisma_points ?? 0;
$rate = $discountConfig['discount_per_charisma'] ?? 0.01;
$cap = $discountConfig['max_discount'] ?? 0.75;
$discountPercent = min($charisma * $rate, $cap); 
$hasDiscount = $discountPercent > 0;
?>

<div class="container-full">
    <h1>Armory</h1>

    <div class="resource-header-card">
        <div class="header-stat">
            <span>Credits on Hand</span>
            <strong class="accent" id="global-user-credits" data-credits="<?= $userResources->credits ?>">
                <?= number_format($userResources->credits) ?>
            </strong>
        </div>
        <div class="header-stat">
            <span>Armory Level</span>
            <strong class="accent-teal" id="global-armory-level" data-level="<?= $userStructures->armory_level ?>">
                Level <?= $userStructures->armory_level ?>
            </strong>
        </div>
        
        <div class="header-stat">
            <span>Charisma Bonus</span>
            <strong class="accent-green">
                <?= number_format($discountPercent * 100, 1) ?>% Discount
            </strong>
        </div>
    </div>

    <div class="tabs-nav">
        <?php $i = 0; ?>
        <?php foreach ($armoryConfig as $unitKey => $unitData): ?>
            <a class="tab-link <?= $i === 0 ? 'active' : '' ?>" data-tab="tab-<?= $unitKey ?>">
                <?= htmlspecialchars($unitData['title']) ?>
            </a>
            <?php $i++; ?>
        <?php endforeach; ?>
    </div>

    <?php $i = 0; ?>
    <?php foreach ($armoryConfig as $unitKey => $unitData): ?>
        <?php 
            $unitResourceKey = $unitData['unit']; 
            $unitCount = $userResources->{$unitResourceKey} ?? 0;
        ?>
        <div id="tab-<?= $unitKey ?>" class="tab-content <?= $i === 0 ? 'active' : '' ?>" data-unit-key="<?= $unitKey ?>" data-unit-count="<?= $unitCount ?>">
            
            <!-- Split Grid: Loadout (Left) | Manufacturing (Right) -->
            <div class="split-grid">
                
                <!-- Left Column: Loadout Management -->
                <div class="data-card">
                    <h3>Current Loadout (<?= number_format($unitCount) ?> <?= htmlspecialchars(ucfirst($unitResourceKey)) ?>)</h3>
                    
                    <form action="/armory/equip" method="POST" class="equip-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                        <input type="hidden" name="unit_key" value="<?= $unitKey ?>">
                        
                        <?php foreach ($unitData['categories'] as $categoryKey => $categoryData): ?>
                            <div class="form-group">
                                <label for="equip-<?= $unitKey ?>-<?= $categoryKey ?>">
                                    <?= htmlspecialchars($categoryData['title']) ?>
                                </label>
                                <select class="equip-select" 
                                        data-category-key="<?= $categoryKey ?>">
                                    <option value="">-- None Equipped --</option>
                                    <?php 
                                        $currentlyEquipped = $loadouts[$unitKey][$categoryKey] ?? null;
                                        foreach ($categoryData['items'] as $itemKey => $item): 
                                            $owned = (int)($inventory[$itemKey] ?? 0);
                                    ?>
                                        <option value="<?= $itemKey ?>" <?= $currentlyEquipped === $itemKey ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($item['name']) ?> (Owned: <?= number_format($owned) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                        
                        <input type="hidden" name="category_key" class="dynamic-category-key" value="">
                        <input type="hidden" name="item_key" class="dynamic-item-key" value="">
                    </form>
                </div>

                <!-- Right Column: Manufacturing Items -->
                <div>
                    <!-- 3 Column Grid for Items -->
                    <div class="item-grid grid-3">
                        <?php foreach ($unitData['categories'] as $categoryKey => $categoryData): ?>
                            
                            <!-- Section Header spans full width of grid -->
                            <h2 class="section-header"><?= htmlspecialchars($categoryData['title']) ?></h2>

                            <?php foreach ($categoryData['items'] as $itemKey => $item): ?>
                                <?php
                                $isTier1 = !isset($item['requires']);
                                $prereqKey = $item['requires'] ?? null;
                                $prereqName = $prereqKey ? ($itemLookup[$prereqKey] ?? 'N/A') : 'N/A';
                                $prereqOwned = (int)($inventory[$prereqKey] ?? 0);
                                $currentOwned = (int)($inventory[$itemKey] ?? 0);
                                $armoryLvlReq = $item['armory_level_req'] ?? 0;
                                $baseCost = $item['cost'];
                                
                                $effectiveCost = (int)floor($baseCost * (1 - $discountPercent));
                                
                                $hasLvl = $userStructures->armory_level >= $armoryLvlReq;
                                $canManufacture = $hasLvl && ($isTier1 || $prereqOwned > 0);
                                ?>
                                <div class="item-card">
                                    <h4><?= htmlspecialchars($item['name']) ?></h4>
                                    <p class="item-notes"><?= htmlspecialchars($item['notes']) ?></p>
                                    
                                    <div class="item-stats">
                                        <?php if (isset($item['attack'])): ?>
                                            <span class="stat-pill attack">+<?= $item['attack'] ?> Attack</span>
                                        <?php endif; ?>
                                        <?php if (isset($item['defense'])): ?>
                                            <span class="stat-pill defense">+<?= $item['defense'] ?> Defense</span>
                                        <?php endif; ?>
                                        <?php if (isset($item['credit_bonus'])): ?>
                                            <span class="stat-pill defense">+<?= $item['credit_bonus'] ?> Credits</span>
                                        <?php endif; ?>
                                    </div>

                                    <ul class="item-info">
                                        <li>
                                            <span>Cost:</span> 
                                            <div>
                                                <?php if ($hasDiscount): ?>
                                                    <span class="cost-original"><?= number_format($baseCost) ?></span>
                                                    <strong class="cost-discounted"><?= number_format($effectiveCost) ?></strong>
                                                <?php else: ?>
                                                    <strong><?= number_format($baseCost) ?></strong>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                        <li>
                                            <span>Lvl Req:</span>
                                            <strong class="<?= $hasLvl ? 'status-ok' : 'status-bad' ?>">
                                                <?= $armoryLvlReq ?>
                                            </strong>
                                        </li>
                                        <?php if (!$isTier1): ?>
                                            <li>
                                                <span>Requires:</span>
                                                <strong><?= htmlspecialchars($prereqName) ?></strong>
                                            </li>
                                            <li>
                                                <span>Req. Owned:</span>
                                                <strong data-inventory-key="<?= $prereqKey ?>"><?= number_format($prereqOwned) ?></strong>
                                            </li>
                                        <?php endif; ?>
                                        <li><span>Owned:</span> <strong data-inventory-key="<?= $itemKey ?>"><?= number_format($currentOwned) ?></strong></li>
                                    </ul>

                                    <form action="/armory/manufacture" method="POST" class="manufacture-form">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                        <input type="hidden" name="item_key" value="<?= $itemKey ?>">
                                        
                                        <div class="form-group">
                                            <div class="amount-input-group">
                                                <input type="number" name="quantity" class="manufacture-amount" min="1" placeholder="Qty" required 
                                                    data-item-key="<?= $itemKey ?>"
                                                    data-item-cost="<?= $effectiveCost ?>" 
                                                    data-prereq-key="<?= $prereqKey ?>"
                                                    data-current-owned="<?= $currentOwned ?>"
                                                >
                                                <button type="button" class="btn-submit btn-accent btn-max-manufacture">Max</button>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn-submit btn-manufacture-submit" <?= !$canManufacture ? 'disabled' : '' ?>>
                                            <?= $isTier1 ? 'Manufacture' : 'Upgrade' ?>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php $i++; ?>
    <?php endforeach; ?>
</div>

<script src="/js/armory.js"></script>