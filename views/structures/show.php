<style>
    .structures-two-column-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
        align-items: flex-start;
    }

    .structures-grid-pane {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
    }

    .structure-details-pane {
        position: sticky;
        top: 2rem;
        background: rgba(13, 17, 23, 0.9);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 1.5rem;
    }
</style>

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
        <!-- Left Column: Grid of all structures -->
        <div class="structures-grid-pane">
            <?php foreach ($groupedStructures as $categoryName => $structures): ?>
                <?php foreach ($structures as $struct): ?>
                    <div class="structure-card interactive <?= $struct['is_max_level'] ? 'max-level' : '' ?>" 
                         data-key="<?= htmlspecialchars($struct['key']) ?>"
                         data-name="<?= htmlspecialchars($struct['name']) ?>"
                         data-level="<?= $struct['current_level'] ?>"
                         data-description="<?= htmlspecialchars($struct['description']) ?>"
                         data-benefit-text="<?= htmlspecialchars($struct['benefit_text']) ?>"
                         data-cost-formatted="<?= htmlspecialchars($struct['cost_formatted']) ?>"
                         data-is-max-level="<?= $struct['is_max_level'] ?>"
                         data-can-afford="<?= $struct['can_afford'] ?>">
                        
                        <div class="card-header-main">
                            <span class="card-icon"><?= $struct['icon'] ?></span>
                            <div class="card-title-group">
                                <h3 class="card-title"><?= htmlspecialchars($struct['name']) ?></h3>
                                <p class="card-level">Level: <span class="text-white"><?= $struct['current_level'] ?></span></p>
                            </div>
                        </div>
                        <div class="card-body-main">
                             <p class="card-description" style="min-height: 40px;"><?= htmlspecialchars($struct['description']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>

        <!-- Right Column: Details Pane -->
        <div class="structure-details-pane" id="structure-details-pane" style="display: none;">
            <h2 id="details-name" class="text-neon-blue">Select a Structure</h2>
            <p id="details-description" class="text-muted"></p>
            <hr class="border-secondary">
            <div id="details-body">
                <!-- Content will be injected by JS -->
            </div>
        </div>
    </div>
</div>

<script src="/js/structures.js"></script>