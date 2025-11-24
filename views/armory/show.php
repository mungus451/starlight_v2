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
            // Use the Service-prepared data
            $tieredItems = $manufacturingData[$unitKey] ?? [];
        ?>
        <div id="tab-<?= $unitKey ?>" class="tab-content <?= $i === 0 ? 'active' : '' ?>" data-unit-key="<?= $unitKey ?>" data-unit-count="<?= $unitCount ?>">
            
            <div class="split-grid">
                
                <!-- Left Column: Loadout Management -->
                <div class="data-card sticky-sidebar">
                    <h3 class="text-accent">Loadout</h3>
                    <p class="form-note mb-1">
                        Active Units: <strong><?= number_format($unitCount) ?> <?= htmlspecialchars(ucfirst($unitResourceKey)) ?></strong>
                    </p>
                    
                    <form action="/armory/equip" method="POST" class="equip-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                        <input type="hidden" name="unit_key" value="<?= $unitKey ?>">
                        
                        <?php foreach ($unitData['categories'] as $categoryKey => $categoryData): ?>
                            <div class="form-group">
                                <label for="equip-<?= $unitKey ?>-<?= $categoryKey ?>">
                                    <?= htmlspecialchars($categoryData['title']) ?>
                                </label>
                                <select class="equip-select w-full p-05 text-sm" 
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

                <!-- Right Column: Manufacturing (Logic-Free View) -->
                <div>
                    <?php foreach ($tieredItems as $tier => $items): ?>
                        <details class="tier-accordion" <?= $tier === 1 ? 'open' : '' ?>>
                            <summary class="tier-summary">
                                Tier <?= $tier ?> Equipment
                            </summary>
                            
                            <div class="tier-content">
                                <div class="item-grid grid-3">
                                    <?php foreach ($items as $item): ?>
                                        <div class="item-card">
                                            <div class="flex-between mb-05">
                                                <h4 class="m-0 border-none font-1-1"><?= htmlspecialchars($item['name']) ?></h4>
                                                <span class="data-badge text-uppercase bg-glass text-muted border-glass font-07">
                                                    <?= htmlspecialchars($item['slot_name']) ?>
                                                </span>
                                            </div>
                                            
                                            <p class="item-notes mb-075"><?= htmlspecialchars($item['notes']) ?></p>
                                            
                                            <div class="item-stats">
                                                <?php foreach ($item['stat_badges'] as $badge): ?>
                                                    <span class="stat-pill <?= $badge['type'] ?>"><?= $badge['label'] ?></span>
                                                <?php endforeach; ?>
                                            </div>

                                            <ul class="item-info font-085">
                                                <li>
                                                    <span>Cost:</span> 
                                                    <div>
                                                        <?php if ($hasDiscount): ?>
                                                            <span class="cost-original"><?= number_format($item['base_cost']) ?></span>
                                                            <strong class="cost-discounted"><?= number_format($item['effective_cost']) ?></strong>
                                                        <?php else: ?>
                                                            <strong><?= number_format($item['base_cost']) ?></strong>
                                                        <?php endif; ?>
                                                    </div>
                                                </li>
                                                <?php if ($item['armory_level_req'] > 0): ?>
                                                    <li>
                                                        <span>Armory:</span>
                                                        <strong class="<?= $item['level_status_class'] ?>">
                                                            Lvl <?= $item['armory_level_req'] ?>
                                                        </strong>
                                                    </li>
                                                <?php endif; ?>
                                                
                                                <?php if (!$item['is_tier_1']): ?>
                                                    <li>
                                                        <span>Req:</span>
                                                        <strong><?= htmlspecialchars($item['prereq_name']) ?></strong>
                                                    </li>
                                                    <li>
                                                        <span>Req Owned:</span>
                                                        <strong data-inventory-key="<?= $item['prereq_key'] ?>"><?= number_format($item['prereq_owned']) ?></strong>
                                                    </li>
                                                <?php endif; ?>
                                                
                                                <li class="border-top pt-05 mt-025">
                                                    <span>Owned:</span> 
                                                    <strong class="accent font-1-1" data-inventory-key="<?= $item['item_key'] ?>">
                                                        <?= number_format($item['current_owned']) ?>
                                                    </strong>
                                                </li>
                                            </ul>

                                            <form action="/armory/manufacture" method="POST" class="manufacture-form mt-auto">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                                <input type="hidden" name="item_key" value="<?= $item['item_key'] ?>">
                                                
                                                <div class="form-group">
                                                    <div class="amount-input-group">
                                                        <input type="number" name="quantity" class="manufacture-amount" min="1" placeholder="Qty" required 
                                                            data-item-key="<?= $item['item_key'] ?>"
                                                            data-item-cost="<?= $item['effective_cost'] ?>" 
                                                            data-prereq-key="<?= $item['prereq_key'] ?? '' ?>"
                                                            data-current-owned="<?= $item['current_owned'] ?>"
                                                        >
                                                        <button type="button" class="btn-submit btn-accent btn-max-manufacture p-05">Max</button>
                                                    </div>
                                                </div>
                                                
                                                <button type="submit" class="btn-submit btn-manufacture-submit" <?= !$item['can_manufacture'] ? 'disabled' : '' ?>>
                                                    <?= $item['manufacture_btn_text'] ?>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php $i++; ?>
    <?php endforeach; ?>
</div>

<script src="/js/armory.js"></script>