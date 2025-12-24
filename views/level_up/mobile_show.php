<?php
// --- Mobile Level Up View ---
/* @var \App\Models\Entities\UserStats $stats */
/* @var array $costs */
/* @var string $csrf_token */

$costPerPoint = $costs['cost_per_point'] ?? 1;

$statDefinitions = [
    'strength' => ['name' => 'Strength', 'icon' => 'fa-fist-raised', 'current' => $stats->strength_points, 'desc' => 'Increases Unit Offense'],
    'constitution' => ['name' => 'Constitution', 'icon' => 'fa-shield-alt', 'current' => $stats->constitution_points, 'desc' => 'Increases Unit Defense'],
    'wealth' => ['name' => 'Wealth', 'icon' => 'fa-coins', 'current' => $stats->wealth_points, 'desc' => 'Increases Income Generation'],
    'dexterity' => ['name' => 'Dexterity', 'icon' => 'fa-user-secret', 'current' => $stats->dexterity_points, 'desc' => 'Increases Spy & Sentry Power'],
    'charisma' => ['name' => 'Charisma', 'icon' => 'fa-crown', 'current' => $stats->charisma_points, 'desc' => 'Unlocks General Slots']
];
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">Neural Link</h1>
        <p style="color: var(--mobile-text-secondary); font-size: 0.9rem; margin: 0.5rem 1rem 0 1rem; text-align: center;">Allocate skill points to enhance your capabilities.</p>
    </div>

    <!-- Status Card -->
    <div class="mobile-card" style="text-align: center; border-color: var(--mobile-accent-green);">
        <div class="mobile-card-header" style="justify-content: center;">
            <h3 style="color: var(--mobile-accent-green);"><i class="fas fa-dna"></i> Available SP</h3>
        </div>
        <div class="mobile-card-content" style="display: block;">
            <div style="font-family: 'Orbitron', sans-serif; font-size: 2.5rem; color: var(--mobile-accent-green); text-shadow: 0 0 15px rgba(51, 255, 153, 0.4);">
                <?= number_format($stats->level_up_points) ?>
            </div>
            <p class="text-muted" style="font-size: 0.8rem; margin-top: 0.5rem;">Cost per point: <?= $costPerPoint ?></p>
        </div>
    </div>

    <form action="/level-up/spend" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <?php foreach ($statDefinitions as $key => $def): ?>
            <div class="mobile-card">
                <div class="mobile-card-header">
                    <h3><i class="fas <?= $def['icon'] ?>"></i> <?= $def['name'] ?></h3>
                    <span class="structure-level" style="color: var(--mobile-text-primary);">Cur: <?= number_format($def['current']) ?></span>
                </div>
                <div class="mobile-card-content" style="display: block;">
                    <p class="structure-description" style="margin-bottom: 0.5rem;"><?= $def['desc'] ?></p>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
                        <label for="<?= $key ?>" style="color: var(--mobile-text-secondary); font-weight: 600;">Add:</label>
                        <input type="number" name="<?= $key ?>" id="<?= $key ?>" 
                               class="mobile-input" value="0" min="0" 
                               style="width: 100%; max-width: 150px; text-align: center; padding: 0.5rem; background: rgba(0,0,0,0.3); border: 1px solid var(--mobile-border); color: var(--mobile-text-primary); border-radius: 4px; font-family: 'Orbitron', sans-serif;">
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="mobile-card" style="margin-top: 1rem; position: sticky; bottom: 1rem; z-index: 100; background: rgba(10, 10, 15, 0.95); backdrop-filter: blur(10px); border: 1px solid var(--mobile-accent-yellow);">
            <div class="mobile-card-content" style="display: block; padding: 1rem;">
                <button type="submit" class="btn btn-accent" style="width: 100%; font-size: 1.1rem; padding: 1rem;">
                    <i class="fas fa-check-double"></i> Confirm Upgrades
                </button>
            </div>
        </div>
    </form>
</div>
