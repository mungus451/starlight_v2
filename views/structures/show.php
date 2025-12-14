<?php
/**
 * @var array $groupedStructures Formatted ViewModel from StructurePresenter.
 * @var \App\Models\Entities\UserResource $resources User's current resources.
 * @var string $csrf_token CSRF token for forms.
 */
?>

<style>
    /* Local override for structure specific cards if needed, otherwise inherits global */
    .structure-card.max-level { 
        border-color: var(--accent-2); 
        box-shadow: var(--shadow), 0 0 15px rgba(249, 199, 79, 0.2); 
    }
</style>

<div class="container-full">
    <h1>Strategic Structures</h1>
    <p class="subtitle" style="text-align: center; color: var(--muted); margin-top: -1rem; margin-bottom: 2rem;">
        Build and upgrade your infrastructure to expand your influence and power.
    </p>

    <!-- Header Stats -->
    <div class="resource-header-card">
        <div class="header-stat">
            <span>Credits</span>
            <strong class="accent-gold"><?= number_format($resources->credits) ?></strong>
        </div>
        <div class="header-stat">
            <span>Soldiers</span>
            <strong class="accent-red"><?= number_format($resources->soldiers) ?></strong>
        </div>
        <div class="header-stat">
            <span>Guards</span>
            <strong class="accent-blue"><?= number_format($resources->guards) ?></strong>
        </div>
        <div class="header-stat">
            <span>Naquadah</span>
            <strong class="accent-purple"><?= number_format($resources->naquadah_crystals, 0) ?></strong>
        </div>
    </div>

    <!-- Structures Grid -->
    <div class="structures-grid">
        <?php if (empty($groupedStructures)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: var(--muted);">
                Configuration error: No structures found.
            </div>
        <?php else: ?>
            <?php foreach ($groupedStructures as $categoryName => $structures): ?>

                <div class="structure-category">
                    <h2><?= htmlspecialchars($categoryName) ?></h2>
                </div>

                <?php foreach ($structures as $struct): ?>
                    <div class="structure-card <?= $struct['is_max_level'] ? 'max-level' : '' ?>">
                        
                        <!-- Card Header -->
                        <div class="card-header-main">
                            <span class="card-icon"><?= $struct['icon'] ?></span>
                            <div class="card-title-group">
                                <h3 class="card-title"><?= htmlspecialchars($struct['name']) ?></h3>
                                <p class="card-level">Level: <?= $struct['current_level'] ?></p>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body-main">
                            <p class="card-description"><?= htmlspecialchars($struct['description']) ?></p>
                            
                            <?php if (!empty($struct['benefit_text'])): ?>
                                <div class="card-benefit">
                                    <span class="icon">✨</span>
                                    <?= htmlspecialchars($struct['benefit_text']) ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!$struct['is_max_level']): ?>
                                <div class="card-costs-next">
                                    <div class="cost-item <?= !$struct['can_afford'] ? 'insufficient' : '' ?>">
                                        <span class="icon">◎</span>
                                        <span class="value"><?= $struct['cost_formatted'] ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Card Footer (Actions) -->
                        <div class="card-footer-actions">
                            <?php if ($struct['is_max_level']): ?>
                                <div class="max-level-badge">Max Level Achieved!</div>
                            <?php else: ?>
                                <form action="/structures/upgrade" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                    <input type="hidden" name="structure_key" value="<?= htmlspecialchars($struct['key']) ?>">

                                    <button type="submit" class="btn-submit" <?= !$struct['can_afford'] ? 'disabled' : '' ?>>
                                        <?= $struct['can_afford'] ? 'Upgrade to Level ' . $struct['next_level'] : 'Insufficient Resources' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                        
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>