<?php
/**
 * @var \App\Models\Entities\User $user
 * @var \App\Models\Entities\UserResource $resources
 * @var \App\Models\Entities\UserStats $stats
 * @var array $activeEffects
 * @var \App\Models\Entities\Notification[] $critical_alerts
 * @var array $incomeBreakdown
 * @var array $advisor_suggestions
 * @var array $threat_and_opportunity
 * @var string $csrf_token
 */

use App\Core\StarlightUtils;

// Calculate total battles and spy missions from the stats object
$total_battles = ($stats->battles_won ?? 0) + ($stats->battles_lost ?? 0);
$total_spy_missions = ($stats->spy_successes ?? 0) + ($stats->spy_failures ?? 0);

// Helper function to map notification types to UI elements
function get_alert_ui_details(string $type): array {
    return match ($type) {
        'attack_report' => ['icon' => 'fa-skull-crossbones', 'class' => 'alert-danger'],
        'spy_report' => ['icon' => 'fa-user-secret', 'class' => 'alert-info'],
        'construction_complete' => ['icon' => 'fa-check-circle', 'class' => 'alert-success'],
        'system' => ['icon' => 'fa-info-circle', 'class' => 'alert-info'],
        default => ['icon' => 'fa-bell', 'class' => 'alert-info'],
    };
}

// Calculate Income vs Upkeep for the visual bar
$base_income = $incomeBreakdown['base_production'] ?? 0;
$total_income = $incomeBreakdown['total_credit_income'] ?? 0;
$total_upkeep = $base_income > $total_income ? $base_income - $total_income : 0; // Simplified for display
$income_pct = $base_income > 0 ? ($total_income / ($base_income + $total_upkeep)) * 100 : 100;
$upkeep_pct = $base_income > 0 ? ($total_upkeep / ($base_income + $total_upkeep)) * 100 : 0;

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

    <!-- Panel 2: Economic Summary -->
    <div class="dashboard-card economy-card">
        <h3><i class="fas fa-chart-line text-accent"></i> Economic Summary</h3>
        <div class="economy-grid">
            <!-- Credits -->
            <div class="economy-item">
                <span class="economy-label">Credits</span>
                <span class="economy-value"><?= StarlightUtils::format_short($resources->credits) ?></span>
                <span class="economy-flow <?= ($incomeBreakdown['total_credit_income'] >= 0) ? 'positive' : 'negative' ?>">
                    <?= StarlightUtils::format_short($incomeBreakdown['total_credit_income'], true) ?>/turn
                </span>
            </div>
            <!-- Crystals -->
            <div class="economy-item">
                <span class="economy-label">Crystals</span>
                <span class="economy-value"><?= StarlightUtils::format_short($resources->research_data) ?></span>
                <span class="economy-flow positive">
                    <?= StarlightUtils::format_short($incomeBreakdown['research_data_income'], true) ?>/turn
                </span>
            </div>
            <!-- Dark Matter -->
            <div class="economy-item">
                <span class="economy-label">Dark Matter</span>
                <span class="economy-value"><?= StarlightUtils::format_short($resources->dark_matter) ?></span>
                <span class="economy-flow positive">
                    <?= StarlightUtils::format_short($incomeBreakdown['dark_matter_income'], true) ?>/turn
                </span>
            </div>
        </div>
        <div class="income-breakdown-bar">
            <div class="income-bar" style="width: <?= $income_pct ?>%;">
                <span>Income</span>
            </div>
            <div class="upkeep-bar" style="width: <?= $upkeep_pct ?>%;">
                <span>Upkeep</span>
            </div>
        </div>
    </div>

    <!-- Panel 3: Advisor Suggestions -->
    <div class="dashboard-card advisor-card">
        <h3><i class="fas fa-lightbulb text-accent"></i> Advisor Suggestions</h3>
        <?php if (!empty($advisor_suggestions)): ?>
            <ul class="advisor-list">
                <?php foreach ($advisor_suggestions as $suggestion): ?>
                    <li class="advisor-item">
                        <i class="fas <?= htmlspecialchars($suggestion['icon']) ?>"></i>
                        <a href="<?= htmlspecialchars($suggestion['link']) ?>"><?= htmlspecialchars($suggestion['text']) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="no-suggestions">No suggestions at this time. Your empire is running smoothly!</p>
        <?php endif; ?>
    </div>
    
    <!-- Panel 4: Threat & Opportunity -->
    <div class="dashboard-card threat-card">
        <h3><i class="fas fa-crosshairs text-accent"></i> Threat & Opportunity</h3>
        <ul class="threat-list">
            <li class="threat-item">
                <i class="fas fa-user-ninja"></i>
                <div>
                    <span class="threat-label">Nearest Rival</span>
                    <?php if ($threat_and_opportunity['rival']): ?>
                        <span class="threat-value">
                            <?= htmlspecialchars($threat_and_opportunity['rival']['character_name']) ?>
                            <small>(<?= StarlightUtils::format_short($threat_and_opportunity['rival']['net_worth']) ?> NW)</small>
                        </span>
                    <?php else: ?>
                        <span class="threat-value">You are at the top!</span>
                    <?php endif; ?>
                </div>
            </li>
            <li class="threat-item">
                <i class="fas fa-flag"></i>
                <div>
                    <span class="threat-label">Alliance Status</span>
                    <?php if ($threat_and_opportunity['active_war']):
                        $war = $threat_and_opportunity['active_war'];
                        $opponent = ($war->declarer_alliance_id === $user->alliance_id) ? $war->defender_name : $war->declarer_name;
                    ?>
                        <span class="threat-value hostile">At war with <?= htmlspecialchars($opponent) ?></span>
                    <?php else: ?>
                        <span class="threat-value">No active wars.</span>
                    <?php endif; ?>
                </div>
            </li>
            <li class="threat-item">
                <i class="fas fa-bullseye"></i>
                <div>
                    <span class="threat-label">Highest Bounty</span>
                     <?php if ($threat_and_opportunity['highest_bounty']):
                        $bounty = $threat_and_opportunity['highest_bounty'];
                    ?>
                        <span class="threat-value">
                            <?= StarlightUtils::format_short($bounty['amount']) ?> Crystals on <?= htmlspecialchars($bounty['target_name']) ?>
                        </span>
                    <?php else: ?>
                        <span class="threat-value">No active bounties.</span>
                    <?php endif; ?>
                </div>
            </li>
        </ul>
    </div>

    <!-- Panel 5: Active Modifiers -->
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

    <!-- Panel 6: Critical Alerts -->
    <div class="dashboard-card alerts-card">
        <h3><i class="fas fa-exclamation-triangle text-accent"></i> Critical Alerts</h3>
        <?php if (!empty($critical_alerts)): ?>
            <ul class="alerts-list">
                <?php foreach ($critical_alerts as $alert): ?>
                    <?php $ui = get_alert_ui_details($alert->type); ?>
                    <li class="alert-item <?= htmlspecialchars($ui['class']) ?>">
                        <i class="fas <?= htmlspecialchars($ui['icon']) ?>"></i>
                        <a href="<?= htmlspecialchars($alert->link ?? '/notifications') ?>" class="alert-link">
                            <?= htmlspecialchars($alert->title) ?>
                        </a>
                        <span class="alert-time"><?= StarlightUtils::time_ago($alert->created_at) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="no-alerts">No recent alerts.</p>
        <?php endif; ?>
    </div>
</div>
