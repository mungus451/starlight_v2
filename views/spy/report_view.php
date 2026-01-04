<?php
// --- Helper variables from the controller ---
/* @var array $report ViewModel from SpyReportPresenter */
?>

<div class="container-full <?= $report['theme_class'] ?>">
    
    <a href="/spy/reports" class="btn-submit" style="width: auto; display: inline-block; margin-bottom: 1rem; background: transparent; border: 1px solid var(--border);">
        &larr; Back to Spy Logs
    </a>

    <!-- NEW: Narrative Story Box -->
    <div class="item-card" style="padding: 2rem; margin-bottom: 2rem; border-color: rgba(255,255,255,0.1); background: rgba(0,0,0,0.3);">
        <div style="font-size: 1.1rem; line-height: 1.8; color: #e0e0e0; text-align: center;">
            <?= $report['story_html'] ?>
        </div>
        <div style="text-align: center; margin-top: 1.5rem; color: var(--muted); font-size: 0.9rem;">
            <?= $report['full_date'] ?>
        </div>
    </div>

    <!-- Legacy Header Banner (Status Only) -->
    <div class="report-banner" style="padding: 1.5rem 1rem; margin-bottom: 2rem;">
        <h1 class="report-status-text" style="font-size: 2.5rem;"><?= $report['result_text'] ?></h1>
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
                        <?php if ($report['naquadah_crystals_seen'] !== null): ?>
                            <li><span>Naquadah</span> <span><?= number_format($report['naquadah_crystals_seen'], 2) ?></span></li>
                        <?php endif; ?>
                        <?php if ($report['dark_matter_seen'] !== null): ?>
                            <li><span>Dark Matter</span> <span><?= number_format($report['dark_matter_seen']) ?></span></li>
                        <?php endif; ?>
                        <?php if ($report['protoform_seen'] !== null): ?>
                            <li><span>Protoform</span> <span><?= number_format($report['protoform_seen'], 2) ?></span></li>
                        <?php endif; ?>
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

                <?php if ($report['operation_result'] === 'success' && ($report['naquadah_crystals_stolen'] > 0 || $report['dark_matter_stolen'] > 0 || $report['protoform_stolen'] > 0)): ?>
                    <h4 style="border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; margin-top: 2rem; margin-bottom: 1rem; color: var(--accent-gold);">Stolen Resources</h4>
                    <ul class="card-stats-list">
                        <?php if ($report['naquadah_crystals_stolen'] > 0): ?>
                            <li><span>Naquadah Stolen</span> <span class="val-success">+ <?= number_format($report['naquadah_crystals_stolen'], 2) ?></span></li>
                        <?php endif; ?>
                        <?php if ($report['dark_matter_stolen'] > 0): ?>
                            <li><span>Dark Matter Stolen</span> <span class="val-success">+ <?= number_format($report['dark_matter_stolen']) ?></span></li>
                        <?php endif; ?>
                        <?php if ($report['protoform_stolen'] > 0): ?>
                            <li><span>Protoform Stolen</span> <span class="val-success">+ <?= number_format($report['protoform_stolen'], 2) ?></span></li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>