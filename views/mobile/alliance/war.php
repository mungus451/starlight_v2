<?php
// --- Mobile Alliance War Room View ---
/* @var array $otherAlliances */
/* @var bool $canDeclareWar */
/* @var string $csrf_token */

$warGoals = [
    'credits_plundered' => 'Credits Plundered',
    'units_killed' => 'Units Killed'
];
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">War Room</h1>
        <p style="color: var(--mobile-text-secondary); font-size: 0.9rem; margin: 0.5rem 1rem 0 1rem; text-align: center;">Orchestrate large-scale conflicts.</p>
    </div>

    <?php if ($canDeclareWar): ?>
    <div class="mobile-card">
        <div class="mobile-card-header"><h3><i class="fas fa-scroll"></i> Declare War</h3></div>
        <div class="mobile-card-content" style="display: block;">
            <form action="/alliance/war/declare" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                
                <div class="form-group">
                    <label for="target_alliance_id">Target Alliance</label>
                    <select name="target_alliance_id" id="target_alliance_id" class="mobile-input" style="width: 100%;">
                        <?php foreach ($otherAlliances as $oa): ?>
                            <option value="<?= $oa->id ?>">[<?= htmlspecialchars($oa->tag) ?>] <?= htmlspecialchars($oa->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="war_name">War Name</label>
                    <input type="text" name="war_name" id="war_name" class="mobile-input" style="width: 100%;" required>
                </div>

                <div class="form-group">
                    <label for="casus_belli">Casus Belli (Reason for War)</label>
                    <textarea name="casus_belli" id="casus_belli" class="mobile-input" style="width: 100%; height: 80px;"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="goal_key">War Goal</label>
                    <select name="goal_key" id="goal_key" class="mobile-input" style="width: 100%;">
                        <?php foreach ($warGoals as $key => $label): ?>
                            <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="goal_threshold">Goal Threshold</label>
                    <input type="number" name="goal_threshold" id="goal_threshold" class="mobile-input" style="width: 100%;" min="1" required>
                </div>

                <button type="submit" class="btn btn-danger" style="width: 100%; margin-top: 1rem;">
                    <i class="fas fa-exclamation-triangle"></i> Declare War
                </button>
            </form>
        </div>
    </div>
    <?php else: ?>
    <div class="info-box">
        <i class="fas fa-info-circle"></i> You do not have permission to declare war.
    </div>
    <?php endif; ?>

    <h3 class="mobile-category-header">Active Wars</h3>
    <p class="text-center text-muted">No active wars.</p>
    
    <h3 class="mobile-category-header">Historical Wars</h3>
    <p class="text-center text-muted">No historical wars.</p>
</div>
