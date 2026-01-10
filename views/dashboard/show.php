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

// System Status logic
$status_label = "Systems Nominal";
$status_class = "";
if (!empty($critical_alerts)) {
    $status_label = "Alert Active";
    $status_class = "warning";
}
if ($threat_and_opportunity['active_war']) {
    $status_label = "Combat Protocol";
    $status_class = "critical";
}

?>

<link rel="stylesheet" href="/css/dashboard_v2.css?v=<?= time() ?>">

<div class="command-bridge-container <?= $status_class === 'critical' ? 'war-mode' : '' ?>">
    <!-- Visual Atmosphere -->
    <div class="tactical-grid-overlay"></div>

    <!-- 1. HOLO-DECK PLAYER STATUS -->
    <div class="holo-deck-panel">
        <div class="holo-planet-container">
            <div class="holo-planet"></div>
            <div class="holo-ring"></div>
            <?php if ($user->profile_picture_url): ?>
                <img src="/serve/avatar/<?= htmlspecialchars($user->profile_picture_url) ?>" alt="Avatar" class="holo-avatar-overlay">
            <?php else: ?>
                <div class="holo-avatar-overlay d-flex align-items-center justify-content-center">
                    <i class="fas fa-user-astronaut" style="font-size: 2.5rem; color: rgba(0, 243, 255, 0.7);"></i>
                </div>
            <?php endif; ?>
        </div>
        <div class="holo-stats">
            <h2 class="holo-title glitch-text" data-text="<?= htmlspecialchars($user->characterName) ?>"><?= htmlspecialchars($user->characterName) ?></h2>
            <div class="holo-subtitle">
                <span>RANK: COMMANDER</span>
                <span>SECTOR: 0-ALPHA</span>
                <div class="system-status <?= $status_class ?>">
                    <i class="fas fa-microchip"></i> <?= $status_label ?>
                    <?php if ($status_class === 'critical'): ?>
                        <a href="/alliance/war" class="ms-2 badge bg-danger text-white text-decoration-none">
                            <i class="fas fa-external-link-alt"></i> WAR_ROOM
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="d-none d-md-block" style="text-align: right;">
            <div class="text-muted small mb-1 font-monospace">NET_WORTH_VAL</div>
            <div class="h4 mb-0 text-neon-blue font-monospace"><?= number_format($stats->net_worth) ?></div>
        </div>
    </div>

    <!-- 2. RESOURCE FLUX (PIPES) -->
    <div class="flux-panel">
        <!-- Credits -->
        <div class="flux-row credits">
            <div class="flux-icon text-warning"><i class="fas fa-coins"></i></div>
            <div class="flux-bar-container">
                <div class="flux-fill"></div>
                <div class="flux-stream"></div>
            </div>
            <div class="flux-value text-warning"><?= StarlightUtils::format_short($resources->credits) ?></div>
            <div class="flux-rate <?= ($incomeBreakdown['total_credit_income'] >= 0) ? '' : 'negative' ?>">
                <?= ($incomeBreakdown['total_credit_income'] >= 0 ? '+' : '') ?><?= StarlightUtils::format_short($incomeBreakdown['total_credit_income']) ?>
            </div>
        </div>
        <!-- Crystals -->
        <div class="flux-row crystals">
            <div class="flux-icon text-neon-blue"><i class="fas fa-gem"></i></div>
            <div class="flux-bar-container">
                <div class="flux-fill"></div>
                <div class="flux-stream"></div>
            </div>
            <div class="flux-value text-neon-blue"><?= StarlightUtils::format_short($resources->naquadah_crystals) ?></div>
            <div class="flux-rate">
                +<?= StarlightUtils::format_short($incomeBreakdown['research_data_income'] ?? 0) ?>
            </div>
        </div>
        <!-- Dark Matter -->
        <div class="flux-row dm">
            <div class="flux-icon" style="color: #bc13fe;"><i class="fas fa-atom"></i></div>
            <div class="flux-bar-container">
                <div class="flux-fill"></div>
                <div class="flux-stream"></div>
            </div>
            <div class="flux-value" style="color: #bc13fe;"><?= StarlightUtils::format_short($resources->dark_matter) ?></div>
            <div class="flux-rate">
                +<?= StarlightUtils::format_short($incomeBreakdown['dark_matter_income'] ?? 0) ?>
            </div>
        </div>
    </div>

    <!-- 3. MODULAR BRIDGE WIDGETS -->
    <div class="bridge-modules-grid">
        
        <!-- Threat Monitor Module -->
        <div class="bridge-module">
            <div class="module-header">
                <span class="module-title">Threat Assessment</span>
                <div class="module-status-light <?= $status_class === 'critical' ? 'bg-danger' : '' ?>"></div>
            </div>
            <div class="module-content">
                <?php if ($threat_and_opportunity['active_war']): ?>
                    <div class="threat-monitor">
                        <span class="threat-level critical">WAR ACTIVE</span>
                        <p class="text-muted small">Engagement with hostiles detected.</p>
                    </div>
                <?php else: ?>
                    <div class="threat-monitor">
                        <span class="threat-level">SECURE</span>
                        <p class="text-muted small">No active alliance wars in sector.</p>
                    </div>
                <?php endif; ?>

                <ul class="list-unstyled mb-0">
                    <li class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Nearest Rival:</span>
                        <span class="text-light"><?= $threat_and_opportunity['rival'] ? htmlspecialchars($threat_and_opportunity['rival']['character_name']) : 'None' ?></span>
                    </li>
                    <li class="d-flex justify-content-between small">
                        <span class="text-muted">Active Bounties:</span>
                        <span class="text-neon-blue"><?= $threat_and_opportunity['highest_bounty'] ? StarlightUtils::format_short($threat_and_opportunity['highest_bounty']['amount']) : '0' ?></span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Advisor Suggestion Module -->
        <div class="bridge-module">
            <div class="module-header">
                <span class="module-title">Mission Briefing</span>
                <div class="module-status-light"></div>
            </div>
            <div class="module-content">
                <?php if (!empty($advisor_suggestions)): ?>
                    <ul class="advisor-list">
                        <?php foreach (array_slice($advisor_suggestions, 0, 3) as $suggestion): ?>
                            <li class="advisor-item" style="border-bottom: 1px solid rgba(255,255,255,0.05); padding: 8px 0;">
                                <i class="fas <?= htmlspecialchars($suggestion['icon']) ?> text-accent me-2"></i>
                                <a href="<?= htmlspecialchars($suggestion['link']) ?>" class="small"><?= htmlspecialchars($suggestion['text']) ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted small">All systems operational. No immediate action required.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modifiers Module -->
        <div class="bridge-module">
            <div class="module-header">
                <span class="module-title">System Modifiers</span>
                <div class="module-status-light"></div>
            </div>
            <div class="module-content">
                <?php if (!empty($activeEffects)): ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach (array_slice($activeEffects, 0, 3) as $effect): ?>
                            <li class="d-flex justify-content-between align-items-center mb-2 small">
                                <span class="text-light"><i class="fas <?= htmlspecialchars($effect['ui_icon']) ?> me-2"></i> <?= htmlspecialchars($effect['ui_label']) ?></span>
                                <span class="badge bg-dark border border-secondary text-muted font-monospace"><?= htmlspecialchars($effect['formatted_time_left']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted small">No external modifiers active.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- 4. BATTLE OSCILLOSCOPE -->
    <div class="oscilloscope-panel">
        <div class="oscilloscope-label">LIVE_COMBAT_LOG // WAVEFORM_DATA</div>
        <canvas id="oscilloscope-canvas" class="oscilloscope-canvas"></canvas>
    </div>

</div>

<script>
    window.dashboardData = {
        battles: <?= json_encode($recent_battles ?? []) ?>
    };
</script>
<script src="/js/dashboard_v3.js?v=<?= time() ?>"></script>