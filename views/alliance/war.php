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
        <div class="dashboard-card war-score-dashboard mb-4" data-war-id="<?= $war->id ?>">
            <h3 class="text-center mb-4 text-neon-blue"><i class="fas fa-star-half-alt me-2"></i>War Score</h3>
            <div class="row align-items-center text-center mb-4">
                <div class="col-5">
                    <img id="alliance-a-avatar-<?= $war->id ?>" src="/serve/alliance_avatar/<?= htmlspecialchars($war->declarer_profile_picture_url ?? 'default.png') ?>" class="hex-avatar-lg mb-2 border-neon">
                    <h4 id="alliance-a-name-<?= $war->id ?>" class="text-neon-blue glitch-text" data-text="[<?= htmlspecialchars($war->declarer_tag) ?>] <?= htmlspecialchars($war->declarer_name) ?>">[<?= htmlspecialchars($war->declarer_tag) ?>] <?= htmlspecialchars($war->declarer_name) ?></h4>
                </div>
                <div class="col-2">
                    <h3 class="text-neon-blue">VS</h3>
                    <div class="timer-countdown text-muted small" data-end-time="<?= htmlspecialchars($war->end_time) ?>">
                        --d --h --m
                    </div>
                </div>
                <div class="col-5">
                    <img id="alliance-b-avatar-<?= $war->id ?>" src="/serve/alliance_avatar/<?= htmlspecialchars($war->defender_profile_picture_url ?? 'default.png') ?>" class="hex-avatar-lg mb-2 border-danger">
                    <h4 id="alliance-b-name-<?= $war->id ?>" class="text-danger glitch-text" data-text="[<?= htmlspecialchars($war->defender_tag) ?>] <?= htmlspecialchars($war->defender_name) ?>">[<?= htmlspecialchars($war->defender_tag) ?>] <?= htmlspecialchars($war->defender_name) ?></h4>
                </div>
            </div>

            <div class="score-breakdown mb-4">
                <!-- Economy -->
                <div class="score-category mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-neon-blue"><i class="fas fa-coins me-2"></i>Economy (Plunder)</span>
                        <div>
                            <span id="alliance-a-economy-score-<?= $war->id ?>" class="text-neon-blue">0</span> / <span id="alliance-b-economy-score-<?= $war->id ?>" class="text-danger">0</span>
                        </div>
                    </div>
                    <div class="progress bg-dark border border-secondary" style="height: 15px;">
                        <div id="economy-progress-a-<?= $war->id ?>" class="progress-bar bg-neon-blue" role="progressbar" style="width: 50%;" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                        <div id="economy-progress-b-<?= $war->id ?>" class="progress-bar bg-danger" role="progressbar" style="width: 50%;" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <!-- Add other categories similarly with war->id suffix -->
            </div>

            <div class="total-score text-center mt-4">
                <h3 class="mb-3 text-uppercase">Total War Score</h3>
                <div class="row">
                    <div class="col-5">
                        <div id="alliance-a-total-score-<?= $war->id ?>" class="display-4 text-neon-blue glitch-text" data-text="0">0</div>
                    </div>
                    <div class="col-2"></div>
                    <div class="col-5">
                        <div id="alliance-b-total-score-<?= $war->id ?>" class="display-4 text-danger glitch-text" data-text="0">0</div>
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

<!-- Link to the new JavaScript files -->
<script src="/js/war_table.js?v=<?= time() ?>"></script>
<script src="/js/war_score.js?v=<?= time() ?>"></script>