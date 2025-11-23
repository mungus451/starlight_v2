<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\UserStats $stats */
/* @var array $costs */
?>

<!-- No outer container; main.php provides .container -->

<h1>Level Up</h1>

<div class="resource-header-card" style="max-width: 600px; margin-left: auto; margin-right: auto;">
    <div class="header-stat">
        <span>Available Points</span>
        <strong class="accent-green" id="available-points" data-points="<?= $stats->level_up_points ?>">
            <?= number_format($stats->level_up_points) ?>
        </strong>
    </div>
</div>

<div class="item-grid" style="grid-template-columns: 1fr; max-width: 800px; margin: 0 auto;">
    <div class="item-card">
        <h4>Allocate Points (Cost: <?= $costs['cost_per_point'] ?> per stat)</h4>
        
        <form action="/level-up/spend" method="POST" id="level-up-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            
            <div class="stat-grid">
                <div class="form-group">
                    <label for="strength">Strength (Current: <?= $stats->strength_points ?>)</label>
                    <input type="number" name="strength" id="strength" class="stat-input" min="0" value="0" data-cost="<?= $costs['cost_per_point'] ?>">
                </div>
                <div class="form-group">
                    <label for="constitution">Constitution (Current: <?= $stats->constitution_points ?>)</label>
                    <input type="number" name="constitution" id="constitution" class="stat-input" min="0" value="0" data-cost="<?= $costs['cost_per_point'] ?>">
                </div>
                <div class="form-group">
                    <label for="wealth">Wealth (Current: <?= $stats->wealth_points ?>)</label>
                    <input type="number" name="wealth" id="wealth" class="stat-input" min="0" value="0" data-cost="<?= $costs['cost_per_point'] ?>">
                </div>
                <div class="form-group">
                    <label for="dexterity">Dexterity (Current: <?= $stats->dexterity_points ?>)</label>
                    <input type="number" name="dexterity" id="dexterity" class="stat-input" min="0" value="0" data-cost="<?= $costs['cost_per_point'] ?>">
                </div>
                <div class="form-group">
                    <label for="charisma">Charisma (Current: <?= $stats->charisma_points ?>)</label>
                    <input type="number" name="charisma" id="charisma" class="stat-input" min="0" value="0" data-cost="<?= $costs['cost_per_point'] ?>">
                </div>
            </div>
            
            <div class="stat-total">
                Total to Spend: <span id="total-to-spend">0</span> / <?= number_format($stats->level_up_points) ?>
            </div>

            <button type="submit" class="btn-submit" id="spend-points-btn" disabled>Spend Points</button>
        </form>
    </div>
</div>

<script src="/js/level_up.js"></script>