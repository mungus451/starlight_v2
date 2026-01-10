<?php
/**
 * The War Table - Command & Control Interface
 *
 * @var array $viewer
 * @var bool $canDeclareWar
 * @var int $allianceId
 * @var array $otherAlliances
 * @var array $activeWars
 * @var array $historicalWars
 * @var string $csrf_token
 */

// Placeholder data for your own alliance for the "Versus" screen
$myAlliance = ['tag' => 'STAR', 'name' => 'Starlight Command', 'avatar' => '/img/alliance_avatars/default.png']; // Use a real avatar later

?>

<link rel="stylesheet" href="/css/war_table.css?v=<?= time() ?>">

<div class="container-fluid py-4">
    <!-- PAGE HEADER -->
    <h1 class="text-neon-blue mb-4"><i class="fas fa-satellite-dish me-3"></i>War Room</h1>

    <?php if (!empty($activeWars)): ?>
        <?php foreach ($activeWars as $war): ?>
            <!-- 1. SITUATION REPORT -->
            <div class="dashboard-card war-situation-report mb-4">
                <h4 class="mb-3 text-center"><i class="fas fa-crosshairs text-danger me-2"></i> Active Conflict: [<?= htmlspecialchars($war->declarer_tag) ?>] vs. [<?= htmlspecialchars($war->defender_tag) ?>]</h4>
                <div class="row align-items-center">
                    <!-- Declarer Alliance -->
                    <div class="col-12 col-md-4 text-center">
                        <div class="war-alliance-card">
                            <div class="alliance-tag text-neon-blue">[<?= htmlspecialchars($war->declarer_tag) ?>]</div>
                            <div class="alliance-name"><?= htmlspecialchars($war->declarer_name) ?></div>
                            <div class="war-score text-neon-blue"><?= number_format($war->declarer_score) ?></div>
                        </div>
                    </div>

                    <!-- VS Separator & Timer -->
                    <div class="col-12 col-md-4 text-center">
                        <div class="vs-separator my-3 my-md-0">VS</div>
                        <div class="war-timer-card mt-3">
                            <div class="timer-label">Time Remaining</div>
                            <div class="timer-countdown" data-end-time="<?= htmlspecialchars($war->end_time) ?>">
                                --d --h --m
                            </div>
                        </div>
                    </div>

                    <!-- Defender Alliance -->
                    <div class="col-12 col-md-4 text-center">
                        <div class="war-alliance-card">
                            <div class="alliance-tag text-danger">[<?= htmlspecialchars($war->defender_tag) ?>]</div>
                            <div class="alliance-name"><?= htmlspecialchars($war->defender_name) ?></div>
                            <div class="war-score text-danger"><?= number_format($war->defender_score) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- "THE WAR TABLE" INTERFACE -->
    <?php if ($canDeclareWar): ?>
        <form id="declare-war-form" action="/alliance/war/declare" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="target_alliance_id" id="target_alliance_id_input">

            <!-- STAGE 1: TARGET ACQUISITION -->
            <div id="war-council-stage-1">
                <div class="dashboard-card war-table-card mb-4 text-center">
                    <h4 class="mb-3"><i class="fas fa-crosshairs text-warning me-2"></i> Acquire Target</h4>
                    <div class="target-grid">
                        <?php foreach ($otherAlliances as $target): ?>
                            <div class="target-card" data-alliance-id="<?= $target->id ?>" data-alliance-tag="<?= htmlspecialchars($target->tag) ?>" data-alliance-name="<?= htmlspecialchars($target->name) ?>" data-alliance-avatar="/serve/alliance_avatar/<?= htmlspecialchars($target->profile_picture_url ?? 'default.png') ?>">
                                <img src="/serve/alliance_avatar/<?= htmlspecialchars($target->profile_picture_url ?? 'default.png') ?>" class="hex-avatar mb-2">
                                <div class="alliance-tag">[<?= htmlspecialchars($target->tag) ?>]</div>
                                <div class="alliance-name"><?= htmlspecialchars($target->name) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- STAGE 2: VERSUS CONFIRMATION -->
            <div id="war-council-stage-2" style="display: none;">
                <div class="dashboard-card war-table-card mb-4">
                    <div class="row">
                        <!-- Your Alliance -->
                        <div class="col-5 text-center">
                            <h5 class="text-neon-blue">YOUR FORCES</h5>
                            <img src="/serve/alliance_avatar/<?= htmlspecialchars($viewer->alliance_profile_picture_url ?? 'default.png') ?>" class="hex-avatar my-2">
                            <div>
                                <label class="font-08 text-muted">Fleet Strength</label>
                                <div class="comparison-bar"><div id="your-fleet-bar" class="comparison-bar-fill" style="width: 0%; background: linear-gradient(90deg, #00f3ff, #7bfdff);"></div></div>
                            </div>
                        </div>
                        <!-- Divider -->
                        <div class="col-2 d-flex justify-content-center">
                            <div class="versus-divider">VS</div>
                        </div>
                        <!-- Target Alliance -->
                        <div class="col-5 text-center">
                            <h5 class="text-danger" id="target-header">TARGET: [TAG]</h5>
                            <img id="target-avatar" src="" class="hex-avatar my-2">
                            <div>
                                <label class="font-08 text-muted">Estimated Fleet Strength</label>
                                <div class="comparison-bar"><div id="target-fleet-bar" class="comparison-bar-fill" style="width: 0%;"></div></div>
                            </div>
                        </div>
                    </div>
                    <hr class="border-secondary my-4">
                    
                    <div class="mb-3 mx-auto" style="max-width: 600px;">
                        <label class="form-label">Casus Belli (Reason for War)</label>
                        <div class="input-group">
                            <textarea id="casus-belli-input" name="casus_belli" class="form-control form-control-dark" rows="2" placeholder="e.g., Violation of Sector 9 Treaty..."></textarea>
                            <button id="randomize-casus-belli" class="btn btn-outline-secondary" type="button">Randomize</button>
                        </div>
                    </div>

                    <div id="slide-to-declare" class="slide-to-confirm-container mt-4 mx-auto" style="max-width: 600px;">
                        <div id="slider-handle" class="slider-handle"><i class="fas fa-chevron-right"></i></div>
                        <div class="slider-path-cleared"></div>
                        <div class="slide-to-confirm-text">&gt;&gt; SLIDE TO DECLARE WAR &gt;&gt;</div>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>

    <!-- 3. WAR ARCHIVES -->
    <div class="dashboard-card text-center">
        <h4 class="mb-3"><i class="fas fa-archive me-2"></i> War Archives</h4>
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mx-auto" style="max-width: 800px;">
                <thead>
                    <tr class="text-muted text-uppercase small">
                        <th>Conflict</th>
                        <th>Result</th>
                        <th>Participants</th>
                        <th>Date Concluded</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historicalWars)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No historical conflicts recorded.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($historicalWars as $hWar): ?>
                            <tr>
                                <td>[<?= htmlspecialchars($hWar->declarer_tag) ?>] vs. [<?= htmlspecialchars($hWar->defender_tag) ?>]</td>
                                <td>
                                    <?php if ($hWar->winner_alliance_id === $allianceId): ?>
                                        <span class="badge bg-success">Victory</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Defeat</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small">
                                    <span class="text-neon-blue">[<?= htmlspecialchars($hWar->declarer_tag) ?>]</span> vs 
                                    <span class="text-danger">[<?= htmlspecialchars($hWar->defender_tag) ?>]</span>
                                </td>
                                <td class="text-muted small"><?= date('M j, Y', strtotime($hWar->end_time)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Link to the new JavaScript file -->
<script src="/js/war_table.js?v=<?= time() ?>"></script>