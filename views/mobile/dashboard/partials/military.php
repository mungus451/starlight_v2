<?php
// --- Partial View: Military ---
/* @var \App\Models\Entities\UserResource $resources */
/* @var array $offenseBreakdown */
/* @var array $defenseBreakdown */
/* @var array $spyBreakdown */
/* @var array $sentryBreakdown */
/* @var array $generals */
?>
<div class="mobile-card">
    <div class="mobile-card-header">
        <h3><i class="fas fa-fist-raised"></i> Power Ratings</h3>
    </div>
    <div class="mobile-card-content" style="display: block;">
        <ul class="mobile-stats-list">
            <li><span><i class="fas fa-bolt value-red"></i> Offense Power</span> <strong class="value-red"><?= number_format($offenseBreakdown['total']) ?></strong></li>
            <li><span><i class="fas fa-shield-alt value-blue"></i> Defense Rating</span> <strong class="value-blue"><?= number_format($defenseBreakdown['total']) ?></strong></li>
            <li><span><i class="fas fa-user-secret value-green"></i> Spy Power</span> <strong class="value-green"><?= number_format($spyBreakdown['total']) ?></strong></li>
            <li><span><i class="fas fa-broadcast-tower value-green"></i> Sentry Power</span> <strong class="value-green"><?= number_format($sentryBreakdown['total']) ?></strong></li>
        </ul>
    </div>
</div>

<div class="mobile-card">
    <div class="mobile-card-header">
        <h3><i class="fas fa-users-cog"></i> Unit Overview</h3>
    </div>
    <div class="mobile-card-content" style="display: block;">
        <ul class="mobile-stats-list">
            <li><span><i class="fas fa-user-ninja"></i> Soldiers</span> <strong><?= number_format($resources->soldiers) ?></strong></li>
            <li><span><i class="fas fa-user-shield"></i> Guards</span> <strong><?= number_format($resources->guards) ?></strong></li>
            <li><span><i class="fas fa-mask"></i> Spies</span> <strong><?= number_format($resources->spies) ?></strong></li>
            <li><span><i class="fas fa-satellite-dish"></i> Sentries</span> <strong><?= number_format($resources->sentries) ?></strong></li>
        </ul>
        <a href="/training" class="btn">Train Units</a>
    </div>
</div>

<!-- Elite Units Section (Commented out for this branch) -->
<!--
<div class="mobile-card">
    <div class="mobile-card-header">
        <h3><i class="fas fa-chess-king"></i> Elite Units</h3>
    </div>
    <div class="mobile-card-content" style="display: block;">
        <?php if (!empty($generals)): ?>
            <ul class="mobile-stats-list">
                <?php foreach ($generals as $general): ?>
                    <li>
                        <span><i class="fas fa-user-tie"></i> <?= htmlspecialchars($general['name'] ?? 'Unknown') ?></span>
                        <strong>Lvl <?= $general['level'] ?? 1 ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-center text-muted">No elite units recruited yet.</p>
            <a href="/generals" class="btn">Recruit Generals</a>
        <?php endif; ?>
    </div>
</div>
-->
