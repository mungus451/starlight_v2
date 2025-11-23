<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\War[] $activeWars */
/* @var \App\Models\Entities\WarHistory[] $historicalWars */
/* @var \App\Models\Entities\Alliance[] $otherAlliances */
/* @var \App\Models\Entities\User $viewer */
/* @var bool $canDeclareWar */
/* @var int $allianceId */
?>

<div class="container-full">
    <h1>Alliance War Room</h1>
    
    <a href="/alliance/profile/<?= $allianceId ?>" class="btn-submit btn-accent" style="max-width: 400px; margin: 0 auto 1.5rem auto;">
        &laquo; Back to Alliance Profile
    </a>

    <!-- Use Split Grid (1/3 left, 2/3 right) -->
    <div class="split-grid">
        
        <!-- Left Column: Declare War Form -->
        <div class="item-card">
            <h4>Declare War</h4>
            <?php if ($canDeclareWar): ?>
                <form action="/alliance/war/declare" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    
                    <div class="form-group">
                        <label for="war_name">War Name</label>
                        <input type="text" name="war_name" id="war_name" required placeholder="e.g., The Eastern Expanse War">
                    </div>

                    <div class="form-group">
                        <label for="target_alliance_id">Target Alliance</label>
                        <select name="target_alliance_id" id="target_alliance_id" required>
                            <option value="">Select an alliance...</option>
                            <?php foreach ($otherAlliances as $a): ?>
                                <option value="<?= $a->id ?>">[<?= htmlspecialchars($a->tag) ?>] <?= htmlspecialchars($a->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="goal_key">War Goal</label>
                        <select name="goal_key" id="goal_key" required>
                            <option value="credits_plundered">Credits Plundered</option>
                            <option value="units_killed">Units Killed</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="goal_threshold">Goal Threshold</label>
                        <input type="number" name="goal_threshold" id="goal_threshold" required placeholder="e.g., 10000000">
                    </div>
                    
                    <div class="form-group">
                        <label for="casus_belli">Casus Belli (Reason)</label>
                        <textarea name="casus_belli" id="casus_belli" placeholder="e.g., Territorial expansion..." style="min-height: 80px;"></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit btn-reject">Declare War</button>
                </form>
            <?php else: ?>
                <p class="form-note" style="text-align: center; padding: 2rem 0;">
                    You do not have the required permissions (`can_declare_war`) to declare war on behalf of your alliance.
                </p>
            <?php endif; ?>
        </div>

        <!-- Right Column: Active Wars & History -->
        <div class="item-card">
            <h4>Active Wars</h4>
            <ul class="data-list">
                <?php if (empty($activeWars)): ?>
                    <li class="data-item" style="color: var(--muted); justify-content: center;">There are no active wars.</li>
                <?php else: ?>
                    <?php foreach ($activeWars as $war): ?>
                        <li class="data-item">
                            <div class="item-info">
                                <span class="name"><?= htmlspecialchars($war->name) ?></span>
                                <span class="role">
                                    <?= htmlspecialchars($war->declarer_name) ?> vs <?= htmlspecialchars($war->defender_name) ?>
                                </span>
                            </div>
                            <div style="text-align: right; font-size: 0.9rem; color: var(--muted);">
                                Goal: <?= number_format($war->declarer_score) ?> / <?= number_format($war->goal_threshold) ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
            
            <h4 style="margin-top: 1.5rem; border-top: 1px solid var(--border); padding-top: 1rem;">War History</h4>
            <ul class="data-list">
                <?php if (empty($historicalWars)): ?>
                    <li class="data-item" style="color: var(--muted); justify-content: center;">There is no war history.</li>
                <?php else: ?>
                    <?php foreach ($historicalWars as $war): ?>
                        <li class="data-item">
                            <div class="item-info">
                                <span class="name"><?= htmlspecialchars($war->name) ?></span>
                                <span class="role">
                                    <?= htmlspecialchars($war->declarer_name) ?> vs <?= htmlspecialchars($war->defender_name) ?>
                                </span>
                            </div>
                            <span class="text-status-active">
                                <?= ucfirst($war->outcome) ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>