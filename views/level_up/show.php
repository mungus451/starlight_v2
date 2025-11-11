<style>
    .levelup-container {
        width: 100%;
        max-width: 800px;
        text-align: left;
    }
    .levelup-container h1 {
        text-align: center;
    }
    .data-card {
        background: #2a2a4a;
        border: 1px solid #3a3a5a;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .data-card h3 {
        color: #f9c74f;
        margin-top: 0;
        border-bottom: 1px solid #3a3a5a;
        padding-bottom: 0.5rem;
    }
    .data-card ul {
        list-style: none;
        padding-left: 0;
        margin: 0;
    }
    .data-card li {
        font-size: 1.2rem;
        color: #e0e0e0;
        padding: 0.25rem 0;
        display: flex;
        justify-content: space-between;
    }
    .data-card li span:first-child {
        font-weight: bold;
        color: #c0c0e0;
    }
    .stat-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    @media (min-width: 768px) {
        .stat-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
    .stat-total {
        font-size: 1.2rem;
        font-weight: bold;
        color: #f9c74f;
        text-align: center;
        margin-top: 1rem;
    }
</style>

<div class="levelup-container">
    <h1>Level Up</h1>

    <div class="data-card">
        <h3>Available Points</h3>
        <ul>
            <li>
                <span>Points to Spend:</span> 
                <span><?= number_format($stats->level_up_points) ?></span>
            </li>
        </ul>
    </div>

    <div class="data-card">
        <h3>Allocate Points (Cost: <?= $costs['cost_per_point'] ?> per stat)</h3>
        <form action="/level-up/spend" method="POST" id="level-up-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            
            <div class="stat-grid">
                <div class="form-group">
                    <label for="strength">Strength (Current: <?= $stats->strength_points ?>)</label>
                    <input type="number" name="strength" id="strength" class="stat-input" min="0" value="0">
                </div>
                <div class="form-group">
                    <label for="constitution">Constitution (Current: <?= $stats->constitution_points ?>)</label>
                    <input type="number" name="constitution" id="constitution" class="stat-input" min="0" value="0">
                </div>
                <div class="form-group">
                    <label for="wealth">Wealth (Current: <?= $stats->wealth_points ?>)</label>
                    <input type="number" name="wealth" id="wealth" class="stat-input" min="0" value="0">
                </div>
                <div class="form-group">
                    <label for="dexterity">Dexterity (Current: <?= $stats->dexterity_points ?>)</label>
                    <input type="number" name="dexterity" id="dexterity" class="stat-input" min="0" value="0">
                </div>
                <div class="form-group">
                    <label for="charisma">Charisma (Current: <?= $stats->charisma_points ?>)</label>
                    <input type="number" name="charisma" id="charisma" class="stat-input" min="0" value="0">
                </div>
            </div>
            
            <div class="stat-total">
                Total to Spend: <span id="total-to-spend">0</span> / <?= $stats->level_up_points ?>
            </div>

            <button type="submit" class="btn-submit">Spend Points</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('level-up-form');
        const inputs = form.querySelectorAll('.stat-input');
        const totalSpan = document.getElementById('total-to-spend');
        
        function updateTotal() {
            let total = 0;
            inputs.forEach(input => {
                let val = parseInt(input.value, 10);
                if (isNaN(val) || val < 0) {
                    val = 0;
                }
                total += val;
            });
            totalSpan.textContent = total;
        }
        
        inputs.forEach(input => {
            input.addEventListener('input', updateTotal);
        });
    });
</script>