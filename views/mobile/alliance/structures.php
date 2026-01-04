<?php
// --- Mobile Alliance Structures View ---
/* @var \App\Models\Entities\Alliance $alliance */
/* @var array $definitions */
/* @var array $costs */
/* @var array $currentLevels */
/* @var bool $canManage */
/* @var string $csrf_token */
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">Alliance Structures</h1>
        <p style="color: var(--mobile-text-secondary); font-size: 0.9rem; margin: 0.5rem 1rem 0 1rem; text-align: center;">Upgrade your alliance's collective power.</p>
    </div>

    <!-- Bank Status -->
    <div class="mobile-card">
        <div class="mobile-card-header"><h3><i class="fas fa-university"></i> Alliance Bank</h3></div>
        <div class="mobile-card-content" style="display: block; text-align: center;">
            <div style="font-size: 1.5rem; color: var(--mobile-accent-yellow); font-family: 'Orbitron', sans-serif;">
                <?= number_format($alliance->bank_credits) ?> Credits
            </div>
        </div>
    </div>

    <?php foreach ($definitions as $def): 
        $key = $def->structure_key;
        $level = $currentLevels[$key] ?? 0;
        $cost = $costs[$key] ?? 0;
        $canAfford = $alliance->bank_credits >= $cost;
    ?>
    <div class="mobile-card">
        <div class="mobile-card-header">
            <h3><?= htmlspecialchars($def->name) ?></h3>
            <span class="structure-level">Lvl <?= $level ?></span>
        </div>
        <div class="mobile-card-content" style="display: block;">
            <p class="structure-description"><?= htmlspecialchars($def->description) ?></p>

            <div class="next-level-info">
                <ul class="structure-costs">
                    <li><i class="fas fa-coins"></i> <?= number_format($cost) ?> Credits</li>
                </ul>
            </div>

            <?php if ($canManage): ?>
            <form action="/alliance/structures/upgrade" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="structure_key" value="<?= htmlspecialchars($key) ?>">
                <button type="submit" class="btn" <?= $canAfford ? '' : 'disabled' ?>>
                    <i class="fas fa-arrow-alt-circle-up"></i> Upgrade
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
