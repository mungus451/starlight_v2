<?php
// --- Partial: Available Edicts ---
/* @var array $available_edicts */
/* @var array $active_keys */
/* @var string $csrf_token */
?>
<div id="available-edicts" class="tab-content">
    <?php if (empty($available_edicts)): ?>
        <p class="text-center text-muted" style="padding: 2rem;">No available edicts found.</p>
    <?php else: ?>
        <?php foreach ($available_edicts as $edict):
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
    <?php endif; ?>
</div>
