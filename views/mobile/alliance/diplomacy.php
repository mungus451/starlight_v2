<?php
// --- Mobile Alliance Diplomacy View ---
/* @var array $pendingTreaties */
/* @var array $activeTreaties */
/* @var array $rivalries */
/* @var array $otherAlliances */
/* @var bool $canManage */
/* @var int $allianceId */
/* @var \App\Models\Entities\User $viewer */
/* @var string $csrf_token */

// Helper to get alliance name from ID
$getAllianceName = function($id, $alliances) {
    foreach ($alliances as $a) {
        if ($a->id === $id) return htmlspecialchars($a->name);
    }
    return 'Unknown Alliance';
};

$treatyTypes = [
    'peace' => 'Peace Treaty',
    'non_aggression' => 'Non-Aggression Pact',
    'mutual_defense' => 'Mutual Defense Pact',
];

?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">Diplomacy Hub</h1>
        <p style="color: var(--mobile-text-secondary); font-size: 0.9rem; margin: 0.5rem 1rem 0 1rem; text-align: center;">Forge alliances and declare rivalries.</p>
    </div>

    <!-- Status Card -->
    <div class="mobile-card" style="text-align: center; border-color: var(--mobile-accent-blue);">
        <div class="mobile-card-header" style="justify-content: center;">
            <h3 style="color: var(--mobile-accent-blue);"><i class="fas fa-handshake"></i> Current Stance</h3>
        </div>
        <div class="mobile-card-content" style="display: block;">
            <div style="font-family: 'Orbitron', sans-serif; font-size: 1.5rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px rgba(0, 140, 255, 0.4);">
                Your Alliance: [<?= htmlspecialchars($viewer->alliance_tag ?? '') ?>] <?= htmlspecialchars($viewer->alliance_name ?? 'Unknown') ?>
            </div>
            <div style="font-size: 0.9rem; color: var(--muted); margin-top: 0.5rem;">
                Management: <strong style="color: <?= $canManage ? 'var(--mobile-accent-green)' : 'var(--mobile-accent-red)' ?>;"><?= $canManage ? 'Authorized' : 'Unauthorized' ?></strong>
            </div>
        </div>
    </div>

    <!-- WRAPPER FOR TABS LOGIC -->
    <div class="nested-tabs-container">
        
        <!-- Tab Navigation -->
        <div class="mobile-tabs nested-tabs">
            <a href="#" class="tab-link active" data-tab-target="tab-treaties">Treaties</a>
            <a href="#" class="tab-link" data-tab-target="tab-rivalries">Rivalries</a>
            <?php if ($canManage): // Only show propose if user has permission ?>
                <a href="#" class="tab-link" data-tab-target="tab-propose">Propose</a>
            <?php endif; ?>
        </div>

        <!-- Tab Content -->
        <div id="tab-content">
            
            <!-- Treaties Tab -->
            <div id="tab-treaties" class="nested-tab-content active">
                <div class="nested-tabs-container" style="background: none; padding: 0; margin-top: 0;">
                    <div class="mobile-tabs nested-tabs" style="justify-content: flex-start; overflow-x: auto; flex-wrap: nowrap; border-bottom: none; margin-bottom: 1rem; padding-bottom: 0.5rem;">
                        <a href="#" class="tab-link active" data-tab-target="treaty-pending" style="white-space: nowrap; font-size: 0.9rem; padding: 0.5rem 1rem;">Incoming</a>
                        <a href="#" class="tab-link" data-tab-target="treaty-active" style="white-space: nowrap; font-size: 0.9rem; padding: 0.5rem 1rem;">Active</a>
                    </div>

                    <div id="tier-content-treaties">
                        <div id="treaty-pending" class="nested-tab-content active">
                            <?php if (empty($pendingTreaties)): ?>
                                <p class="text-center text-muted" style="padding: 2rem;">No pending proposals.</p>
                            <?php else: ?>
                                <?php foreach ($pendingTreaties as $treaty): 
                                    $proposingAllianceName = $getAllianceName($treaty->alliance1_id, $otherAlliances);
                                ?>
                                <div class="mobile-card">
                                    <div class="mobile-card-header"><h3><?= htmlspecialchars($treatyTypes[$treaty->treaty_type] ?? ucfirst($treaty->treaty_type)) ?></h3></div>
                                    <div class="mobile-card-content" style="display: block;">
                                        <p class="structure-description">From: <strong style="color: #fff;"><?= $proposingAllianceName ?></strong></p>
                                        <p class="text-muted" style="font-size: 0.85rem;"><?= nl2br(htmlspecialchars($treaty->terms ?? 'No terms provided.')) ?></p>
                                        <?php if ($canManage): ?>
                                            <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                                                <form action="/alliance/diplomacy/treaty/accept/<?= $treaty->id ?>" method="POST" style="flex: 1;">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                                    <button type="submit" class="btn btn-accent" style="width: 100%;">Accept</button>
                                                </form>
                                                <form action="/alliance/diplomacy/treaty/decline/<?= $treaty->id ?>" method="POST" style="flex: 1;">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                                    <button type="submit" class="btn btn-danger" style="width: 100%;">Decline</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div id="treaty-active" class="nested-tab-content">
                            <?php if (empty($activeTreaties)): ?>
                                <p class="text-center text-muted" style="padding: 2rem;">No active treaties.</p>
                            <?php else: ?>
                                <?php foreach ($activeTreaties as $treaty): 
                                    $otherAllianceId = ($treaty->alliance1_id === $allianceId) ? $treaty->alliance2_id : $treaty->alliance1_id;
                                    $otherAllianceName = $getAllianceName($otherAllianceId, $otherAlliances);
                                ?>
                                <div class="mobile-card">
                                    <div class="mobile-card-header"><h3><?= htmlspecialchars($treatyTypes[$treaty->treaty_type] ?? ucfirst($treaty->treaty_type)) ?></h3></div>
                                    <div class="mobile-card-content" style="display: block;">
                                        <p class="structure-description">With: <strong style="color: #fff;"><?= $otherAllianceName ?></strong></p>
                                        <p class="text-muted" style="font-size: 0.85rem;"><?= nl2br(htmlspecialchars($treaty->terms ?? 'No terms provided.')) ?></p>
                                        <?php if ($canManage): ?>
                                            <form action="/alliance/diplomacy/treaty/break/<?= $treaty->id ?>" method="POST" onsubmit="return confirm('Are you sure you want to break this treaty? This may incur penalties.');" style="margin-top: 1rem;">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                                <button type="submit" class="btn btn-danger" style="width: 100%;">Break Treaty</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div> <!-- End Treaty Nested Container -->
            </div>

            <!-- Rivalries Tab -->
            <div id="tab-rivalries" class="nested-tab-content">
                <div class="mobile-card">
                    <div class="mobile-card-header"><h3>Current Rivalries</h3></div>
                    <div class="mobile-card-content" style="display: block;">
                        <?php if (empty($rivalries)): ?>
                            <p class="text-center text-muted">No active rivalries.</p>
                        <?php else: ?>
                            <ul class="mobile-stats-list">
                                <?php foreach ($rivalries as $rivalry): 
                                    $rivalAllianceId = ($rivalry->alliance_a_id === $allianceId) ? $rivalry->alliance_b_id : $rivalry->alliance_a_id;
                                    $rivalAllianceName = $getAllianceName($rivalAllianceId, $otherAlliances);
                                ?>
                                    <li><span><i class="fas fa-skull"></i> <?= $rivalAllianceName ?></span> <strong style="color: var(--mobile-accent-red);">Rival</strong></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($canManage): ?>
                    <div class="mobile-card">
                        <div class="mobile-card-header"><h3>Declare Rivalry</h3></div>
                        <div class="mobile-card-content" style="display: block;">
                            <form action="/alliance/diplomacy/rivalry/declare" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <div class="form-group">
                                    <label for="target_rival_alliance_id">Target Alliance</label>
                                    <select name="target_alliance_id" id="target_rival_alliance_id" class="mobile-input" style="width: 100%;">
                                        <?php foreach ($otherAlliances as $oa): ?>
                                            <option value="<?= $oa->id ?>">[<?= htmlspecialchars($oa->tag) ?>] <?= htmlspecialchars($oa->name) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-danger" style="width: 100%; margin-top: 1rem;">
                                    <i class="fas fa-exclamation-triangle"></i> Declare Rivalry
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Propose Tab -->
            <?php if ($canManage): ?>
            <div id="tab-propose" class="nested-tab-content">
                <div class="mobile-card">
                    <div class="mobile-card-header"><h3>Propose New Treaty</h3></div>
                    <div class="mobile-card-content" style="display: block;">
                        <form action="/alliance/diplomacy/treaty/propose" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            
                            <div class="form-group">
                                <label for="propose_target_alliance_id">Target Alliance</label>
                                <select name="target_alliance_id" id="propose_target_alliance_id" class="mobile-input" style="width: 100%;">
                                    <?php foreach ($otherAlliances as $oa): ?>
                                        <option value="<?= $oa->id ?>">[<?= htmlspecialchars($oa->tag) ?>] <?= htmlspecialchars($oa->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="treaty_type">Treaty Type</label>
                                <select name="treaty_type" id="treaty_type" class="mobile-input" style="width: 100%;">
                                    <?php foreach ($treatyTypes as $key => $label): ?>
                                        <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="terms">Terms (Optional)</label>
                                <textarea name="terms" id="terms" class="mobile-input" style="width: 100%; height: 80px;" placeholder="e.g., No attacks on members under level 10."></textarea>
                            </div>

                            <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">
                                <i class="fas fa-pencil-alt"></i> Propose Treaty
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div> <!-- End nested-tabs-container -->
</div>
