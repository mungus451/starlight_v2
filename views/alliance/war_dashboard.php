<?php
/**
 * War Dashboard
 *
 * @var array $overview
 * @var array $battleLogs
 * @var array $spyLogs
 * @var array $leaderboard
 * @var string $csrf_token
 */

$war = $overview['war'];
$yourAlliance = $overview['yourAlliance'];
$opponentAlliance = $overview['opponentAlliance'];
$warAggregates = $overview['warAggregates'];

?>

<link rel="stylesheet" href="/css/war_table.css?v=<?= time() ?>">
<style>
    .stat-card {
        background: rgba(13, 17, 23, 0.7);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 1rem;
        border-radius: 4px;
        text-align: center;
    }
    .stat-card .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--accent-orange);
    }
    .stat-card .stat-label {
        font-size: 0.8rem;
        text-transform: uppercase;
        color: #a8afd4;
    }
</style>

<div class="container-fluid py-4">
    <!-- PAGE HEADER -->
    <h1 class="text-neon-blue mb-4"><i class="fas fa-chart-line me-3"></i>War Dashboard</h1>

    <!-- SITUATION REPORT -->
    <div class="dashboard-card war-situation-report mb-4">
        </div>

    <!-- TABS -->
    <div class="tabs-nav mb-4 justify-content-center">
        <a class="tab-link active" data-tab="tab-overview">Overview</a>
        <a class="tab-link" data-tab="tab-battle-log">Battle Log</a>
        <a class="tab-link" data-tab="tab-espionage">Intel & Espionage</a>
        <a class="tab-link" data-tab="tab-leaderboard">Performance</a>
    </div>

    <!-- TAB CONTENT -->
    <div id="tab-overview" class="tab-content active">
        <div class="row">
            <div class="col-md-6">
                <div class="dashboard-card war-table-card mb-4">
                    <h5 class="text-center">Our Strategic Objectives</h5>
                    <!-- Loop through your objectives -->
                </div>
            </div>
            <div class="col-md-6">
                <div class="dashboard-card war-table-card mb-4">
                    <h5 class="text-center">Enemy Strategic Objectives</h5>
                    <!-- Loop through enemy objectives -->
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3 col-6 mb-3">
                <div class="stat-card h-100">
                    <div class="stat-value"><?= number_format($warAggregates['total_attacks']) ?></div>
                    <div class="stat-label">Attacks Exchanged</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="stat-card h-100">
                    <div class="stat-value"><?= number_format($warAggregates['total_plunder']) ?></div>
                    <div class="stat-label">Credits Plundered</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="stat-card h-100">
                    <div class="stat-value"><?= number_format($warAggregates['total_units_killed']) ?></div>
                    <div class="stat-label">Units Destroyed</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="stat-card h-100">
                    <div class="stat-value"><?= number_format($warAggregates['total_structure_damage']) ?></div>
                    <div class="stat-label">Structure Damage</div>
                </div>
            </div>
        </div>
    </div>

    <div id="tab-battle-log" class="tab-content">
        <div class="dashboard-card">
            <h4 class="mb-3">Battle Log</h4>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle">
                    <!-- Table header -->
                    <thead>
                        <tr>
                            <th>Attacker</th>
                            <th>Defender</th>
                            <th>Victor</th>
                            <th>Plunder</th>
                            <th>Units Lost</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($battleLogs['logs'] as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log->attacker_name) ?></td>
                                <td><?= htmlspecialchars($log->defender_name) ?></td>
                                <td><?= htmlspecialchars($log->victor_name) ?></td>
                                <td><?= number_format($log->credits_plundered) ?></td>
                                <td><?= number_format($log->units_killed) ?></td>
                                <td><a href="/battle/report/<?= $log->battle_report_id ?>" class="btn btn-sm btn-outline-primary">Debrief</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination placeholder -->
        </div>
    </div>

    <div id="tab-espionage" class="tab-content">
        <div class="dashboard-card">
            <h4 class="mb-3">Intel & Espionage Log</h4>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle">
                    <!-- Table header -->
                    <thead>
                        <tr>
                            <th>Operator</th>
                            <th>Target</th>
                            <th>Operation</th>
                            <th>Result</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($spyLogs['logs'] as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log->attacker_name) ?></td>
                                <td><?= htmlspecialchars($log->defender_name) ?></td>
                                <td><?= htmlspecialchars($log->operation_type) ?></td>
                                <td><?= htmlspecialchars($log->result) ?></td>
                                <td>
                                    <?php if($log->spy_report_id): ?>
                                        <a href="/spy/report/<?= $log->spy_report_id ?>" class="btn btn-sm btn-outline-primary">Intel</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination placeholder -->
        </div>
    </div>

    <div id="tab-leaderboard" class="tab-content">
        <div class="dashboard-card">
            <h4 class="mb-3">Performance Leaderboard</h4>
            <p class="text-muted">Coming soon.</p>
        </div>
    </div>
</div>