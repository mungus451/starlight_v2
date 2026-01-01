<?php
// --- Mobile Embassy View (Full Server-Side Render) ---
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">Embassy</h1>
        <p style="color: var(--mobile-text-secondary); font-size: 0.9rem; margin: 0.5rem 1rem 0 1rem; text-align: center;">Manage active directives and enact powerful new edicts.</p>
    </div>

    <!-- Embassy Status Card -->
    <div class="mobile-card">
        <div class="mobile-card-header">
            <h3><i class="fas fa-landmark"></i> Embassy Status</h3>
        </div>
        <div class="mobile-card-content" style="display: block;">
            <ul class="mobile-stats-list">
                <li><span><i class="fas fa-building"></i> Embassy Level</span> <strong><?= $embassy_level ?></strong></li>
                <li><span><i class="fas fa-check-circle"></i> Edict Slots Used</span> <strong><?= $slots_used ?> / <?= $max_slots ?></strong></li>
            </ul>
        </div>
    </div>

    <!-- Main Tab Navigation -->
    <div id="embassy-tabs" class="mobile-tabs nested-tabs-container">
        <a href="#" class="tab-link active" data-tab-target="content-active">Active Directives</a>
        <a href="#" class="tab-link" data-tab-target="content-available">Available Edicts</a>
    </div>

    <!-- Main Content Panes -->
    <div id="tab-content">
        <!-- Active Edicts Pane -->
        <div id="content-active" class="nested-tab-content active">
            <?php if (empty($active_edicts)): ?>
                <p class="text-center text-muted" style="padding: 2rem;">No active directives.</p>
            <?php else: ?>
                <?php foreach ($active_edicts as $edict): ?>
                    <div class="mobile-card">
                        <div class="mobile-card-header"><h3><i class="fas fa-file-alt"></i> <?= htmlspecialchars($edict->name) ?></h3></div>
                        <div class="mobile-card-content" style="display: block;">
                            <p class="structure-description"><?= htmlspecialchars($edict->description) ?></p>
                            <form action="/embassy/revoke" method="POST" style="margin-top: 1rem;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <input type="hidden" name="edict_key" value="<?= htmlspecialchars($edict->key) ?>">
                                <button type="submit" class="btn btn-accent"><i class="fas fa-times-circle"></i> Revoke Edict</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Available Edicts Pane -->
        <div id="content-available" class="nested-tab-content">
            <?php if (empty($grouped_edicts)): ?>
                <p class="text-center text-muted" style="padding: 2rem;">No available edicts found.</p>
            <?php else:
                $category_keys = array_keys($grouped_edicts);
            ?>
                <div class="nested-tabs-container">
                    <div class="mobile-tabs nested-tabs">
                        <?php foreach ($category_keys as $index => $category): ?>
                            <a href="#" class="tab-link <?= $index === 0 ? 'active' : '' ?>" data-tab-target="nested-<?= str_replace(' & ', '_', strtolower($category)) ?>">
                                <?= htmlspecialchars($category) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php foreach ($grouped_edicts as $category => $edicts): ?>
                        <div id="nested-<?= str_replace(' & ', '_', strtolower($category)) ?>" class="nested-tab-content <?= $category === $category_keys[0] ? 'active' : '' ?>">
                            <?php foreach ($edicts as $edict):
                                $is_active = in_array($edict->key, $active_keys);
                            ?>
                                <div class="mobile-card">
                                    <div class="mobile-card-header"><h3><i class="fas fa-file-alt"></i> <?= htmlspecialchars($edict->name) ?></h3></div>
                                    <div class="mobile-card-content" style="display: block;">
                                        <p class="structure-description"><?= htmlspecialchars($edict->description) ?></p>
                                        <?php if ($is_active): ?>
                                            <div class="max-level-notice" style="margin-top: 1rem; background-color: rgba(0, 229, 255, 0.1); border-color: var(--mobile-text-primary); color: var(--mobile-text-primary);">
                                                <i class="fas fa-check-circle"></i> Already Active
                                            </div>
                                        <?php else: ?>
                                            <form action="/embassy/activate" method="POST" style="margin-top: 1rem;">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                                <input type="hidden" name="edict_key" value="<?= htmlspecialchars($edict->key) ?>">
                                                <button type="submit" class="btn"><i class="fas fa-check-circle"></i> Enact Edict</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
