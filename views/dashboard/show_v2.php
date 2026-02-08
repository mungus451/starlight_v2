<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\User $user */
/* @var \App\Models\Entities\UserResource $resources */
/* @var \App\Models\Entities\UserStats $stats */
/* @var \App\Models\Entities\UserStructure $structures */
/* @var array $incomeBreakdown */
/* @var array $offenseBreakdown */
/* @var array $defenseBreakdown */
/* @var array $spyBreakdown */
/* @var array $sentryBreakdown */
?>

<div class="dashboard-v2-grid">
    <!-- Column 1: Player Status & Vitals -->
    <div class="dashboard-v2-col">
        <!-- Player Card -->
        <div class="player-card-v2">
            <div class="player-card-v2-header">
                <?php if ($user->profile_picture_url): ?>
                    <img src="/serve/avatar/<?= htmlspecialchars($user->profile_picture_url) ?>" alt="Avatar" class="player-card-v2-avatar">
                <?php else: ?>
                    <svg class="player-card-v2-avatar player-avatar-svg" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                    </svg>
                <?php endif; ?>
                <div class="player-card-v2-info">
                    <h2><?= htmlspecialchars($user->characterName) ?></h2>
                    <div>
                        <span class="badge bg-glass border-glass text-neon-blue"><?= htmlspecialchars($user->race ?? 'Unknown Race') ?></span>
                        <span class="badge bg-glass border-glass text-accent-2"><?= htmlspecialchars($user->class ?? 'Unknown Class') ?></span>
                    </div>
                </div>
            </div>
            <div class="player-card-v2-stats">
                <div class="player-card-v2-stat">
                    <span>Level</span>
                    <strong><?= $stats->level ?></strong>
                </div>
                <div class="player-card-v2-stat">
                    <span>Net Worth</span>
                    <strong><?= $formatted_net_worth ?></strong>
                </div>
                <div class="player-card-v2-stat">
                    <span>Alliance</span>
                    <strong>
                        <?php if ($user->alliance_id): ?>
                            <a href="/alliance/profile/<?= $user->alliance_id ?>">View</a>
                        <?php else: ?>
                            <a href="/alliance/list">Find</a>
                        <?php endif; ?>
                    </strong>
                </div>
                <div class="player-card-v2-stat">
                    <span>Attack Turns</span>
                    <strong><?= number_format($stats->attack_turns) ?></strong>
                </div>
            </div>
        </div>

        <!-- Vitals Card -->
        <div class="data-card vitals-card">
            <div class="card-header">
                <h3>Vitals</h3>
            </div>
            <ul class="card-stats-list">
                <li>
                    <span><i class="fas fa-money-bill-wave fa-fw me-2 text-success"></i>Credits</span>
                    <span class="value-green"><?= number_format($resources->credits) ?></span>
                </li>
                <li>
                    <span><i class="fas fa-users fa-fw me-2 text-info"></i>Citizens</span>
                    <span><?= number_format($resources->untrained_citizens) ?></span>
                </li>
                <li>
                    <span><i class="fas fa-hard-hat fa-fw me-2 text-warning"></i>Workers</span>
                    <span><?= number_format($resources->workers) ?></span>
                </li>
                <li class="border-top mt-2 pt-2">
                    <span><i class="fas fa-chart-line fa-fw me-2 text-success"></i>Income / Turn</span>
                    <span class="value-green">+ <?= number_format($incomeBreakdown['total_credit_income']) ?></span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Column 2: Action Center -->
    <div class="dashboard-v2-col">
        <div class="data-card">
            <div class="card-header">
                <h3>Action Center</h3>
            </div>
            <div class="card-body">
                <h4 class="breakdown-title text-neon-blue">Recommended Actions</h4>
                <ul class="card-stats-list">
                    <?php if ($stats->level_up_points > 0): ?>
                        <li>
                            <span>You have unspent level up points.</span>
                            <a href="/level-up" class="btn-submit btn-accent">Level Up</a>
                        </li>
                    <?php endif; ?>
                    <li>
                        <span>Your economy is ready for an upgrade.</span>
                        <a href="/structures" class="btn-submit btn-accent">Upgrade</a>
                    </li>
                    <li>
                        <span>You have idle attack turns.</span>
                        <a href="/battle" class="btn-submit btn-accent">Attack</a>
                    </li>
                </ul>

                <hr class="glow-divider my-3">

                <h4 class="breakdown-title text-neon-blue">Recent Events</h4>
                <ul class="card-stats-list">
                    <?php foreach ($critical_alerts as $alert): ?>
                        <li>
                            <span><?= htmlspecialchars($alert->title) ?></span>
                            <a href="/notifications" class="card-toggle">View</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Column 3: Military & Power Overview -->
    <div class="dashboard-v2-col">
        <div class="data-card">
            <div class="card-header">
                <h3>Military Overview</h3>
            </div>
            <ul class="card-stats-list">
                <li>
                    <span>Offense Power</span>
                    <span class="value-red value-total"><?= number_format($offenseBreakdown['total']) ?></span>
                </li>
                <li>
                    <span>Defense Rating</span>
                    <span class="value-blue value-total"><?= number_format($defenseBreakdown['total']) ?></span>
                </li>
                <li>
                    <span>Spy Power</span>
                    <span class="value-green"><?= number_format($spyBreakdown['total']) ?></span>
                </li>
                <li>
                    <span>Sentry Power</span>
                    <span class="value-green"><?= number_format($sentryBreakdown['total']) ?></span>
                </li>
            </ul>
        </div>
        <div class="data-card">
            <div class="card-header">
                <h3>Military Units</h3>
                <a href="/training" class="card-toggle">Train</a>
            </div>
            <ul class="card-stats-list">
                <li><span>Soldiers</span> <span><?= number_format($resources->soldiers) ?></span></li>
                <li><span>Guards</span> <span><?= number_format($resources->guards) ?></span></li>
                <li><span>Spies</span> <span><?= number_format($resources->spies) ?></span></li>
                <li><span>Sentries</span> <span><?= number_format($resources->sentries) ?></span></li>
            </ul>
        </div>

        <?php if (!empty($activeEffects)): ?>
        <div class="data-card">
            <div class="card-header">
                <h3 style="color: var(--accent);">Active Effects</h3>
            </div>
            <ul class="data-list" style="margin-top: 0.5rem;">
                <?php foreach ($activeEffects as $effect): ?>
                    <li class="data-item" style="justify-content: space-between; padding: 0.75rem 1rem;">
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <i class="fas <?= $effect['ui_icon'] ?> <?= $effect['ui_color'] ?>" style="font-size: 1.2rem; width: 25px; text-align: center;"></i>
                            <span style="font-weight: 600; color: #fff;"><?= $effect['ui_label'] ?></span>
                        </div>
                        <span style="font-family: monospace; font-size: 1rem; color: var(--muted);">
                            <i class="far fa-clock" style="margin-right: 5px;"></i> <?= $effect['formatted_time_left'] ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>