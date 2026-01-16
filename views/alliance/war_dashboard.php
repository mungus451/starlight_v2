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
    /* Base Stat Cards (Overview Tab) */
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

    /* HUD Styling (Performance Tab) */
    .hud-section-header {
        font-family: 'Courier New', monospace;
        text-transform: uppercase;
        letter-spacing: 2px;
        border-bottom: 2px solid;
        padding-bottom: 5px;
        margin-bottom: 15px;
        text-shadow: 0 0 5px currentColor;
    }
    .hud-panel {
        background: rgba(10, 10, 12, 0.85);
        border: 1px solid #333;
        padding: 10px;
        height: 100%;
        position: relative;
        overflow: hidden;
        font-family: 'Courier New', monospace;
    }
    /* Friendly Theme */
    .hud-friendly {
        border-color: rgba(0, 243, 255, 0.3);
        box-shadow: 0 0 10px rgba(0, 243, 255, 0.05);
    }
    .hud-friendly::before {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 100%; height: 2px;
        background: #00f3ff;
        box-shadow: 0 0 8px #00f3ff;
    }
    /* Hostile Theme */
    .hud-hostile {
        border-color: rgba(255, 50, 50, 0.3);
        box-shadow: 0 0 10px rgba(255, 50, 50, 0.05);
    }
    .hud-hostile::before {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 100%; height: 2px;
        background: #ff3333;
        box-shadow: 0 0 8px #ff3333;
    }
    
    .hud-card-title {
        font-size: 0.9rem;
        font-weight: bold;
        text-transform: uppercase;
        margin-bottom: 10px;
        text-align: center;
        letter-spacing: 1px;
    }
    
    .hud-table {
        width: 100%;
        font-size: 0.85rem;
        border-collapse: collapse;
    }
    .hud-table td {
        padding: 4px 6px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }
    .hud-table tr:last-child td {
        border-bottom: none;
    }
    .hud-rank { color: #666; width: 20px; }
    .hud-val { text-align: right; font-weight: bold; }
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
        <!-- ROW 1: FRIENDLY FORCES -->
        <h4 class="hud-section-header text-neon-blue">
            <i class="fas fa-shield-alt me-2"></i>ALLIANCE ASSETS [<?= htmlspecialchars($yourAlliance->tag) ?>]
        </h4>
        <div class="row mb-5">
            <!-- Vanguard -->
            <div class="col-md-3">
                <div class="hud-panel hud-friendly">
                    <div class="hud-card-title text-warning">Vanguard (Dmg)</div>
                    <table class="hud-table">
                        <?php if(empty($leaderboard['your_alliance']['vanguard'])): ?>
                            <tr><td class="text-muted text-center">No Data</td></tr>
                        <?php else: ?>
                            <?php foreach($leaderboard['your_alliance']['vanguard'] as $idx => $entry): ?>
                            <tr>
                                <td class="hud-rank">#<?= $idx+1 ?></td>
                                <td><?= htmlspecialchars($entry['character_name']) ?></td>
                                <td class="hud-val text-warning"><?= number_format($entry['total_value']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            <!-- Reapers -->
            <div class="col-md-3">
                <div class="hud-panel hud-friendly">
                    <div class="hud-card-title text-danger">Reapers (Kills)</div>
                    <table class="hud-table">
                        <?php if(empty($leaderboard['your_alliance']['reapers'])): ?>
                            <tr><td class="text-muted text-center">No Data</td></tr>
                        <?php else: ?>
                            <?php foreach($leaderboard['your_alliance']['reapers'] as $idx => $entry): ?>
                            <tr>
                                <td class="hud-rank">#<?= $idx+1 ?></td>
                                <td><?= htmlspecialchars($entry['character_name']) ?></td>
                                <td class="hud-val text-danger"><?= number_format($entry['total_value']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            <!-- Marauders -->
            <div class="col-md-3">
                <div class="hud-panel hud-friendly">
                    <div class="hud-card-title text-success">Marauders (Gold)</div>
                    <table class="hud-table">
                        <?php if(empty($leaderboard['your_alliance']['marauders'])): ?>
                            <tr><td class="text-muted text-center">No Data</td></tr>
                        <?php else: ?>
                            <?php foreach($leaderboard['your_alliance']['marauders'] as $idx => $entry): ?>
                            <tr>
                                <td class="hud-rank">#<?= $idx+1 ?></td>
                                <td><?= htmlspecialchars($entry['character_name']) ?></td>
                                <td class="hud-val text-success"><?= number_format($entry['total_value']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            <!-- Phantoms -->
            <div class="col-md-3">
                <div class="hud-panel hud-friendly">
                    <div class="hud-card-title text-info">Phantoms (Intel)</div>
                    <table class="hud-table">
                        <?php if(empty($leaderboard['your_alliance']['phantoms'])): ?>
                            <tr><td class="text-muted text-center">No Data</td></tr>
                        <?php else: ?>
                            <?php foreach($leaderboard['your_alliance']['phantoms'] as $idx => $entry): ?>
                            <tr>
                                <td class="hud-rank">#<?= $idx+1 ?></td>
                                <td><?= htmlspecialchars($entry['character_name']) ?></td>
                                <td class="hud-val text-info"><?= number_format($entry['total_value']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

        <!-- ROW 2: HOSTILE FORCES -->
        <h4 class="hud-section-header text-danger">
            <i class="fas fa-crosshairs me-2"></i>THREAT ASSESSMENT [<?= htmlspecialchars($opponentAlliance->tag) ?>]
        </h4>
        <div class="row">
            <!-- Vanguard -->
            <div class="col-md-3">
                <div class="hud-panel hud-hostile">
                    <div class="hud-card-title text-warning">Vanguard (Dmg)</div>
                    <table class="hud-table">
                        <?php if(empty($leaderboard['opponent_alliance']['vanguard'])): ?>
                            <tr><td class="text-muted text-center">No Intel</td></tr>
                        <?php else: ?>
                            <?php foreach($leaderboard['opponent_alliance']['vanguard'] as $idx => $entry): ?>
                            <tr>
                                <td class="hud-rank">#<?= $idx+1 ?></td>
                                <td><?= htmlspecialchars($entry['character_name']) ?></td>
                                <td class="hud-val text-warning"><?= number_format($entry['total_value']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            <!-- Reapers -->
            <div class="col-md-3">
                <div class="hud-panel hud-hostile">
                    <div class="hud-card-title text-danger">Reapers (Kills)</div>
                    <table class="hud-table">
                        <?php if(empty($leaderboard['opponent_alliance']['reapers'])): ?>
                            <tr><td class="text-muted text-center">No Intel</td></tr>
                        <?php else: ?>
                            <?php foreach($leaderboard['opponent_alliance']['reapers'] as $idx => $entry): ?>
                            <tr>
                                <td class="hud-rank">#<?= $idx+1 ?></td>
                                <td><?= htmlspecialchars($entry['character_name']) ?></td>
                                <td class="hud-val text-danger"><?= number_format($entry['total_value']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            <!-- Marauders -->
            <div class="col-md-3">
                <div class="hud-panel hud-hostile">
                    <div class="hud-card-title text-success">Marauders (Gold)</div>
                    <table class="hud-table">
                        <?php if(empty($leaderboard['opponent_alliance']['marauders'])): ?>
                            <tr><td class="text-muted text-center">No Intel</td></tr>
                        <?php else: ?>
                            <?php foreach($leaderboard['opponent_alliance']['marauders'] as $idx => $entry): ?>
                            <tr>
                                <td class="hud-rank">#<?= $idx+1 ?></td>
                                <td><?= htmlspecialchars($entry['character_name']) ?></td>
                                <td class="hud-val text-success"><?= number_format($entry['total_value']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            <!-- Phantoms -->
            <div class="col-md-3">
                <div class="hud-panel hud-hostile">
                    <div class="hud-card-title text-info">Phantoms (Intel)</div>
                    <table class="hud-table">
                        <?php if(empty($leaderboard['opponent_alliance']['phantoms'])): ?>
                            <tr><td class="text-muted text-center">No Intel</td></tr>
                        <?php else: ?>
                            <?php foreach($leaderboard['opponent_alliance']['phantoms'] as $idx => $entry): ?>
                            <tr>
                                <td class="hud-rank">#<?= $idx+1 ?></td>
                                <td><?= htmlspecialchars($entry['character_name']) ?></td>
                                <td class="hud-val text-info"><?= number_format($entry['total_value']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tabs with persistence
    StarlightUtils.initTabs({
        storageKey: 'war_dashboard_tab_<?= $war->id ?>',
        defaultTab: 'tab-overview'
    });
});
</script>