<?php
// --- Mobile Spy Report Detail View ---
/* @var array $report */
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">Report #<?= $report['id'] ?></h1>
        <p style="color: var(--mobile-text-secondary); font-size: 0.9rem; margin: 0.5rem 1rem 0 1rem; text-align: center;">Operation against <?= htmlspecialchars($report['opponent_name']) ?></p>
    </div>

    <!-- Result Banner -->
    <div class="mobile-card" style="text-align: center; border-color: <?= $report['result_class'] === 'res-win' ? 'var(--mobile-accent-green)' : 'var(--mobile-accent-red)' ?>; background: linear-gradient(180deg, rgba(0,0,0,0) 0%, <?= $report['result_class'] === 'res-win' ? 'rgba(51, 255, 153, 0.1)' : 'rgba(255, 51, 102, 0.1)' ?> 100%);">
        <div class="mobile-card-content" style="display: block; padding: 2rem;">
            <h1 style="color: <?= $report['result_class'] === 'res-win' ? 'var(--mobile-accent-green)' : 'var(--mobile-accent-red)' ?>; font-size: 2.5rem; text-shadow: 0 0 15px <?= $report['result_class'] === 'res-win' ? 'var(--mobile-accent-green)' : 'var(--mobile-accent-red)' ?>;">
                <?= $report['result_text'] ?>
            </h1>
            <p class="text-muted"><?= $report['full_date'] ?></p>
        </div>
    </div>

    <!-- Narrative Section -->
    <div class="mobile-card">
        <div class="mobile-card-content" style="display: block; padding: 1.5rem; line-height: 1.6; font-size: 1rem; color: #e0e0e0;">
            <?= $report['story_html'] ?>
        </div>
    </div>

    <?php if ($report['result_class'] === 'res-win'): ?>
        <h3 class="mobile-category-header">Dossier</h3>
        
        <!-- Resources -->
        <div class="mobile-card">
            <div class="mobile-card-header"><h3><i class="fas fa-coins"></i> Resources Detected</h3></div>
            <div class="mobile-card-content" style="display: block;">
                <ul class="mobile-stats-list">
                    <li><span>Credits</span> <strong><?= number_format($report['credits_seen'] ?? 0) ?></strong></li>
                </ul>
            </div>
        </div>

        <!-- Military -->
        <div class="mobile-card">
            <div class="mobile-card-header"><h3><i class="fas fa-crosshairs"></i> Military Estimates</h3></div>
            <div class="mobile-card-content" style="display: block;">
                <ul class="mobile-stats-list">
                    <li><span>Soldiers</span> <strong><?= number_format($report['soldiers_seen'] ?? 0) ?></strong></li>
                    <li><span>Guards</span> <strong><?= number_format($report['guards_seen'] ?? 0) ?></strong></li>
                    <li><span>Spies</span> <strong><?= number_format($report['spies_seen'] ?? 0) ?></strong></li>
                    <li><span>Sentries</span> <strong><?= number_format($report['sentries_seen'] ?? 0) ?></strong></li>
                </ul>
            </div>
        </div>

        <!-- Structures -->
        <div class="mobile-card">
            <div class="mobile-card-header"><h3><i class="fas fa-city"></i> Infrastructure</h3></div>
            <div class="mobile-card-content" style="display: block;">
                <ul class="mobile-stats-list">
                    <li><span>Armory</span> <strong>Lvl <?= $report['armory_level_seen'] ?? '?' ?></strong></li>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <div style="margin-top: 2rem; text-align: center;">
        <a href="/spy/reports" class="btn btn-outline" style="width: 100%;">
            <i class="fas fa-arrow-left"></i> Back to Archives
        </a>
    </div>
</div>
