<?php
// --- Mobile Armory View ---
/* @var \App\Models\Entities\UserResource $userResources */
/* @var array $manufacturingData */
/* @var array $loadouts */
/* @var string $csrf_token */

$unitTypes = [
    'soldier' => ['name' => 'Soldier Gear', 'icon' => 'fa-user-ninja'],
    'guard' => ['name' => 'Guard Gear', 'icon' => 'fa-user-shield'],
    'spy' => ['name' => 'Spy Gear', 'icon' => 'fa-mask'],
    'sentry' => ['name' => 'Sentry Tech', 'icon' => 'fa-satellite-dish']
];
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">Armory</h1>
        <p style="color: var(--mobile-text-secondary); font-size: 0.9rem; margin: 0.5rem 1rem 0 1rem; text-align: center;">Manufacture and equip gear for your units.</p>
    </div>

    <!-- Resource Overview -->
    <div class="mobile-card resource-overview-card">
        <div class="mobile-card-header">
            <h3><i class="fas fa-wallet"></i> Resources</h3>
        </div>
        <div class="mobile-card-content" style="display: block;">
            <ul class="mobile-stats-list">
                <li><span><i class="fas fa-coins"></i> Credits</span> <strong><?= number_format($userResources->credits) ?></strong></li>
                <li><span><i class="fas fa-gem"></i> Crystals</span> <strong><?= number_format($userResources->naquadah_crystals) ?></strong></li>
            </ul>
        </div>
    </div>

    <!-- MAIN WRAPPER FOR UNIT TABS -->
    <div class="nested-tabs-container">
        
        <!-- Unit Tab Navigation -->
        <div class="mobile-tabs nested-tabs">
            <?php $isFirstUnit = true; foreach ($unitTypes as $key => $meta): ?>
                <a href="#" class="tab-link <?= $isFirstUnit ? 'active' : '' ?>" data-tab-target="tab-<?= $key ?>">
                    <i class="fas <?= $meta['icon'] ?>"></i> <?= $meta['name'] ?>
                </a>
            <?php $isFirstUnit = false; endforeach; ?>
        </div>

        <!-- Unit Tab Content -->
        <div id="tab-content">
            <?php $isFirstUnit = true; foreach ($unitTypes as $unitKey => $meta): 
                $tiers = $manufacturingData[$unitKey] ?? [];
                $currentLoadout = $loadouts[$unitKey] ?? [];
            ?>
            <div id="tab-<?= $unitKey ?>" class="nested-tab-content <?= $isFirstUnit ? 'active' : '' ?>">
                
                <!-- NESTED WRAPPER FOR TIER TABS -->
                <div class="nested-tabs-container" style="background: none; padding: 0; margin-top: 0;">
                    
                    <!-- Tier Tab Navigation -->
                    <div class="mobile-tabs nested-tabs" style="justify-content: flex-start; overflow-x: auto; flex-wrap: nowrap; border-bottom: none; margin-bottom: 1rem; padding-bottom: 0.5rem;">
                        <?php 
                        $isFirstTier = true; 
                        foreach ($tiers as $tier => $items): 
                            $tabId = "tab-{$unitKey}-t{$tier}";
                        ?>
                            <a href="#" class="tab-link <?= $isFirstTier ? 'active' : '' ?>" data-tab-target="<?= $tabId ?>" style="white-space: nowrap; font-size: 0.9rem; padding: 0.5rem 1rem;">
                                Tier <?= $tier ?>
                            </a>
                        <?php 
                        $isFirstTier = false; 
                        endforeach; 
                        ?>
                    </div>

                    <!-- Tier Tab Content -->
                    <div id="tier-content-<?= $unitKey ?>">
                        <?php 
                        $isFirstTier = true;
                        foreach ($tiers as $tier => $items): 
                            $tabId = "tab-{$unitKey}-t{$tier}";
                        ?>
                        <div id="<?= $tabId ?>" class="nested-tab-content <?= $isFirstTier ? 'active' : '' ?>">
                            <?php foreach ($items as $item): 
                                $isEquipped = false;
                                foreach ($currentLoadout as $slot => $equippedKey) {
                                    if ($equippedKey === $item['item_key']) $isEquipped = true;
                                }

                                // Calculate affordability and limits directly in View
                                $can_afford_cost = ($userResources->credits >= $item['effective_cost']);
                                $can_afford_prereq = ($item['is_tier_1'] || ($item['prereq_owned'] > 0));
                                
                                $maxByCredits = ($item['effective_cost'] > 0) ? floor($userResources->credits / $item['effective_cost']) : 999999;
                                $maxByPrereq = ($item['is_tier_1']) ? 999999 : $item['prereq_owned'];
                                $max_buildable = min($maxByCredits, $maxByPrereq);
                            ?>
                            <div class="mobile-card" style="<?= $isEquipped ? 'border-color: var(--mobile-accent-green);' : '' ?>">
                                <div class="mobile-card-header">
                                    <h3><?= htmlspecialchars($item['name']) ?></h3>
                                    <span class="structure-level" style="color: var(--mobile-text-primary);">Owned: <?= $item['current_owned_formatted'] ?></span>
                                </div>
                                <div class="mobile-card-content" style="display: block;">
                                    <div class="item-stats" style="margin-bottom: 0.5rem;">
                                        <?php foreach ($item['stat_badges'] as $badge): ?>
                                            <span class="stat-pill <?= $badge['type'] ?>"><?= $badge['label'] ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <?php if ($isEquipped): ?>
                                        <div class="max-level-notice" style="margin-bottom: 1rem; padding: 0.5rem; font-size: 0.9rem;">
                                            <i class="fas fa-check"></i> Equipped
                                        </div>
                                    <?php endif; ?>

                                    <!-- Manufacture Form -->
                                    <form action="/armory/manufacture" method="POST" style="margin-bottom: 1rem;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <input type="hidden" name="item_key" value="<?= htmlspecialchars($item['item_key']) ?>">
                                        
                                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.5rem;">
                                            <div style="font-size: 0.9rem; color: var(--mobile-accent-yellow);">
                                                <?= $item['effective_cost_formatted'] ?> <i class="fas fa-coins"></i>
                                            </div>
                                                                                <?php if (!$item['is_tier_1']): ?>
                                                                                    <div style="font-size: 0.8rem; color: var(--muted);">
                                                                                        Req: 1x <?= htmlspecialchars($item['prereq_name'] ?? 'Previous Tier') ?> (Owned: <?= $item['prereq_owned_formatted'] ?>)
                                                                                    </div>
                                                                                <?php endif; ?>                                        </div>

                                        <?php if ($can_afford_cost && $can_afford_prereq): ?>
                                            <div class="form-group">
                                                <label>Quantity</label>
                                                <input type="number" name="quantity" class="mobile-input" placeholder="0" min="1" max="<?= $max_buildable ?>" style="width: 100%; margin-bottom: 0.5rem;">
                                                <button type="button" class="btn btn-sm" style="width: 100%;" onclick="this.previousElementSibling.value = '<?= $max_buildable ?>'">MAX (<?= number_format($max_buildable) ?>)</button>
                                            </div>
                                            <button type="submit" class="btn" style="width: 100%; margin-top: 0.5rem;">
                                                <?= $item['manufacture_btn_text'] ?>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn" disabled style="width: 100%;">
                                                <?= !$can_afford_cost ? 'Need Credits' : 'Need Prerequisite' ?>
                                            </button>
                                        <?php endif; ?>
                                    </form>

                                    <!-- Equip Button -->
                                    <?php if (!$isEquipped && $item['current_owned'] > 0): ?>
                                        <form action="/armory/equip" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                            <input type="hidden" name="unit_key" value="<?= htmlspecialchars($unitKey) ?>">
                                            <input type="hidden" name="category_key" value="<?= htmlspecialchars($item['category']) ?>">
                                            <input type="hidden" name="item_key" value="<?= htmlspecialchars($item['item_key']) ?>">
                                            <button type="submit" class="btn btn-outline" style="width: 100%;">
                                                Equip
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php 
                        $isFirstTier = false; 
                        endforeach; 
                        ?>
                    </div>
                </div> <!-- End Tier Nested Container -->

            </div>
            <?php $isFirstUnit = false; endforeach; ?>
        </div>
    </div> <!-- End Main Unit Container -->
</div>
