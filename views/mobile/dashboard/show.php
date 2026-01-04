<?php
// =================================================================
// MOBILE "COMMAND CONSOLE" VIEW
// =================================================================
?>
<!-- Image Lightbox Structure -->
<div id="avatar-lightbox" class="image-lightbox">
    <span class="lightbox-close">&times;</span>
    <img class="lightbox-content" id="lightbox-img">
</div>

<div class="mobile-content">
    <div class="player-hub">
        <a href="#" id="avatar-trigger">
            <?php if ($user->profile_picture_url): ?>
                <img src="/serve/avatar/<?= htmlspecialchars($user->profile_picture_url) ?>" alt="Avatar" class="player-avatar large">
            <?php else: ?>
                <svg class="player-avatar large" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                </svg>
            <?php endif; ?>
        </a>
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
    <div id="dashboard-tabs" class="mobile-tabs">
        <a href="#" class="tab-link active" data-tab="overview">Overview</a>
        <a href="#" class="tab-link" data-tab="economics">Economics</a>
        <a href="#" class="tab-link" data-tab="military">Military</a>
        <a href="#" class="tab-link" data-tab="structures">Structures</a>
    </div>

    <div id="tab-content">
        <?php require __DIR__ . '/partials/overview.php'; ?>
    </div>
</div>
