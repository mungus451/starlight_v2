<?php
// =================================================================
// MOBILE "COMMAND CONSOLE" VIEW
// =================================================================
?>
<div class="mobile-content">
    <div class="player-hub">
        <?php if ($user->profile_picture_url): ?>
            <img src="/serve/avatar/<?= htmlspecialchars($user->profile_picture_url) ?>" alt="Avatar" class="player-avatar large">
        <?php else: ?>
            <svg class="player-avatar large" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
            </svg>
        <?php endif; ?>
        <h2 class="player-name"><?= htmlspecialchars($user->characterName) ?></h2>
        <?php if ($user->alliance_id): ?>
            <a href="/alliance/profile/<?= $user->alliance_id ?>" class="player-alliance-link">View Alliance</a>
        <?php else: ?>
            <a href="/alliance/list" class="player-alliance-link">Find an Alliance</a>
        <?php endif; ?>
        <div class="key-stats-grid">
            <div class="key-stat">
                <span class="stat-label">Level</span>
                <strong class="stat-value"><?= $stats->level ?></strong>
            </div>
            <div class="key-stat">
                <span class="stat-label">Citizens</span>
                <strong class="stat-value"><?= number_format($resources->untrained_citizens) ?></strong>
            </div>
            <div class="key-stat">
                <span class="stat-label">Turns</span>
                <strong class="stat-value"><?= number_format($stats->attack_turns) ?></strong>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="tabs-nav mb-3">
        <a class="tab-link" data-tab="overview">Overview</a>
        <a class="tab-link" data-tab="economics">Economics</a>
        <a class="tab-link" data-tab="military">Military</a>
    </div>
    
    <a href="/structures" class="btn btn-secondary d-block mb-4">Manage Structures</a>

    <div class="tab-content-container">
        <?php require __DIR__ . '/partials/overview.php'; ?>
        <?php require __DIR__ . '/partials/economics.php'; ?>
        <?php require __DIR__ . '/partials/military.php'; ?>
    </div>
</div>

<script src="/js/utils.js?v=<?= time() ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        StarlightUtils.initTabs({
            defaultTab: 'overview'
        });
    });
</script>
