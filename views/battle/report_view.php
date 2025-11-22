<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\BattleReport $report */
/* @var int $userId */

// --- FIXED LOGIC: Use only $report properties ---
$isAttacker = ($report->attacker_id === $userId);

// Names from the report entity
$attackerName = $report->attacker_name ?? 'Unknown Attacker';
$defenderName = $report->defender_name ?? 'Unknown Defender';

$viewerName = $isAttacker ? $attackerName : $defenderName;
$opponentName = $isAttacker ? $defenderName : $attackerName;
$viewerLabel = $isAttacker ? "Attacker" : "Defender";
$opponentLabel = $isAttacker ? "Defender" : "Attacker";

// Determine result from viewer perspective
$isWinner = false;
$isStalemate = ($report->attack_result === 'stalemate');

if (!$isStalemate) {
    if ($isAttacker) {
        $isWinner = ($report->attack_result === 'victory');
    } else {
        // If I am defender, attacker's defeat is my victory
        $isWinner = ($report->attack_result === 'defeat');
    }
}

// Theme Classes
$themeClass = $isStalemate ? 'theme-draw' : ($isWinner ? 'theme-win' : 'theme-loss');
$statusText = $isStalemate ? 'STALEMATE' : ($isWinner ? 'VICTORY' : 'DEFEAT');
?>

<style>
    :root {
        --bg-dark: #050712;
        --panel-bg: rgba(13, 15, 27, 0.6);
        --border-color: rgba(255, 255, 255, 0.08);
        
        --color-win: #00e676;
        --color-loss: #ff4d4d;
        --color-draw: #f9c74f;
        --color-teal: #2dd1d1;
    }

    .report-detail-container {
        width: 100%;
        max-width: 1000px;
        margin: 0 auto;
        padding-bottom: 2rem;
    }

    /* --- Header Banner --- */
    .report-banner {
        text-align: center;
        padding: 3rem 1rem;
        background: radial-gradient(circle at center, rgba(13, 15, 27, 0.9) 0%, rgba(5, 7, 18, 1) 100%);
        border-bottom: 2px solid var(--border-color);
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }
    
    .theme-win .report-banner { border-bottom-color: var(--color-win); background: radial-gradient(circle at center, rgba(0, 230, 118, 0.1) 0%, rgba(5, 7, 18, 1) 100%); }
    .theme-loss .report-banner { border-bottom-color: var(--color-loss); background: radial-gradient(circle at center, rgba(255, 77, 77, 0.1) 0%, rgba(5, 7, 18, 1) 100%); }
    
    .report-status {
        font-family: "Orbitron", sans-serif;
        font-size: 3.5rem;
        font-weight: 900;
        letter-spacing: 0.1em;
        margin: 0;
        text-shadow: 0 0 30px rgba(0,0,0,0.8);
    }
    .theme-win .report-status { color: var(--color-win); }
    .theme-loss .report-status { color: var(--color-loss); }
    .theme-draw .report-status { color: var(--color-draw); }

    .report-date {
        color: #8f9bb3;
        font-size: 0.9rem;
        margin-top: 0.5rem;
        display: block;
    }

    /* --- Back Button --- */
    .back-link {
        display: inline-block;
        margin-bottom: 1rem;
        color: #8f9bb3;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.2s;
    }
    .back-link:hover { color: #fff; }

    /* --- VS Grid --- */
    .versus-grid {
        display: grid;
        grid-template-columns: 1fr 80px 1fr;
        gap: 1rem;
        align-items: start;
        margin-bottom: 2rem;
    }

    .player-card {
        background: var(--panel-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.5rem;
        text-align: center;
        backdrop-filter: blur(10px);
    }
    .player-card.is-me {
        border-color: rgba(45, 209, 209, 0.3);
        background: rgba(45, 209, 209, 0.05);
    }
    
    .avatar-frame {
        width: 100px;
        height: 100px;
        margin: 0 auto 1rem;
        border-radius: 50%;
        border: 3px solid var(--border-color);
        overflow: hidden;
        background: #111;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #444;
    }
    
    .player-name { font-size: 1.25rem; font-weight: 700; color: #fff; margin-bottom: 0.25rem; }
    .player-role { font-size: 0.8rem; text-transform: uppercase; color: #8f9bb3; letter-spacing: 0.05em; font-weight: bold; }

    .vs-divider {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        font-family: "Orbitron", sans-serif;
        font-size: 1.5rem;
        font-weight: 900;
        color: rgba(255,255,255,0.2);
        font-style: italic;
    }

    /* --- Stats Table --- */
    .stats-container {
        background: var(--panel-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    .stats-header {
        font-family: "Orbitron", sans-serif;
        font-size: 1.1rem;
        color: #fff;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 0.75rem;
        margin-bottom: 1rem;
    }

    .comparison-row {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid rgba(255,255,255,0.03);
        font-size: 0.95rem;
    }
    .comparison-row:last-child { border-bottom: none; }
    
    .stat-label { color: #8f9bb3; text-align: center; flex: 1; }
    .stat-val { flex: 1; font-weight: 600; color: #eff1ff; }
    .stat-val.left { text-align: left; }
    .stat-val.right { text-align: right; }
    
    .val-highlight { color: var(--color-teal); }
    .val-danger { color: var(--color-loss); }

    /* --- Loot Box --- */
    .loot-box {
        background: linear-gradient(135deg, rgba(249, 199, 79, 0.1), rgba(0,0,0,0));
        border: 1px solid rgba(249, 199, 79, 0.3);
        border-radius: 12px;
        padding: 1.5rem;
        text-align: center;
        margin-top: 1rem;
    }
    .loot-title { color: var(--color-draw); font-weight: bold; text-transform: uppercase; letter-spacing: 0.1em; font-size: 0.85rem; margin-bottom: 0.5rem; }
    .loot-amount { font-size: 2rem; font-weight: 700; color: #fff; text-shadow: 0 0 15px rgba(249, 199, 79, 0.4); }

    /* Mobile */
    @media (max-width: 768px) {
        .versus-grid {
            grid-template-columns: 1fr; /* Stack vertically */
            gap: 1.5rem;
        }
        .vs-divider { display: none; }
        .report-status { font-size: 2.5rem; }
    }
</style>

<div class="report-detail-container <?= $themeClass ?>">
    
    <a href="/battle/reports" class="back-link">&larr; Back to Battle Log</a>

    <div class="report-banner">
        <h1 class="report-status"><?= $statusText ?></h1>
        <span class="report-date"><?= date('F j, Y - H:i', strtotime($report->created_at)) ?></span>
    </div>

    <div class="versus-grid">
        <div class="player-card is-me">
            <div class="avatar-frame">
                <svg width="40" height="40" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="player-name"><?= htmlspecialchars($viewerName) ?></div>
            <div class="player-role" style="color: var(--color-draw);"><?= $viewerLabel ?></div>
        </div>

        <div class="vs-divider">VS</div>

        <div class="player-card">
            <div class="avatar-frame">
                <svg width="40" height="40" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="player-name"><?= htmlspecialchars($opponentName) ?></div>
            <div class="player-role" style="color: var(--color-teal);"><?= $opponentLabel ?></div>
        </div>
    </div>

    <div class="stats-container">
        <div class="stats-header">Combat Analysis</div>
        
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
        <div style="color: #aaa; margin-top: 0.5rem;">+<?= number_format($report->experience_gained) ?> Experience Points</div>
    </div>
    <?php endif; ?>

</div>