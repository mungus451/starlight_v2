<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\BattleReport $report */
/* @var int $userId */

// --- LOGIC ---
$isAttacker = ($report->attacker_id === $userId);

$attackerName = $report->attacker_name ?? 'Unknown Attacker';
$defenderName = $report->defender_name ?? 'Unknown Defender';

$viewerName = $isAttacker ? $attackerName : $defenderName;
$opponentName = $isAttacker ? $defenderName : $attackerName;
$viewerLabel = $isAttacker ? "Attacker" : "Defender";
$opponentLabel = $isAttacker ? "Defender" : "Attacker";

$isWinner = false;
$isStalemate = ($report->attack_result === 'stalemate');

if (!$isStalemate) {
    if ($isAttacker) {
        $isWinner = ($report->attack_result === 'victory');
    } else {
        $isWinner = ($report->attack_result === 'defeat');
    }
}

$themeClass = $isStalemate ? 'theme-draw' : ($isWinner ? 'theme-win' : 'theme-loss');
$statusText = $isStalemate ? 'STALEMATE' : ($isWinner ? 'VICTORY' : 'DEFEAT');
// --- END LOGIC ---
?>

<!-- Wrapper gets the theme class for coloring -->
<div class="container-full <?= $themeClass ?>">
    
    <a href="/battle/reports" class="btn-submit" style="width: auto; display: inline-block; margin-bottom: 1rem; background: transparent; border: 1px solid var(--border);">
        &larr; Back to Log
    </a>

    <div class="report-banner">
        <h1 class="report-status-text"><?= $statusText ?></h1>
        <span style="color: var(--muted); font-size: 0.9rem; display: block; margin-top: 0.5rem;">
            <?= date('F j, Y - H:i', strtotime($report->created_at)) ?>
        </span>
    </div>

    <div class="versus-grid">
        <div class="combat-player-card is-me">
            <div class="player-avatar" style="margin: 0 auto 1rem; width: 100px; height: 100px; border-radius: 50%; border: 3px solid var(--border); display: flex; align-items: center; justify-content: center; background: #111; color: #444;">
                <i class="fas fa-user fa-2x"></i>
            </div>
            <div style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-bottom: 0.25rem;">
                <?= htmlspecialchars($viewerName) ?>
            </div>
            <div style="font-size: 0.8rem; text-transform: uppercase; color: var(--muted); font-weight: bold;">
                <?= $viewerLabel ?>
            </div>
        </div>

        <div class="vs-divider">VS</div>

        <div class="combat-player-card">
            <div class="player-avatar" style="margin: 0 auto 1rem; width: 100px; height: 100px; border-radius: 50%; border: 3px solid var(--border); display: flex; align-items: center; justify-content: center; background: #111; color: #444;">
                <i class="fas fa-user fa-2x"></i>
            </div>
            <div style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-bottom: 0.25rem;">
                <?= htmlspecialchars($opponentName) ?>
            </div>
            <div style="font-size: 0.8rem; text-transform: uppercase; color: var(--muted); font-weight: bold;">
                <?= $opponentLabel ?>
            </div>
        </div>
    </div>

    <div class="data-card">
        <h3 style="text-align: center;">Combat Analysis</h3>
        
        <div class="comparison-row">
            <span class="stat-val left"><?= number_format($report->soldiers_sent) ?></span>
            <span class="stat-label">Soldiers Sent</span>
            <span class="stat-val right">Unknown</span>
        </div>

        <div class="comparison-row">
            <span class="stat-val left"><?= number_format($report->attacker_offense_power) ?></span>
            <span class="stat-label">Total Power</span>
            <span class="stat-val right"><?= number_format($report->defender_defense_power) ?></span>
        </div>
        
        <div class="comparison-row">
            <span class="stat-val left val-danger">-<?= number_format($report->attacker_soldiers_lost) ?></span>
            <span class="stat-label">Casualties</span>
            <span class="stat-val right val-danger">-<?= number_format($report->defender_guards_lost) ?></span>
        </div>
    </div>

    <?php if ($isWinner): ?>
    <div class="loot-box">
        <div class="loot-title">Spoils of War</div>
        <div class="loot-amount">+<?= number_format($report->credits_plundered) ?> Credits</div>
        <div style="color: var(--muted); margin-top: 0.5rem;">
            +<?= number_format($report->experience_gained) ?> Experience Points
        </div>
    </div>
    <?php endif; ?>

</div>