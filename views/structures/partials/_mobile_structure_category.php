<?php
// --- Partial View: Renders a list of structures for one category ---
/* @var array $structures */
/* @var string $csrf_token */
?>

<?php foreach ($structures as $s): ?>
    <div class="mobile-card structure-card-mobile">
        <div class="mobile-card-header">
            <h3><i class="fas <?= htmlspecialchars($s['icon']) ?>"></i> <?= htmlspecialchars($s['name']) ?></h3>
            <span class="structure-level">Lvl <?= $s['current_level'] ?></span>
        </div>
        <div class="mobile-card-content">
            <p class="structure-description"><?= htmlspecialchars($s['description']) ?></p>
            
            <div class="structure-benefit">
                <strong>Current Effect:</strong> <?= htmlspecialchars($s['benefit_text']) ?>
            </div>
            
            <?php if ($s['is_max_level']): ?>
                <div class="max-level-notice">
                    <i class="fas fa-check-circle"></i> Max Level Reached
                </div>
            <?php else: ?>
                <div class="next-level-info">
                    <div class="next-level-benefit">
                        <!-- Note: The presenter logic for next benefit is complex, so we will omit it for now -->
                    </div>
                    <ul class="structure-costs">
                         <li><i class="fas fa-coins"></i> <?= htmlspecialchars($s['cost_formatted']) ?></li>
                    </ul>
                </div>
                <form action="/structures/upgrade" method="POST">
                    <input type="hidden" name="csrf_token" value="" class="csrf-token-input">
                    <input type="hidden" name="structure_key" value="<?= htmlspecialchars($s['key']) ?>">
                    <button type="submit" class="btn" <?= $s['can_afford'] ? '' : 'disabled' ?>>
                        <i class="fas fa-arrow-alt-circle-up"></i> Upgrade
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
