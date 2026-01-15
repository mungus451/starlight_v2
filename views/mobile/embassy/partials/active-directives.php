<?php
// --- Partial: Active Directives ---
/* @var array $active_edicts */
/* @var string $csrf_token */
?>
<div id="active-directives" class="tab-content">
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
