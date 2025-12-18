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
            <span>Workers</span>
            <strong class="accent"><?= number_format($resources->workers) ?></strong>
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
            <span>Spies</span>
            <strong class="accent-green"><?= number_format($resources->spies) ?></strong>
        </div>
        <div class="header-stat">
            <span>Sentries</span>
            <strong class="accent-green"><?= number_format($resources->sentries) ?></strong>
        </div>
        <div class="header-stat">
            <span>Naquadah</span>
            <strong class="accent-purple"><?= number_format($resources->naquadah_crystals, 0) ?></strong>
        </div>
        <div class="header-stat">
            <span>Dark Matter</span>
            <strong class="accent-blue"><?= number_format($resources->dark_matter) ?></strong>
        </div>
        <div class="header-stat">
            <span>Research</span>
            <strong class="accent"><?= number_format($resources->research_data) ?></strong>
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
                                <button type="button" 
                                        class="btn-submit btn-add-cart" 
                                        style="border: 1px solid var(--accent);"
                                        data-key="<?= htmlspecialchars($struct['key']) ?>"
                                        data-name="<?= htmlspecialchars($struct['name']) ?>"
                                        data-next-level="<?= $struct['next_level'] ?>"
                                        data-cost-credits="<?= $struct['upgrade_cost_credits'] ?>"
                                        data-cost-crystal="<?= $struct['upgrade_cost_crystals'] ?>"
                                        data-cost-dm="<?= $struct['upgrade_cost_dark_matter'] ?? 0 ?>"
                                        <?= !$struct['can_afford'] ? 'disabled' : '' ?>>
                                    <?= $struct['can_afford'] ? 'Add to Batch (Lvl ' . $struct['next_level'] . ')' : 'Insufficient Resources' ?>
                                </button>
                            <?php endif; ?>
                        </div>
                        
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Batch Checkout Box -->
    <div id="structure-checkout-box" style="display:none; position: fixed; bottom: 20px; right: 20px; width: 320px; background: rgba(13, 17, 23, 0.95); border: 1px solid var(--accent); box-shadow: 0 0 15px rgba(0,0,0,0.8); padding: 15px; z-index: 9999; border-radius: 8px; backdrop-filter: blur(5px);">
        <div class="checkout-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; margin-bottom: 10px;">
            <h3 style="margin: 0; font-size: 1.1em; color: var(--accent);">Batch Upgrade</h3>
            <button id="btn-cancel-batch" style="background: none; border: none; color: var(--muted); cursor: pointer; font-size: 1.2em;">&times;</button>
        </div>
        
        <div id="checkout-list" style="max-height: 200px; overflow-y: auto; margin-bottom: 15px; font-size: 0.9em;">
            <!-- Items injected by JS -->
        </div>
        
        <div class="checkout-totals" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px; margin-bottom: 15px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span>Credits:</span>
                <span class="accent-gold" id="checkout-total-credits">0</span>
            </div>
            <div id="checkout-rare-resources" style="display: none;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span>Naquadah:</span>
                    <span class="accent-purple" id="checkout-total-crystal">0</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span>Dark Matter:</span>
                    <span class="accent-blue" id="checkout-total-dm">0</span>
                </div>
            </div>
        </div>
        
        <form id="checkout-form" action="/structures/batch-upgrade" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            <input type="hidden" name="structure_keys" id="checkout-input-keys" value="">
            <button type="submit" class="btn-submit" style="width: 100%;">Confirm Purchase</button>
        </form>
    </div>

</div>

<script src="/js/structures.js"></script>