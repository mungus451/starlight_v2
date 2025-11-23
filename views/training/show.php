<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\UserResource $resources */
/* @var array $costs (e.g., ['soldiers' => ['credits' => 1000, 'citizens' => 1]]) */
?>

<div class="container-full">
    <h1>Training</h1>

    <div class="resource-header-card">
        <div class="header-stat">
            <span>Credits on Hand</span>
            <strong class="accent-gold" id="global-user-credits" data-credits="<?= $resources->credits ?>">
                <?= number_format($resources->credits) ?>
            </strong>
        </div>
        <div class="header-stat">
            <span>Untrained Citizens</span>
            <strong class="accent-teal" id="global-user-citizens" data-citizens="<?= $resources->untrained_citizens ?>">
                <?= number_format($resources->untrained_citizens) ?>
            </strong>
        </div>
    </div>

    <!-- Grid-3 modifier for 3 columns on desktop -->
    <div class="item-grid grid-3">
        <?php foreach ($costs as $unitKey => $unitData): ?>
            <?php
            // Find the property name on the $resources object.
            $ownedKey = $unitKey; 
            $ownedCount = $resources->{$ownedKey} ?? 0;
            ?>
            <div class="item-card">
                <form action="/training/train" method="POST" class="train-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    <input type="hidden" name="unit_type" value="<?= $unitKey ?>">
                    
                    <h4><?= htmlspecialchars(ucfirst($unitKey)) ?></h4>

                    <!-- Using card-stats-list from Dashboard for consistency -->
                    <ul class="card-stats-list">
                        <li>
                            <span>Cost (Credits)</span> 
                            <strong class="accent"><?= number_format($unitData['credits']) ?></strong>
                        </li>
                        <li>
                            <span>Cost (Citizens)</span> 
                            <strong class="accent-teal"><?= number_format($unitData['citizens']) ?></strong>
                        </li>
                        <li>
                            <span>Owned</span> 
                            <strong><?= number_format($ownedCount) ?></strong>
                        </li>
                    </ul>
                    
                    <div class="form-group">
                        <div class="amount-input-group">
                            <input type="number" name="amount" class="train-amount" min="1" placeholder="Amount" required
                                   data-credit-cost="<?= $unitData['credits'] ?>"
                                   data-citizen-cost="<?= $unitData['citizens'] ?>"
                            >
                            <button type="button" class="btn-submit btn-accent btn-max-train">Max</button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit" style="width: 100%;">Train</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="/js/training.js"></script>