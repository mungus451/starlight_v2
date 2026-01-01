<?php
// --- Mobile Battle Report Detail View ---
/* @var array $report */
/* @var int $userId */

$iWon = $report['is_winner'];
$color = $iWon ? 'var(--mobile-accent-green)' : 'var(--mobile-accent-red)';
$resultText = $report['result_text']; // Already VICTORY/DEFEAT/STALEMATE from presenter
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">Report #<?= $report['id'] ?></h1>
    </div>

    <!-- Result Banner -->
    <div class="mobile-card" style="text-align: center; border-color: <?= $color ?>; background: linear-gradient(180deg, rgba(0,0,0,0) 0%, <?= $iWon ? 'rgba(51, 255, 153, 0.1)' : 'rgba(255, 51, 102, 0.1)' ?> 100%);">
        <div class="mobile-card-content" style="display: block; padding: 2rem;">
            <h1 style="color: <?= $color ?>; font-size: 2.5rem; text-shadow: 0 0 15px <?= $color ?>;">
                <?= $report['result_text'] ?>
            </h1>
            <p class="text-muted"><?= $report['full_date'] ?></p>
        </div>
    </div>

    <!-- Narrative -->
    <div class="mobile-card">
        <div class="mobile-card-content" style="display: block; padding: 1.5rem; line-height: 1.6; font-size: 1rem; color: #e0e0e0;">
            <?= $report['story_html'] ?>
        </div>
    </div>

    <!-- Loot (if any) -->
    <?php if ($report['credits_plundered'] > 0): ?>
        <div class="mobile-card" style="border-color: var(--mobile-accent-yellow);">
            <div class="mobile-card-header"><h3><i class="fas fa-gem"></i> Spoils of War</h3></div>
            <div class="mobile-card-content" style="display: block;">
                <ul class="mobile-stats-list">
                    <li><span>Credits</span> <strong class="value-green">+<?= number_format($report['credits_plundered']) ?></strong></li>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <!-- Casualties -->
    <div class="mobile-card">
        <div class="mobile-card-header"><h3><i class="fas fa-skull"></i> Casualties</h3></div>
        <div class="mobile-card-content" style="display: block;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <!-- Attacker Losses -->
                <div>
                    <h4 style="color: var(--mobile-text-secondary); border-bottom: 1px solid var(--mobile-border); padding-bottom: 0.5rem;">Attacker</h4>
                    <ul class="mobile-stats-list" style="font-size: 0.9rem;">
                        <li><span>Soldiers</span> <strong class="value-red">-<?= number_format($report['attacker_soldiers_lost']) ?></strong></li>
                    </ul>
                </div>
                <!-- Defender Losses -->
                <div>
                    <h4 style="color: var(--mobile-text-secondary); border-bottom: 1px solid var(--mobile-border); padding-bottom: 0.5rem;">Defender</h4>
                    <ul class="mobile-stats-list" style="font-size: 0.9rem;">
                        <li><span>Guards</span> <strong class="value-red">-<?= number_format($report['defender_guards_lost']) ?></strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top: 2rem; text-align: center;">
        <a href="/battle/reports" class="btn btn-outline" style="width: 100%;">
            <i class="fas fa-arrow-left"></i> Back to Logs
        </a>
    </div>
</div>
