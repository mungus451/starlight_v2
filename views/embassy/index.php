<?php
/**
 * View: Embassy / Planetary Directives
 * Layout: Advisor V2 (Mirrored from Structures/Armory)
 */

// Categorize available edicts
$types = ['economic', 'military', 'espionage', 'special'];
$groupedEdicts = [];
foreach ($types as $type) {
    $groupedEdicts[$type] = array_filter($available_edicts, fn($e) => $e->type === $type);
}

// Icon helper
function getEdictIcon($type) {
    return match($type) {
        'economic' => 'fas fa-coins',
        'military' => 'fas fa-crosshairs',
        'espionage' => 'fas fa-user-secret',
        'special' => 'fas fa-star',
        default => 'fas fa-scroll'
    };
}
?>

<div class="structures-page-content">
    
    <!-- 1. Page Header -->
    <div class="page-header-container">
        <h1 class="page-title-neon">Planetary Directives</h1>
        <p class="page-subtitle-tech">
            Executive Orders // Imperial Policy // Clearance Level <?= $embassy_level ?>
        </p>
        <div class="flex-center gap-2 mt-2">
            <div class="badge bg-dark border-secondary">
                Embassy Lvl <?= $embassy_level ?>
            </div>
            <div class="badge bg-dark border-<?= $slots_used >= $max_slots ? 'danger' : 'success' ?>">
                Active Slots: <?= $slots_used ?> / <?= $max_slots ?>
            </div>
        </div>
    </div>

    <!-- 2. Navigation Deck -->
    <div class="structure-nav-container">
        <!-- Active Tab -->
        <button class="structure-nav-btn active" data-tab-target="cat-active">
            <i class="fas fa-check-circle text-success"></i> Active Orders (<?= count($active_edicts) ?>)
        </button>
        
        <!-- Category Tabs -->
        <?php foreach ($types as $type): 
            $count = count($groupedEdicts[$type] ?? []);
        ?>
            <button class="structure-nav-btn" data-tab-target="cat-<?= $type ?>">
                <i class="<?= getEdictIcon($type) ?>"></i> <?= ucfirst($type) ?>
            </button>
        <?php endforeach; ?>

        <?php if (!empty($currentUserAllianceId)): ?>
            <a href="/alliance/diplomacy" class="structure-nav-btn" style="text-decoration: none;">
                <i class="fas fa-handshake"></i> Diplomacy
            </a>
        <?php endif; ?>
    </div>

    <!-- 3. Content Deck -->
    <div class="structure-deck">
        
        <!-- ACTIVE ORDERS TAB -->
        <div id="cat-active" class="structure-category-container active">
            <div class="structures-grid">
                <?php if (empty($active_edicts)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: var(--muted); border: 1px dashed var(--border); border-radius: 12px; background: rgba(0,0,0,0.2);">
                        <i class="fas fa-clipboard-list fa-3x mb-3 opacity-20"></i>
                        <p>No directives currently enacted.</p>
                        <p class="font-08">Select a protocol category to view available orders.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($active_edicts as $edict): ?>
                        <div class="structure-card" style="border-color: var(--accent-green);">
                            <div class="card-header-main">
                                <span class="card-icon" style="color: var(--accent-green); background: rgba(56, 161, 105, 0.1); border-color: rgba(56, 161, 105, 0.3);">
                                    <i class="<?= getEdictIcon($edict->type) ?>"></i>
                                </span>
                                <div class="card-title-group">
                                    <h3 class="card-title"><?= htmlspecialchars($edict->name) ?></h3>
                                    <p class="card-level text-success">STATUS: ACTIVE</p>
                                </div>
                            </div>

                            <div class="card-body-main">
                                <p class="card-description"><?= htmlspecialchars($edict->description) ?></p>
                                
                                <div class="card-benefit mb-3">
                                    <span class="text-neon-blue" style="font-weight: 600; font-size: 0.9rem;">
                                        <i class="fas fa-bolt me-1"></i> <?= htmlspecialchars($edict->lore) ?>
                                    </span>
                                </div>

                                <?php if ($edict->upkeep_cost > 0): ?>
                                    <div class="resource-cost-grid">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted">Upkeep Cost:</span>
                                            <span class="text-danger font-weight-bold">
                                                -<?= number_format($edict->upkeep_cost) ?> <?= ucfirst($edict->upkeep_resource) ?>/turn
                                            </span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="card-footer-actions p-3 pt-0">
                                <form action="/embassy/revoke" method="POST" class="w-100">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                    <input type="hidden" name="edict_key" value="<?= $edict->key ?>">
                                    <button type="submit" class="btn btn-outline-warning w-100">
                                        <i class="fas fa-ban me-1"></i> Revoke Order
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- CATEGORY TABS -->
        <?php foreach ($types as $type): 
            $edicts = $groupedEdicts[$type] ?? [];
        ?>
            <div id="cat-<?= $type ?>" class="structure-category-container">
                <div class="structures-grid">
                    <?php if (empty($edicts)): ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: var(--muted);">
                            No <?= ucfirst($type) ?> protocols available at this level.
                        </div>
                    <?php else: ?>
                        <?php foreach ($edicts as $edict): 
                            if (in_array($edict->key, $active_keys)) continue; // Skip if active (shown in active tab)
                            $canAfford = true; // Add logic if upfront costs exist
                            $isLocked = $slots_used >= $max_slots;
                        ?>
                            <div class="structure-card">
                                <div class="card-header-main">
                                    <span class="card-icon"><i class="<?= getEdictIcon($type) ?>"></i></span>
                                    <div class="card-title-group">
                                        <h3 class="card-title"><?= htmlspecialchars($edict->name) ?></h3>
                                        <p class="card-level">Clearance: Level 1</p>
                                    </div>
                                </div>

                                <div class="card-body-main">
                                    <p class="card-description"><?= htmlspecialchars($edict->description) ?></p>
                                    
                                    <div class="card-benefit mb-3">
                                        <span class="text-neon-blue" style="font-weight: 600; font-size: 0.9rem;">
                                            <i class="fas fa-star me-1"></i> <?= htmlspecialchars($edict->lore) ?>
                                        </span>
                                    </div>

                                    <?php if ($edict->upkeep_cost > 0): ?>
                                        <div class="resource-cost-grid">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted">Projected Upkeep:</span>
                                                <span class="text-warning font-weight-bold">
                                                    <?= number_format($edict->upkeep_cost) ?> <?= ucfirst($edict->upkeep_resource) ?>/turn
                                                </span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="card-footer-actions p-3 pt-0">
                                    <form action="/embassy/activate" method="POST" class="w-100">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                        <input type="hidden" name="edict_key" value="<?= $edict->key ?>">
                                        
                                        <?php if ($isLocked): ?>
                                            <button type="button" class="btn btn-secondary w-100" disabled>
                                                <i class="fas fa-lock me-1"></i> Slots Full
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="fas fa-check me-1"></i> Enact Protocol
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

    </div>
</div>

<script src="/js/embassy.js?v=<?= time() ?>"></script>
