<?php
// --- Helper variables from the controller ---
/* @var array $report ViewModel from SpyReportPresenter */
?>

<div class="container-full <?= $report['theme_class'] ?>">
    
    <a href="/spy/reports" class="btn-submit" style="width: auto; display: inline-block; margin-bottom: 1rem; background: transparent; border: 1px solid var(--border);">
        &larr; Back to Spy Logs
    </a>

    <div class="report-banner">
        <h1 class="report-status-text"><?= $report['result_text'] ?></h1>
        <span style="color: var(--muted); font-size: 0.9rem; display: block; margin-top: 0.5rem;">
            <?= $report['full_date'] ?>
        </span>
    </div>

    <div class="versus-grid">
        <!-- Viewer Card -->
        <div class="combat-player-card is-me">
            <div class="player-avatar" style="margin: 0 auto 1rem; width: 100px; height: 100px; border-radius: 50%; border: 3px solid var(--border); display: flex; align-items: center; justify-content: center; background: #111; color: #444;">
                <i class="fas fa-user-secret fa-2x"></i>
            </div>
            <div style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-bottom: 0.25rem;">
                <?= htmlspecialchars($report['viewer_name']) ?>
            </div>
            <div style="font-size: 0.8rem; text-transform: uppercase; color: var(--muted); font-weight: bold;">
                <?= $report['viewer_label'] ?>
            </div>
        </div>

        <div class="vs-divider">VS</div>

        <!-- Opponent Card -->
        <div class="combat-player-card">
            <div class="player-avatar" style="margin: 0 auto 1rem; width: 100px; height: 100px; border-radius: 50%; border: 3px solid var(--border); display: flex; align-items: center; justify-content: center; background: #111; color: #444;">
                <i class="fas fa-user fa-2x"></i>
            </div>
            <div style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-bottom: 0.25rem;">
                <?= htmlspecialchars($report['opponent_name']) ?>
            </div>
            <div style="font-size: 0.8rem; text-transform: uppercase; color: var(--muted); font-weight: bold;">
                <?= $report['opponent_label'] ?>
            </div>
        </div>
    </div>

    <div class="item-card" style="padding: 2rem;">
        <h3 style="text-align: center; margin-bottom: 1.5rem;">Intelligence Briefing</h3>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <!-- Left: Losses -->
            <div>
                <h4 style="border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1rem; color: var(--accent-red);">Casualties</h4>
                <ul class="card-stats-list">
                    <li>
                        <span>Spies Lost</span>
                        <span class="val-danger">- <?= number_format($report['spies_lost_attacker']) ?></span>
                    </li>
                    <li>
                        <span>Sentries Lost</span>
                        <span class="val-danger">- <?= number_format($report['sentries_lost_defender']) ?></span>
                    </li>
                </ul>
            </div>

            <!-- Right: Intel -->
            <div>
                <h4 style="border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1rem; color: var(--accent-blue);">Gathered Intel</h4>
                
                <?php if ($report['operation_result'] === 'success'): ?>
                    <ul class="card-stats-list">
                        <li><span>Credits</span> <span><?= number_format($report['credits_seen']) ?></span></li>
                        <li><span>Soldiers</span> <span><?= number_format($report['soldiers_seen']) ?></span></li>
                        <li><span>Guards</span> <span><?= number_format($report['guards_seen']) ?></span></li>
                        <li><span>Sentries</span> <span><?= number_format($report['sentries_seen']) ?></span></li>
                        <li><span>Fortification</span> <span>Lvl <?= number_format($report['fortification_level_seen']) ?></span></li>
                        <li><span>Armory</span> <span>Lvl <?= number_format($report['armory_level_seen']) ?></span></li>
                    </ul>
                <?php else: ?>
                    <p style="text-align: center; color: var(--muted); padding: 1rem 0; font-style: italic;">
                        Target defenses prevented intel gathering.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>