<?php
// --- Mobile General Armory View ---
/* @var array $general */
/* @var array $elite_weapons */
/* @var \App\Models\Entities\UserResource $resources */
/* @var string $csrf_token */
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 1.8rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">Elite Armory</h1>
        <p style="color: var(--mobile-text-secondary); font-size: 0.9rem; margin: 0.5rem 1rem 0 1rem; text-align: center;">Outfitting: <strong style="color: #fff;"><?= htmlspecialchars($general['name']) ?></strong></p>
    </div>

    <!-- Resource Overview -->
    <div class="mobile-card resource-overview-card">
        <div class="mobile-card-header">
            <h3><i class="fas fa-wallet"></i> Resources</h3>
        </div>
        <div class="mobile-card-content" style="display: block;">
            <ul class="mobile-stats-list">
                <li><span><i class="fas fa-coins"></i> Credits</span> <strong><?= number_format($resources->credits) ?></strong></li>
                <li><span><i class="fas fa-gem"></i> Crystals</span> <strong><?= number_format($resources->naquadah_crystals) ?></strong></li>
                <li><span><i class="fas fa-atom"></i> Dark Matter</span> <strong><?= number_format($resources->dark_matter) ?></strong></li>
                <li><span><i class="fas fa-cube"></i> Protoform</span> <strong><?= number_format($resources->protoform ?? 0) ?></strong></li>
            </ul>
        </div>
    </div>

    <?php foreach ($elite_weapons as $key => $weapon): 
        $isEquipped = ($general['weapon_slot_1'] === $key);
        $canAfford = (
            $resources->credits >= ($weapon['cost']['credits'] ?? 0) &&
            $resources->naquadah_crystals >= ($weapon['cost']['naquadah_crystals'] ?? 0) &&
            $resources->dark_matter >= ($weapon['cost']['dark_matter'] ?? 0)
        );
    ?>
    <div class="mobile-card" style="<?= $isEquipped ? 'border-color: var(--mobile-accent-green);' : '' ?>">
        <div class="mobile-card-header">
            <h3><i class="fas fa-crosshairs"></i> <?= htmlspecialchars($weapon['name']) ?></h3>
            <?php if ($isEquipped): ?>
                <span class="structure-level" style="color: var(--mobile-accent-green);"><i class="fas fa-check"></i> EQUIPPED</span>
            <?php endif; ?>
        </div>
        <div class="mobile-card-content" style="display: block;">
            <p class="structure-description"><?= htmlspecialchars($weapon['description']) ?></p>
            
            <?php if (!$isEquipped): ?>
                <div class="next-level-info">
                    <ul class="structure-costs">
                        <?php if (($weapon['cost']['credits'] ?? 0) > 0): ?>
                            <li class="<?= $resources->credits >= $weapon['cost']['credits'] ? '' : 'insufficient' ?>">
                                <i class="fas fa-coins"></i> <?= number_format($weapon['cost']['credits']) ?> Credits
                            </li>
                        <?php endif; ?>
                        <?php if (($weapon['cost']['naquadah_crystals'] ?? 0) > 0): ?>
                            <li class="<?= $resources->naquadah_crystals >= $weapon['cost']['naquadah_crystals'] ? '' : 'insufficient' ?>">
                                <i class="fas fa-gem"></i> <?= number_format($weapon['cost']['naquadah_crystals']) ?> Crystals
                            </li>
                        <?php endif; ?>
                        <?php if (($weapon['cost']['dark_matter'] ?? 0) > 0): ?>
                            <li class="<?= $resources->dark_matter >= $weapon['cost']['dark_matter'] ? '' : 'insufficient' ?>">
                                <i class="fas fa-atom"></i> <?= number_format($weapon['cost']['dark_matter']) ?> Dark Matter
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <form action="/generals/equip" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="general_id" value="<?= $general['id'] ?>">
                    <input type="hidden" name="weapon_key" value="<?= htmlspecialchars($key) ?>">
                    <button type="submit" class="btn" <?= $canAfford ? '' : 'disabled' ?> style="width: 100%;">
                        Equip Weapon
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    
    <div style="margin-top: 2rem; text-align: center;">
        <a href="/generals" class="btn btn-outline" style="width: 100%;">
            <i class="fas fa-arrow-left"></i> Back to Command
        </a>
    </div>
</div>
