<?php
// --- Mobile Armory View ---
/* @var \App\Models\Entities\UserResource $userResources */
/* @var array $armoryConfig */
/* @var array $manufacturingData */
/* @var array $loadouts */
/* @var string $csrf_token */

// Use the live config to drive the UI, not a hardcoded list
$unitIcons = [
    'soldier' => 'fa-user-ninja',
    'guard' => 'fa-user-shield',
    'spy' => 'fa-mask',
    'sentry' => 'fa-satellite-dish',
    'worker' => 'fa-helmet-safety'
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
                <li><span><i class="fas fa-atom"></i> Dark Matter</span> <strong><?= number_format($userResources->dark_matter) ?></strong></li>
            </ul>
        </div>
    </div>

    <!-- MAIN WRAPPER FOR UNIT TABS -->
    <div class="tabs-container">
        
        <!-- Unit Tab Navigation -->
        <div class="mobile-tabs tabs-nav" style="justify-content: center; margin-bottom: 1.5rem;">
            <?php $isFirstUnit = true; foreach (array_keys($armoryConfig) as $unitKey): ?>
                <a href="#" class="tab-link <?= $isFirstUnit ? 'active' : '' ?>" data-tab="tab-<?= $unitKey ?>">
                    <i class="fas <?= $unitIcons[$unitKey] ?? 'fa-question-circle' ?>"></i> <?= htmlspecialchars($armoryConfig[$unitKey]['title']) ?>
                </a>
            <?php $isFirstUnit = false; endforeach; ?>
        </div>

        <!-- Unit Tab Content Panels -->
        <?php $isFirstUnit = true; foreach (array_keys($armoryConfig) as $unitKey): 
            $tiers = $manufacturingData[$unitKey] ?? [];
            $currentLoadout = $loadouts[$unitKey] ?? [];
        ?>
        <div id="tab-<?= $unitKey ?>" class="tab-content <?= $isFirstUnit ? 'active' : '' ?>">
            
            <!-- TIER TABS -->
            <div class="tier-tabs-wrapper">
                
                <!-- Tier Tab Navigation -->
                <div class="mobile-tabs tabs-nav" style="justify-content: flex-start; overflow-x: auto; flex-wrap: nowrap; border-bottom: none; margin-bottom: 1rem; padding-bottom: 0.5rem;">
                    <?php 
                    $isFirstTier = true; 
                    foreach ($tiers as $tier => $items): 
                        if (empty($items)) continue;
                        $tabId = "tab-{$unitKey}-tier-{$tier}";
                    ?>
                        <a href="#" class="tab-link <?= $isFirstTier ? 'active' : '' ?>" data-tab="<?= $tabId ?>" style="white-space: nowrap; font-size: 0.9rem; padding: 0.5rem 1rem;">
                            Tier <?= $tier ?>
                        </a>
                    <?php 
                    $isFirstTier = false; 
                    endforeach; 
                    ?>
                </div>

                <!-- Tier Tab Content Panels -->
                <?php 
                $isFirstTier = true;
                foreach ($tiers as $tier => $items): 
                    if (empty($items)) continue;
                    $tabId = "tab-{$unitKey}-tier-{$tier}";
                ?>
                <div id="<?= $tabId ?>" class="tab-content <?= $isFirstTier ? 'active' : '' ?>">
                    <?php foreach ($items as $item): 
                        $isEquipped = in_array($item['item_key'], $currentLoadout);

                        // Calculate affordability and limits
                        $can_afford_credits = ($userResources->credits >= $item['effective_cost']);
                        $can_afford_crystals = ($userResources->naquadah_crystals >= $item['cost_crystals']);
                        $can_afford_dark_matter = ($userResources->dark_matter >= $item['cost_dark_matter']);
                        $can_afford_prereq = ($item['is_tier_1'] || ($item['prereq_owned'] > 0));
                        
                        $maxByCredits = ($item['effective_cost'] > 0) ? floor($userResources->credits / $item['effective_cost']) : PHP_INT_MAX;
                        $maxByCrystals = ($item['cost_crystals'] > 0) ? floor($userResources->naquadah_crystals / $item['cost_crystals']) : PHP_INT_MAX;
                        $maxByDarkMatter = ($item['cost_dark_matter'] > 0) ? floor($userResources->dark_matter / $item['cost_dark_matter']) : PHP_INT_MAX;
                        $maxByPrereq = ($item['is_tier_1']) ? PHP_INT_MAX : $item['prereq_owned'];
                        $max_buildable = (int)min($maxByCredits, $maxByPrereq, $maxByCrystals, $maxByDarkMatter);

                        $cannot_afford_reason = '';
                        if (!$can_afford_credits) $cannot_afford_reason = 'Need Credits';
                        elseif (!$can_afford_crystals) $cannot_afford_reason = 'Need Crystals';
                        elseif (!$can_afford_dark_matter) $cannot_afford_reason = 'Need Dark Matter';
                        elseif (!$can_afford_prereq) $cannot_afford_reason = 'Need Prerequisite';
                    ?>
                    <div class="mobile-card" style="<?= $isEquipped ? 'border-color: var(--mobile-accent-green);' : '' ?>">
                        <div class="mobile-card-header">
                            <h3><?= htmlspecialchars($item['name']) ?></h3>
                            <span class="structure-level" style="color: var(--mobile-text-primary);">Owned: <?= number_format($item['current_owned']) ?></span>
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
                            <form action="/armory/manufacture" method="POST" style="margin-bottom: 1rem;" class="manufacture-form">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <input type="hidden" name="item_key" value="<?= htmlspecialchars($item['item_key']) ?>">
                                
                                <div class="item-card"> <!-- Wrapper for JS name finding -->
                                    <h4 style="display:none;"><?= htmlspecialchars($item['name']) ?></h4>
                                    <div style="display: flex; flex-direction: column; gap: 0.25rem; margin-bottom: 0.75rem; font-size: 0.9rem;">
                                        <div style="color: var(--mobile-accent-yellow); display: flex; justify-content: space-between;">
                                            <span><i class="fas fa-coins fa-fw"></i> Credits:</span>
                                            <strong><?= number_format($item['effective_cost']) ?></strong>
                                        </div>
                                        <?php if ($item['cost_crystals'] > 0): ?>
                                        <div style="color: var(--mobile-accent-cyan); display: flex; justify-content: space-between;">
                                            <span><i class="fas fa-gem fa-fw"></i> Crystals:</span>
                                            <strong><?= $item['cost_crystals_formatted'] ?></strong>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($item['cost_dark_matter'] > 0): ?>
                                        <div style="color: var(--mobile-accent-purple); display: flex; justify-content: space-between;">
                                            <span><i class="fas fa-atom fa-fw"></i> Dark Matter:</span>
                                            <strong><?= $item['cost_dark_matter_formatted'] ?></strong>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!$item['is_tier_1']): ?>
                                        <div style="color: var(--muted); font-size: 0.8rem; display: flex; justify-content: space-between; border-top: 1px solid var(--mobile-border-color); padding-top: 0.25rem; margin-top: 0.25rem;">
                                            <span>Req: 1x <?= htmlspecialchars($item['prereq_name'] ?? 'Item') ?></span>
                                            <span>(Owned: <?= number_format($item['prereq_owned']) ?>)</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if (empty($cannot_afford_reason)): ?>
                                    <div class="form-group">
                                        <div class="amount-input-group">
                                            <input type="number" name="quantity" class="mobile-input manufacture-amount" placeholder="0" min="1" max="<?= $max_buildable ?>" style="width: 100%; margin-bottom: 0.5rem;"
                                                data-item-cost="<?= $item['effective_cost'] ?>"
                                            >
                                            <button type="button" class="btn btn-sm btn-max-manufacture" style="width: 100%;" 
                                                data-item-cost="<?= $item['effective_cost'] ?>"
                                                data-req-owned="<?= $maxByPrereq ?>"
                                            >MAX (<?= number_format($max_buildable) ?>)</button>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-manufacture-submit" style="width: 100%; margin-top: 0.5rem;">
                                        Manufacture
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn" disabled style="width: 100%;">
                                        <?= htmlspecialchars($cannot_afford_reason) ?>
                                    </button>
                                <?php endif; ?>
                            </form>

                            <!-- Equip Button -->
                            <?php if (!$isEquipped && $item['current_owned'] > 0): ?>
                                <form action="/armory/equip" method="POST" class="equip-form">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <input type="hidden" name="unit_key" value="<?= htmlspecialchars($unitKey) ?>">
                                    <input type="hidden" name="category_key" class="dynamic-category-key" value="<?= htmlspecialchars($item['category_key']) ?>">
                                    <input type="hidden" name="item_key" class="dynamic-item-key" value="<?= htmlspecialchars($item['item_key']) ?>">
                                    <!-- Use select for JS logic or just a button -->
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
            </div> <!-- End Tier Wrapper -->

        </div>
        <?php $isFirstUnit = false; endforeach; ?>
    </div> <!-- End Main Unit Container -->
</div>

<!-- Add the global credit indicator for JS -->
<div id="global-user-credits" style="display:none;"><?= $userResources->credits ?></div>

<script src="/js/armory.js"></script>
<style>
/* Ensure tab content behavior matches armory.js expectation */
.tab-content { display: none; }
.tab-content.active { display: block; }
.tab-link { cursor: pointer; }
.tab-link.active { border-bottom: 2px solid var(--mobile-accent-primary); color: var(--mobile-accent-primary); }
</style>
