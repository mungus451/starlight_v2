<?php
// --- Mobile Training View ---
/* @var \App\Models\Entities\UserResource $resources */
/* @var array $costs */
/* @var string $csrf_token */

$units = [
    'workers' => ['name' => 'Workers', 'icon' => 'fa-hammer', 'desc' => 'Generates income.', 'current' => $resources->workers],
    'soldiers' => ['name' => 'Soldiers', 'icon' => 'fa-user-ninja', 'desc' => 'Offensive unit.', 'current' => $resources->soldiers],
    'guards' => ['name' => 'Guards', 'icon' => 'fa-user-shield', 'desc' => 'Defensive unit.', 'current' => $resources->guards],
    'spies' => ['name' => 'Spies', 'icon' => 'fa-mask', 'desc' => 'Intelligence & sabotage.', 'current' => $resources->spies],
    'sentries' => ['name' => 'Sentries', 'icon' => 'fa-satellite-dish', 'desc' => 'Counter-espionage.', 'current' => $resources->sentries],
];
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">Barracks</h1>
        <p style="color: var(--mobile-text-secondary); font-size: 0.9rem; margin: 0.5rem 1rem 0 1rem; text-align: center;">Train citizens into specialized units.</p>
    </div>

    <!-- Resource Overview -->
    <div class="mobile-card resource-overview-card">
        <div class="mobile-card-header">
            <h3><i class="fas fa-wallet"></i> Resources</h3>
        </div>
        <div class="mobile-card-content" style="display: block;">
            <ul class="mobile-stats-list">
                <li><span><i class="fas fa-coins"></i> Credits</span> <strong><?= number_format($resources->credits) ?></strong></li>
                <li><span><i class="fas fa-users"></i> Untrained Citizens</span> <strong><?= number_format($resources->untrained_citizens) ?></strong></li>
            </ul>
        </div>
    </div>

    <?php foreach ($units as $key => $unit): 
        $cost = $costs[$key] ?? ['credits' => 0, 'citizens' => 1];
        
        // Calculate Max Trainable
        $maxByCredits = ($cost['credits'] > 0) ? floor($resources->credits / $cost['credits']) : 999999;
        $maxByCitizens = ($cost['citizens'] > 0) ? floor($resources->untrained_citizens / $cost['citizens']) : 999999;
        $maxTrainable = min($maxByCredits, $maxByCitizens);
    ?>
    <div class="mobile-card">
        <div class="mobile-card-header">
            <h3><i class="fas <?= $unit['icon'] ?>"></i> <?= $unit['name'] ?></h3>
            <span class="structure-level" style="color: var(--mobile-text-primary);">Owned: <?= number_format($unit['current']) ?></span>
        </div>
        <div class="mobile-card-content" style="display: block;">
            <p class="structure-description"><?= $unit['desc'] ?></p>
            
            <div class="next-level-info">
                <ul class="structure-costs">
                    <li><i class="fas fa-coins"></i> <?= number_format($cost['credits']) ?> Credits</li>
                    <li><i class="fas fa-user"></i> <?= number_format($cost['citizens']) ?> Citizen</li>
                </ul>
            </div>

            <form action="/training/train" method="POST" style="margin-top: 1rem;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="unit_type" value="<?= $key ?>">
                
                <div class="form-group">
                    <label for="amount_<?= $key ?>">Amount</label>
                    <input type="number" name="amount" id="amount_<?= $key ?>" class="mobile-input" placeholder="0" min="1" max="<?= $maxTrainable ?>" style="width: 100%; margin-bottom: 0.5rem;">
                    <button type="button" class="btn btn-sm" style="width: 100%;" onclick="document.getElementById('amount_<?= $key ?>').value = '<?= $maxTrainable ?>'">MAX (<?= number_format($maxTrainable) ?>)</button>
                </div>

                <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;" <?= $maxTrainable > 0 ? '' : 'disabled' ?>>
                    <i class="fas fa-dumbbell"></i> Train
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
