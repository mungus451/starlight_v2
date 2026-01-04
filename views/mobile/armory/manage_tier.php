<?php
// --- Mobile Armory Tier Management ---
// Patterned after Training/Show.php
/* @var string $unitName */
/* @var int $tier */
/* @var array $items */
/* @var \App\Models\Entities\UserResource $userResources */
/* @var string $csrf_token */
/* @var array $loadouts */
?>

<div class="mobile-content">
    <!-- Header -->
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <a href="/armory" style="color: var(--mobile-text-secondary); font-size: 1.2rem;"><i class="fas fa-arrow-left"></i></a>
            <h1 style="font-family: 'Orbitron', sans-serif; font-size: 1.5rem; margin: 0;"><?= htmlspecialchars($unitName) ?> T<?= $tier ?></h1>
            <div style="width: 20px;"></div>
        </div>
    </div>

    <!-- Resource Overview (Standard Card) -->
    <div class="mobile-card resource-overview-card">
        <div class="mobile-card-header">
            <h3><i class="fas fa-wallet"></i> Resources</h3>
        </div>
        <div class="mobile-card-content" style="display: block;">
            <ul class="mobile-stats-list">
                <li><span>Credits</span> <strong><?= number_format($userResources->credits) ?></strong></li>
                <li><span>Crystals</span> <strong><?= number_format($userResources->naquadah_crystals) ?></strong></li>
                <li><span>Dark Matter</span> <strong><?= number_format($userResources->dark_matter) ?></strong></li>
            </ul>
        </div>
    </div>

    <!-- Items Loop -->
    <?php if (empty($items)): ?>
        <div class="mobile-card">
            <div class="mobile-card-content">
                <p style="text-align: center; color: var(--mobile-text-secondary);">No items found in this tier.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($items as $item): 
            // Logic Setup
            $isEquipped = in_array($item['item_key'], $loadouts);
            
            $costCred = $item['effective_cost'] ?? 0;
            $costCrys = $item['cost_crystals'] ?? 0;
            $costDM = $item['cost_dark_matter'] ?? 0;
            
            // Calc Max
            $maxCred = ($costCred > 0) ? floor($userResources->credits / $costCred) : 999999;
            $maxCrys = ($costCrys > 0) ? floor($userResources->naquadah_crystals / $costCrys) : 999999;
            $maxDM = ($costDM > 0) ? floor($userResources->dark_matter / $costDM) : 999999;
            
            // Prereqs
            $prereqOwned = $item['prereq_owned'] ?? 0;
            $maxPrereq = ($item['is_tier_1']) ? 999999 : $prereqOwned;
            
            $maxTrainable = min($maxCred, $maxCrys, $maxDM, $maxPrereq);
            $isDisabled = ($maxTrainable <= 0);
        ?>
        <div class="mobile-card" style="<?= $isEquipped ? 'border: 1px solid var(--mobile-accent-green);' : '' ?>">
            <div class="mobile-card-header">
                <h3><?= htmlspecialchars($item['name']) ?></h3>
                <span style="color: var(--mobile-text-secondary);">Owned: <?= number_format($item['current_owned']) ?></span>
            </div>
            
            <div class="mobile-card-content" style="display: block;">
                <p style="font-size: 0.9rem; color: #ccc; margin-bottom: 0.5rem;"><?= htmlspecialchars($item['notes']) ?></p>
                
                <!-- Stats -->
                <div style="margin-bottom: 1rem;">
                    <?php foreach ($item['stat_badges'] as $badge): ?>
                        <span class="stat-pill <?= $badge['type'] ?>"><?= $badge['label'] ?></span>
                    <?php endforeach; ?>
                    <?php if ($isEquipped): ?>
                        <span class="stat-pill success"><i class="fas fa-check"></i> Equipped</span>
                    <?php endif; ?>
                </div>

                <!-- Costs -->
                <ul class="structure-costs" style="margin-bottom: 1rem;">
                    <li><i class="fas fa-coins"></i> <?= number_format($costCred) ?> Credits</li>
                    <?php if ($costCrys > 0): ?><li><i class="fas fa-gem"></i> <?= number_format($costCrys) ?> Crys</li><?php endif; ?>
                    <?php if ($costDM > 0): ?><li><i class="fas fa-atom"></i> <?= number_format($costDM) ?> DM</li><?php endif; ?>
                    <?php if (!$item['is_tier_1']): ?>
                        <li style="color: <?= $prereqOwned > 0 ? '#aaa' : '#ff4444' ?>">Req: <?= htmlspecialchars($item['prereq_name']) ?> (<?= $prereqOwned ?>)</li>
                    <?php endif; ?>
                </ul>

                <!-- Manufacture Form -->
                <form action="/armory/manufacture" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="item_key" value="<?= htmlspecialchars($item['item_key']) ?>">
                    
                    <?php if (!$isDisabled): ?>
                        <div class="form-group">
                            <input type="number" name="quantity" class="mobile-input" placeholder="Qty" min="1" max="<?= $maxTrainable ?>" style="width: 70%; display: inline-block;">
                            <button type="button" class="btn btn-sm" style="width: 28%; display: inline-block;" onclick="this.previousElementSibling.value = <?= $maxTrainable ?>">MAX</button>
                        </div>
                        <button type="submit" class="btn" style="width: 100%; margin-top: 0.5rem;">
                            <?= $item['is_tier_1'] ? 'Manufacture' : 'Upgrade' ?>
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn" disabled style="width: 100%;">
                            Insufficient Resources
                        </button>
                    <?php endif; ?>
                </form>

                <!-- Equip Form -->
                <?php if (!$isEquipped && $item['current_owned'] > 0): ?>
                    <form action="/armory/equip" method="POST" style="margin-top: 0.5rem;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <input type="hidden" name="unit_key" value="<?= htmlspecialchars($unitKey ?? 'soldier') ?>">
                        <input type="hidden" name="category_key" value="<?= htmlspecialchars($item['category_key']) ?>">
                        <input type="hidden" name="item_key" value="<?= htmlspecialchars($item['item_key']) ?>">
                        <button type="submit" class="btn btn-outline" style="width: 100%;">Equip</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>