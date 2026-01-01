<?php
/**
 * View: Embassy / Planetary Directives
 * Layout: Mirrored from Structures Page
 */

// Categorize available edicts
$types = ['economic', 'military', 'espionage', 'special'];
$groupedEdicts = [];
foreach ($types as $type) {
    $groupedEdicts[$type] = array_filter($available_edicts, fn($e) => $e->type === $type);
}
?>

<div class="container-full">
    <div class="tabs-nav mb-4 justify-content-center">
        <a href="/embassy" class="tab-link active">Planetary Directives</a>
        <?php if (!empty($currentUserAllianceId)): ?>
            <a href="/alliance/diplomacy" class="tab-link">Alliance Diplomacy</a>
        <?php endif; ?>
    </div>

    <h1>Planetary Directives</h1>
    <p class="subtitle" style="text-align: center; color: var(--muted); margin-top: -1rem; margin-bottom: 2rem;">
        Enact executive orders to specialize your empire's functionality.
    </p>

    <!-- Header Stats (Mirrors Resource Header) -->
    <div class="resource-header-card">
        <div class="header-stat">
            <span>Clearance Level</span>
            <strong class="accent-blue"><?= $embassy_level ?></strong>
        </div>
        <div class="header-stat">
            <span>Edict Slots</span>
            <strong class="<?= $slots_used >= $max_slots ? 'accent-red' : 'accent-gold' ?>">
                <?= $slots_used ?> / <?= $max_slots ?>
            </strong>
        </div>
        <div class="header-stat">
            <span>Active Orders</span>
            <strong class="accent-green"><?= count($active_edicts) ?></strong>
        </div>
    </div>

    <div class="structures-grid">
        
        <!-- SECTION 1: ACTIVE DIRECTIVES -->
        <div class="structure-category">
            <h2>Active Orders</h2>
        </div>

        <?php if (empty($active_edicts)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: var(--muted); background: rgba(0,0,0,0.2); border-radius: 12px;">
                No directives currently enacted. Select a protocol below.
            </div>
        <?php else: ?>
            <?php foreach ($active_edicts as $edict): ?>
                <div class="structure-card max-level">
                    <div class="card-header-main">
                        <span class="card-icon" style="color: var(--accent-green);"><i class="fas fa-check-circle"></i></span>
                        <div class="card-title-group">
                            <h3 class="card-title"><?= htmlspecialchars($edict->name) ?></h3>
                            <p class="card-level" style="color: var(--accent-green);">Status: ACTIVE</p>
                        </div>
                    </div>

                    <div class="card-body-main">
                        <p class="card-description"><?= htmlspecialchars($edict->description) ?></p>
                        
                        <div class="card-benefit" style="border-color: var(--accent-green); color: var(--accent-green);">
                            <span class="icon">📜</span>
                            <?= htmlspecialchars($edict->lore) ?>
                        </div>

                        <?php if ($edict->upkeep_cost > 0): ?>
                            <div class="card-costs-next">
                                <div class="cost-item">
                                    <span class="icon">⚡</span>
                                    <span class="value" style="color: var(--accent-red);">
                                        -<?= $edict->upkeep_cost ?> <?= ucfirst(substr($edict->upkeep_resource, 0, 3)) ?>/turn
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-footer-actions">
                        <form action="/embassy/revoke" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                            <input type="hidden" name="edict_key" value="<?= $edict->key ?>">
                            <button type="submit" class="btn-submit btn-reject" style="width: 100%;">Revoke Order</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>


        <!-- SECTION 2: AVAILABLE PROTOCOLS -->
        <?php foreach ($types as $type): ?>
            <?php 
                $visibleEdicts = [];
                // Filter out active ones for the list
                foreach ($groupedEdicts[$type] as $e) {
                    if (!in_array($e->key, $active_keys)) {
                        $visibleEdicts[] = $e;
                    }
                }
                
                if (empty($visibleEdicts)) continue; 
            ?>

            <div class="structure-category">
                <h2><?= ucfirst($type) ?> Protocols</h2>
            </div>

            <?php foreach ($visibleEdicts as $edict): ?>
                <div class="structure-card">
                    <div class="card-header-main">
                        <span class="card-icon"><i class="fas fa-terminal"></i></span>
                        <div class="card-title-group">
                            <h3 class="card-title"><?= htmlspecialchars($edict->name) ?></h3>
                            <p class="card-level">Clearance: Level 1</p>
                        </div>
                    </div>

                    <div class="card-body-main">
                        <p class="card-description"><?= htmlspecialchars($edict->description) ?></p>
                        
                        <div class="card-benefit">
                            <span class="icon">✨</span>
                            <?= htmlspecialchars($edict->lore) ?>
                        </div>

                        <?php if ($edict->upkeep_cost > 0): ?>
                            <div class="card-costs-next">
                                <div class="cost-item">
                                    <span class="icon">⚡</span>
                                    <span class="value"><?= $edict->upkeep_cost ?> <?= ucfirst($edict->upkeep_resource) ?> / turn</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-footer-actions">
                        <form action="/embassy/activate" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                            <input type="hidden" name="edict_key" value="<?= $edict->key ?>">

                            <?php if ($embassy_level < 1): ?>
                                <button type="button" class="btn-submit" disabled>Embassy Required</button>
                            <?php elseif ($slots_used >= $max_slots): ?>
                                <button type="button" class="btn-submit" disabled>Slots Full</button>
                            <?php else: ?>
                                <button type="submit" class="btn-submit">Enact Protocol</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>

    </div>
</div>
