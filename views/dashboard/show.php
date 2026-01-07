<?php
/**
 * @var \App\Models\Entities\User $user
 * @var \App\Models\Entities\UserResource $resources
 * @var \App\Models\Entities\UserStats $stats
 * @var array $activeEffects
 * @var string $csrf_token
 */

// Calculate total battles and spy missions from the stats object
$total_battles = ($stats->battles_won ?? 0) + ($stats->battles_lost ?? 0);
$total_spy_missions = ($stats->spy_successes ?? 0) + ($stats->spy_failures ?? 0);
?>

<link rel="stylesheet" href="/css/dashboard_v2.css?v=<?= time() ?>">

<div class="dashboard-v2-grid">

    <!-- Panel 1: Player Info & Stats -->
    <div class="dashboard-card player-card">
        <div class="player-card-header">
            <?php if ($user->profile_picture_url): ?>
                <img src="/serve/avatar/<?= htmlspecialchars($user->profile_picture_url) ?>" alt="Avatar" class="player-avatar">
            <?php else: ?>
                <svg class="player-avatar player-avatar-svg" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                </svg>
            <?php endif; ?>
            <div class="player-title">
                <h2 class="glitch-text" data-text="<?= htmlspecialchars($user->characterName) ?>"><?= htmlspecialchars($user->characterName) ?></h2>
                <p>Level <?= htmlspecialchars($stats->level) ?></p>
            </div>
        </div>
        <div class="player-card-body">
            <div class="stat-item">
                <span class="stat-label">Total Battles Fought</span>
                <span class="stat-value"><?= number_format($total_battles) ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Spy Missions Conducted</span>
                <span class="stat-value"><?= number_format($total_spy_missions) ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Net Worth</span>
                <span class="stat-value"><?= number_format($stats->net_worth) ?></span>
            </div>
        </div>
    </div>

    <!-- Panel 2: Core Resources -->
    <div class="dashboard-card resource-card">
        <h3><i class="fas fa-coins text-accent"></i> Core Resources</h3>
        <div class="resource-grid">
            <div class="resource-item">
                <span class="resource-label">Credits</span>
                <span class="resource-value"><?= number_format($resources->credits) ?></span>
            </div>
            <div class="resource-item">
                <span class="resource-label">Crystals</span>
                <span class="resource-value"><?= number_format($resources->research_data) ?></span>
            </div>
            <div class="resource-item">
                <span class="resource-label">Dark Matter</span>
                <span class="resource-value"><?= number_format($resources->dark_matter) ?></span>
            </div>
        </div>
    </div>

    <!-- Panel 3: Active Modifiers -->
    <div class="dashboard-card modifiers-card">
        <h3><i class="fas fa-bolt text-accent"></i> Active Modifiers</h3>
        <?php if (!empty($activeEffects)): ?>
            <ul class="modifiers-list">
                <?php foreach ($activeEffects as $effect): ?>
                    <li class="modifier-item">
                        <i class="fas <?= htmlspecialchars($effect['ui_icon']) ?> <?= htmlspecialchars($effect['ui_color']) ?>"></i>
                        <span class="modifier-label"><?= htmlspecialchars($effect['ui_label']) ?></span>
                        <span class="modifier-timer">
                            <i class="far fa-clock"></i> <?= htmlspecialchars($effect['formatted_time_left']) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="no-modifiers">No active modifiers.</p>
        <?php endif; ?>
    </div>

    <!-- Panel 4: Critical Alerts -->
    <div class="dashboard-card alerts-card">
        <h3><i class="fas fa-exclamation-triangle text-accent"></i> Critical Alerts</h3>
        <ul class="alerts-list">
            <!-- Note: Backend logic for alerts needs to be implemented. This is a placeholder. -->
            <li class="alert-item alert-danger">
                <i class="fas fa-skull-crossbones"></i>
                <span>Incoming attack from [Player]!</span>
                <span class="alert-timer">00:15:32</span>
            </li>
            <li class="alert-item alert-success">
                <i class="fas fa-check-circle"></i>
                <span>Construction of Metal Mine (Level 10) complete.</span>
            </li>
             <li class="alert-item alert-info">
                <i class="fas fa-info-circle"></i>
                <span>A spy report is available for review.</span>
            </li>
        </ul>
    </div>
</div>
