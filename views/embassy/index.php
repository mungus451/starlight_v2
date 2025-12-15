<?php
// views/embassy/index.php
// $embassy_level, $max_slots, $slots_used, $active_edicts, $active_keys, $available_edicts are passed in
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Embassy <small class="text-muted">(Level <?= $embassy_level ?>)</small></h1>
            <p class="lead">
                Manage your empire's Planetary Directives. 
                <span class="badge bg-info text-dark">Slots: <?= $slots_used ?> / <?= $max_slots ?></span>
            </p>
        </div>
    </div>

    <!-- Active Edicts Section -->
    <div class="row mb-5">
        <div class="col-md-12">
            <h3>Active Directives</h3>
            <?php if (empty($active_edicts)): ?>
                <div class="alert alert-secondary">No active directives. Enact one below!</div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($active_edicts as $edict): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card border-success h-100">
                                <div class="card-header bg-success text-white">
                                    <strong><?= htmlspecialchars($edict->name) ?></strong>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><em>"<?= htmlspecialchars($edict->lore) ?>"</em></p>
                                    <p class="card-text"><?= htmlspecialchars($edict->description) ?></p>
                                    <?php if ($edict->upkeep_cost > 0): ?>
                                        <p class="text-danger small">Upkeep: <?= $edict->upkeep_cost ?> <?= ucfirst($edict->upkeep_resource) ?>/turn</p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <form action="/embassy/revoke" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="edict_key" value="<?= $edict->key ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">Revoke</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Available Edicts Section -->
    <div class="row">
        <div class="col-md-12">
            <h3>Available Edicts</h3>
            
            <?php 
                $types = ['economic', 'military', 'espionage', 'special'];
                foreach ($types as $type):
                    // Filter edicts by type
                    $edictsOfType = array_filter($available_edicts, fn($e) => $e->type === $type);
                    if (empty($edictsOfType)) continue;
            ?>
                <h4 class="mt-4 text-capitalize border-bottom pb-2"><?= $type ?> Directives</h4>
                <div class="row">
                    <?php foreach ($edictsOfType as $edict): 
                        $isActive = in_array($edict->key, $active_keys);
                    ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card h-100 <?= $isActive ? 'border-success' : '' ?>">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?= htmlspecialchars($edict->name) ?>
                                        <?php if ($isActive): ?>
                                            <span class="badge bg-success float-end">Active</span>
                                        <?php endif; ?>
                                    </h5>
                                    <h6 class="card-subtitle mb-2 text-muted fst-italic"><?= htmlspecialchars($edict->lore) ?></h6>
                                    <p class="card-text"><?= htmlspecialchars($edict->description) ?></p>
                                    
                                    <?php if ($edict->upkeep_cost > 0): ?>
                                        <p class="text-danger small mb-2">Upkeep: <?= $edict->upkeep_cost ?> <?= ucfirst($edict->upkeep_resource) ?>/turn</p>
                                    <?php endif; ?>

                                    <?php if (!$isActive): ?>
                                        <form action="/embassy/activate" method="POST" class="mt-3">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                            <input type="hidden" name="edict_key" value="<?= $edict->key ?>">
                                            <?php if ($embassy_level < 1): ?>
                                                <button type="button" class="btn btn-secondary w-100" disabled>Build Embassy First</button>
                                            <?php elseif ($slots_used >= $max_slots): ?>
                                                <button type="button" class="btn btn-secondary w-100" disabled>Slots Full</button>
                                            <?php else: ?>
                                                <button type="submit" class="btn btn-primary w-100">Enact</button>
                                            <?php endif; ?>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
