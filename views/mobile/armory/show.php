<?php
// --- Mobile Armory Lobby View ---
/* @var \App\Models\Entities\UserResource $userResources */
/* @var array $armoryConfig */
/* @var array $manufacturingData */

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
        <h1 class="glitch-text" data-text="Armory" style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">Armory</h1>
        <p style="color: var(--mobile-text-secondary); font-size: 0.9rem; margin: 0.5rem 1rem 0 1rem; text-align: center;">Select a unit type to view available tiers.</p>
    </div>

    <!-- Unit List -->
    <div class="unit-lobby-container">
        <?php foreach ($armoryConfig as $unitKey => $unitData): 
            $tiers = $manufacturingData[$unitKey] ?? [];
            $icon = $unitIcons[$unitKey] ?? 'fa-cube';
        ?>
            <div class="mobile-card mb-3 unit-card">
                <div class="mobile-card-header" onclick="this.closest('.unit-card').classList.toggle('expanded')">
                    <h3 class="flex-center-y">
                        <i class="fas <?= $icon ?> fa-fw mr-2" style="color: var(--mobile-accent-primary);"></i>
                        <?= htmlspecialchars($unitData['title']) ?>
                    </h3>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </div>
                
                <div class="unit-tiers-grid">
                    <!-- Loadout Manager Button -->
                    <a href="/armory/mobile/loadout/<?= $unitKey ?>" class="tier-btn" style="background: rgba(var(--mobile-accent-primary-rgb), 0.15); border-color: var(--mobile-accent-primary);">
                        <span class="tier-num"><i class="fas fa-user-cog"></i></span>
                        <span class="item-count" style="color: #fff;">Loadout</span>
                    </a>

                    <?php foreach ($tiers as $tier => $items): if (empty($items)) continue; ?>
                        <a href="/armory/mobile/manage/<?= $unitKey ?>/<?= $tier ?>" class="tier-btn">
                            <span class="tier-num">Tier <?= $tier ?></span>
                            <span class="item-count"><?= count($items) ?> Items</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.unit-card .mobile-card-header {
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.unit-tiers-grid {
    display: none;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    padding: 10px;
    background: rgba(0,0,0,0.2);
}
.unit-card.expanded .unit-tiers-grid {
    display: grid;
}
.unit-card.expanded .toggle-icon {
    transform: rotate(180deg);
}

.tier-btn {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--mobile-border-color);
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    text-decoration: none;
    color: var(--mobile-text-primary);
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.tier-btn:active {
    background: var(--mobile-accent-primary);
    color: #fff;
}
.tier-num { font-family: 'Orbitron', sans-serif; font-size: 1.1rem; }
.item-count { font-size: 0.8rem; color: var(--mobile-text-secondary); }
.tier-btn:active .item-count { color: rgba(255,255,255,0.8); }

.flex-center-y { display: flex; align-items: center; }
.mr-2 { margin-right: 0.5rem; }
</style>
