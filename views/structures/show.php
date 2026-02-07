<link rel="stylesheet" href="/css/structures.css">

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
            Construct and enhance your imperial infrastructure.
        </p>
    </div>

    <div class="structures-two-column-layout">
        <!-- Left Column: Selectable List of Structures -->
        <div class="requisition-grid">
            <?php foreach ($groupedStructures as $categoryName => $structures): ?>
                <h4 class="category-header"><?= htmlspecialchars($categoryName) ?></h4>
                <?php foreach ($structures as $struct): ?>
                    <div class="unit-row interactive <?= $struct['is_max_level'] ? 'max-level' : '' ?>"
                         data-key="<?= htmlspecialchars($struct['key']) ?>"
                         data-name="<?= htmlspecialchars($struct['name']) ?>"
                         data-level="<?= $struct['current_level'] ?>"
                         data-max-level="<?= $struct['max_level'] ?>"
                         data-description="<?= htmlspecialchars($struct['description']) ?>"
                         data-benefit-text="<?= htmlspecialchars($struct['benefit_text']) ?>"
                         data-cost-credits="<?= $struct['upgrade_cost_credits'] ?>"
                         data-is-max-level="<?= $struct['is_max_level'] ? 'true' : 'false' ?>"
                         data-can-afford="<?= $struct['can_afford'] ? 'true' : 'false' ?>"
                         data-icon='<?= $struct['icon'] ?>'
                         onclick="selectStructure(this)">
                        
                        <div class="unit-icon-box">
                            <?= $struct['icon'] ?>
                        </div>
                        
                        <div class="unit-info">
                            <h4><?= htmlspecialchars($struct['name']) ?></h4>
                            <div class="meta">
                                Level: <?= $struct['current_level'] ?> / <?= $struct['max_level'] ?>
                            </div>
                        </div>

                        <div class="unit-controls">
                            <?php if ($struct['is_max_level']): ?>
                                <span class="badge bg-success">MAX</span>
                            <?php elseif (!$struct['can_afford']): ?>
                                <span class="badge bg-danger">INSUFFICIENT</span>
                            <?php else: ?>
                                <span class="cost-preview text-warning">
                                    <?= number_format($struct['upgrade_cost_credits'] ?? 0) ?> Cr
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>

        <!-- Right Column: Inspector Pane -->
        <div class="tactical-inspector" id="inspector-panel">
            <div class="inspector-header">
                <h3 class="inspector-title" id="insp-title">SELECT STRUCTURE</h3>
            </div>

            <div class="wireframe-container">
                <div class="wireframe-placeholder" id="insp-wireframe">
                    <span id="insp-icon" style="font-size: 3rem; position: absolute; top: 50%; left: 50%; transform: translate(--50%, -50%);"></span>
                </div>
            </div>

            <div class="inspector-body">
                <p class="lore-text" id="insp-desc">
                    Select a structure from the requisition grid to view details and manage construction.
                </p>

                <div id="insp-details" style="display:none;">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Current Level:</span>
                        <strong id="insp-level"></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Next Level Benefit:</span>
                        <strong id="insp-benefit" class="text-success"></strong>
                    </div>

                    <hr class="border-secondary">
                    
                    <h4 class="text-neon-blue">UPGRADE COST</h4>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Credits:</span>
                        <strong id="insp-cost-credits" class="text-warning"></strong>
                    </div>

                    <form action="/structures/upgrade" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                        <input type="hidden" name="structure_key" id="insp-structure-key" value="">
                        
                        <button type="submit" class="btn btn-primary w-100" id="btn-confirm">
                            <i class="fas fa-hammer"></i> Begin Construction
                        </button>
                    </form>
                </div>

                <div id="insp-max-level-notice" style="display:none;">
                    <div class="alert alert-success text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h4 class="alert-heading">MAXIMUM LEVEL REACHED</h4>
                        <p>This structure has been fully upgraded.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/js/structures.js"></script>