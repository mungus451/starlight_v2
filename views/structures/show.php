<?php
/**
 * @var array $groupedStructures Formatted ViewModel from StructurePresenter.
 * @var \App\Models\Entities\UserResource $resources User's current resources.
 * @var string $csrf_token CSRF token for forms.
 */
?>

<div class="structures-page-content">
    <div class="page-header-container">
        <h1 class="page-title-neon">Strategic Structures</h1>
        <p class="page-subtitle-tech">
            Build and upgrade infrastructure // Expand influence
        </p>
    </div>

    <?php if (empty($groupedStructures)): ?>
        <div style="text-align: center; padding: 2rem; color: var(--muted); border: 1px dashed var(--border); border-radius: 8px;">
            <i class="fas fa-exclamation-triangle"></i> Configuration error: No structures found.
        </div>
    <?php else: ?>

        <!-- Category Navigation Deck -->
        <div class="structure-nav-container">
            <?php 
            $firstCategory = true;
            foreach ($groupedStructures as $categoryName => $structures): 
                $catId = 'cat-' . md5($categoryName);
            ?>
                <button class="structure-nav-btn <?= $firstCategory ? 'active' : '' ?>" data-tab-target="<?= $catId ?>">
                    <?php 
                        // Optional: Add icons based on category name if desired, or just text
                        $icon = match($categoryName) {
                            'Economy' => '<i class="fas fa-coins"></i>',
                            'Military' => '<i class="fas fa-crosshairs"></i>',
                            'Defense' => '<i class="fas fa-shield-alt"></i>',
                            'Intel' => '<i class="fas fa-satellite"></i>',
                            default => '<i class="fas fa-layer-group"></i>'
                        };
                    ?>
                    <?= $icon ?> <?= htmlspecialchars($categoryName) ?>
                </button>
                <?php $firstCategory = false; ?>
            <?php endforeach; ?>
        </div>

        <!-- Central Viewing Containers -->
        <div class="structure-deck">
            <?php 
            $firstCategory = true;
            foreach ($groupedStructures as $categoryName => $structures): 
                $catId = 'cat-' . md5($categoryName);
            ?>
                <div id="<?= $catId ?>" class="structure-category-container <?= $firstCategory ? 'active' : '' ?>">
                    <div class="structures-grid">
                        <?php foreach ($structures as $struct): ?>
                            <div class="structure-card <?= $struct['is_max_level'] ? 'max-level' : '' ?>">
                                
                                <!-- Card Header -->
                                <div class="card-header-main">
                                    <span class="card-icon"><?= $struct['icon'] ?></span>
                                    <div class="card-title-group">
                                        <h3 class="card-title"><?= htmlspecialchars($struct['name']) ?></h3>
                                        <p class="card-level">Level: <span class="text-white"><?= $struct['current_level'] ?></span></p>
                                    </div>
                                </div>

                                <!-- Card Body -->
                                <div class="card-body-main">
                                    <p class="card-description" style="min-height: 40px;"><?= htmlspecialchars($struct['description']) ?></p>
                                    
                                    <?php if (!empty($struct['benefit_text'])): ?>
                                        <div class="card-benefit mb-3">
                                            <span class="text-neon-blue" style="font-weight: 600; font-size: 0.9rem;">
                                                <i class="fas fa-bolt" style="margin-right: 5px;"></i>
                                                <?= htmlspecialchars($struct['benefit_text']) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!$struct['is_max_level']): ?>
                                        <div class="resource-cost-grid">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted">Upgrade Cost:</span>
                                                <span class="<?= !$struct['can_afford'] ? 'text-danger' : 'text-white' ?> font-weight-bold">
                                                    <?= $struct['cost_formatted'] ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Card Footer (Actions) -->
                                <div class="card-footer-actions p-3 pt-0 flex-gap-sm">
                                    <?php if ($struct['is_max_level']): ?>
                                        <div class="w-100 text-center p-2" style="border: 1px solid var(--accent-2); border-radius: 6px; color: var(--accent-2); background: rgba(249, 199, 79, 0.1);">
                                            <i class="fas fa-check-circle"></i> Max Level
                                        </div>
                                    <?php else: ?>
                                        <button type="button" 
                                                class="btn btn-primary flex-grow-1 btn-upgrade-now" 
                                                data-key="<?= htmlspecialchars($struct['key']) ?>"
                                                <?= !$struct['can_afford'] ? 'disabled' : '' ?>>
                                            Upgrade
                                        </button>
                                        <button type="button" 
                                                class="btn btn-outline-info flex-grow-1 btn-add-cart" 
                                                data-key="<?= htmlspecialchars($struct['key']) ?>"
                                                data-name="<?= htmlspecialchars($struct['name']) ?>"
                                                data-next-level="<?= $struct['next_level'] ?>"
                                                data-cost-credits="<?= $struct['upgrade_cost_credits'] ?>"
                                                <?= !$struct['can_afford'] ? 'disabled' : '' ?>>
                                            <i class="fas fa-plus"></i> Batch
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php $firstCategory = false; ?>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

    <!-- New HUD Floating Bar (Replaces old checkout box) -->
    <div id="structure-checkout-box" class="hud-floating-bar" style="display:none;">
        <div class="hud-header">
            <h3><i class="fas fa-list-ul"></i> Build Queue</h3>
            <button id="btn-cancel-batch" class="hud-btn-close" title="Clear Queue">&times;</button>
        </div>
        
        <div id="checkout-list" class="hud-content">
            <!-- Items injected by JS -->
        </div>
        
        <div class="hud-footer">
            <div class="hud-total-row">
                <span>Total Credits:</span>
                <span class="icon-gold" id="checkout-total-credits">0</span>
            </div>
            
            <form id="checkout-form" action="/structures/batch-upgrade" method="POST" class="mt-3">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                <input type="hidden" name="structure_keys" id="checkout-input-keys" value="">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-hammer"></i> Confirm Construction
                </button>
            </form>
        </div>
    </div>

</div>

<script src="/js/structures.js"></script>
