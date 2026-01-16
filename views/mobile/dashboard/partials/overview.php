<?php
// --- Partial View: Overview ---
?>
<div id="overview" class="tab-content">
    <div class="mobile-card">
        <div class="mobile-card-header">
            <h3><i class="fas fa-user-circle"></i> Key Stats</h3>
        </div>
        <div class="mobile-card-content" style="display: block;">
            <ul class="mobile-stats-list">
                <li><span><i class="fas fa-star"></i> Points to Spend</span> <strong class="value-green"><?= number_format($stats->level_up_points) ?></strong></li>
                <li><span><i class="fas fa-fist-raised"></i> Strength</span> <strong><?= number_format($stats->strength_points) ?></strong></li>
                <li><span><i class="fas fa-heartbeat"></i> Constitution</span> <strong><?= number_format($stats->constitution_points) ?></strong></li>
                <li><span><i class="fas fa-coins"></i> Wealth</span> <strong><?= number_format($stats->wealth_points) ?></strong></li>
            </ul>
            <a href="/level-up" class="btn">Spend Points</a>
        </div>
    </div>

    <div class="mobile-card">
        <div class="mobile-card-header">
            <h3><i class="fas fa-users-cog"></i> Unit Command</h3>
        </div>
        <div class="mobile-card-content" style="display: block;">
            <ul class="mobile-stats-list">
                <li><span><i class="fas fa-fighter-jet"></i> Soldiers</span> <strong><?= number_format($resources->soldiers) ?></strong></li>
                <li><span><i class="fas fa-user-shield"></i> Guards</span> <strong><?= number_format($resources->guards) ?></strong></li>
                <li><span><i class="fas fa-user-secret"></i> Spies</span> <strong><?= number_format($resources->spies) ?></strong></li>
                <li><span><i class="fas fa-broadcast-tower"></i> Sentries</span> <strong><?= number_format($resources->sentries) ?></strong></li>
            </ul>
            <a href="/training" class="btn">Train Units</a>
        </div>
    </div>
</div>
