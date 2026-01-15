<?php
// --- Mobile Embassy View (Full Server-Side Render) ---
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">Embassy</h1>
        <p style="color: var(--mobile-text-secondary); font-size: 0.9rem; margin: 0.5rem 1rem 0 1rem; text-align: center;">Manage active directives and enact powerful new edicts.</p>
    </div>

    <!-- Embassy Status Card -->
    <div class="mobile-card">
        <div class="mobile-card-header">
            <h3><i class="fas fa-landmark"></i> Embassy Status</h3>
        </div>
        <div class="mobile-card-content" style="display: block;">
            <ul class="mobile-stats-list">
                <li><span><i class="fas fa-building"></i> Embassy Level</span> <strong><?= $embassy_level ?></strong></li>
                <li><span><i class="fas fa-check-circle"></i> Edict Slots Used</span> <strong><?= $slots_used ?> / <?= $max_slots ?></strong></li>
            </ul>
        </div>
    </div>

    <!-- Main Tab Navigation -->
    <div class="tabs-nav mb-3">
        <a class="tab-link" data-tab="active-directives">Active Directives</a>
        <a class="tab-link" data-tab="available-edicts">Available Edicts</a>
    </div>

    <!-- Main Content Panes -->
    <div class="tab-content-container">
        <?php require __DIR__ . '/partials/active-directives.php'; ?>
        <?php require __DIR__ . '/partials/available-edicts.php'; ?>
    </div>
</div>

<script src="/js/utils.js?v=<?= time() ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        StarlightUtils.initTabs({
            defaultTab: 'active-directives'
        });
    });
</script>
