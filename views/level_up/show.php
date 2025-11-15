<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\UserStats $stats */
/* @var array $costs */
?>

<style>
    :root {
        --card: radial-gradient(circle at 30% -10%, rgba(45, 209, 209, 0.07), rgba(13, 15, 27, 0.6));
        --border: rgba(255, 255, 255, 0.03);
        --accent: #2dd1d1;
        --accent-2: #f9c74f;
        --accent-green: #4CAF50;
        --text: #eff1ff;
        --muted: #a8afd4;
        --radius: 18px;
        --shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
    }

    /* --- Base Container --- */
    .levelup-container-full {
        width: 100%;
        max-width: 1400px;
        margin-inline: auto;
        padding: 0;
        position: relative;
    }

    .levelup-container-full h1 {
        text-align: center;
        margin-bottom: 0.25rem;
        font-size: clamp(2.1rem, 3vw, 2.6rem);
        letter-spacing: -0.03em;
        color: #fff;
        padding-top: 1.5rem;
    }

    /* --- Header Card (from Bank) --- */
    .resource-header-card {
        background: radial-gradient(circle at top, rgba(45, 209, 209, 0.12), rgba(11, 13, 24, 0.9));
        border: 1px solid rgba(45, 209, 209, 0.4);
        border-radius: var(--radius);
        padding: 1rem 1.5rem;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-around;
        gap: 1.5rem;
        box-shadow: var(--shadow);
        backdrop-filter: blur(6px);
        margin-bottom: 2rem;
        max-width: 600px; /* Constrain header */
        margin-inline: auto; /* Center header */
    }
    .header-stat {
        text-align: center;
        flex-grow: 1;
    }
    .header-stat span {
        display: block;
        font-size: 0.9rem;
        color: var(--muted);
        margin-bottom: 0.25rem;
    }
    .header-stat strong {
        font-size: 1.5rem;
        color: #fff;
    }
    .header-stat strong.accent-green {
        color: var(--accent-green);
    }

    /* --- Grid for Action Cards (from Bank) --- */
    .item-grid {
        display: grid;
        grid-template-columns: 1fr; /* Single column */
        gap: 1.5rem;
        max-width: 800px; /* Constrain the form card */
        margin: 0 auto; /* Center the form grid */
    }
    
    /* --- Action Card (from Bank/Training) --- */
    .item-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.25rem 1.5rem;
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
    }
    .item-card h4 {
        color: #fff;
        margin: 0 0 1rem 0;
        font-size: 1.1rem;
        border-bottom: 1px solid var(--border);
        padding-bottom: 0.75rem;
    }
    
    /* --- New grid for stat inputs --- */
    .stat-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem 1.5rem;
    }
    @media (max-width: 600px) {
        .stat-grid {
            grid-template-columns: 1fr;
        }
    }
    
    .stat-total {
        font-size: 1.2rem;
        font-weight: bold;
        color: var(--accent-2);
        text-align: center;
        margin-top: 1.5rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border);
    }

    .item-card .btn-submit {
        width: 100%;
        margin-top: 1rem;
        border: none;
        background: linear-gradient(120deg, var(--accent) 0%, #1f8ac5 100%);
        color: #fff;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: filter 0.1s ease-out, transform 0.1s ease-out;
    }
    .item-card .btn-submit:not([disabled]):hover {
        filter: brightness(1.02);
        transform: translateY(-1px);
    }
</style>

<div class="levelup-container-full">
    <h1>Level Up</h1>

    <div class="resource-header-card">
        <div class="header-stat">
            <span>Available Points</span>
            <strong class="accent-green" id="available-points" data-points="<?= $stats->level_up_points ?>">
                <?= number_format($stats->level_up_points) ?>
            </strong>
        </div>
    </div>

    <div class="item-grid">
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
                    Total to Spend: <span id="total-to-spend">0</span> / <?= $stats->level_up_points ?>
                </div>

                <button type="submit" class="btn-submit" id="spend-points-btn" disabled>Spend Points</button>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('level-up-form');
        const inputs = form.querySelectorAll('.stat-input');
        const totalSpan = document.getElementById('total-to-spend');
        const availablePoints = parseInt(document.getElementById('available-points').getAttribute('data-points'), 10);
        const submitBtn = document.getElementById('spend-points-btn');
        const costPerPoint = <?= $costs['cost_per_point'] ?>;

        function updateTotal() {
            let totalPointsAllocated = 0;
            inputs.forEach(input => {
                let val = parseInt(input.value, 10);
                if (isNaN(val) || val < 0) {
                    val = 0;
                }
                totalPointsAllocated += val;
            });
            
            const totalCost = totalPointsAllocated * costPerPoint;
            totalSpan.textContent = totalCost;

            // Disable button if spending 0 points or more than available
            if (totalCost > 0 && totalCost <= availablePoints) {
                submitBtn.disabled = false;
                submitBtn.textContent = `Spend ${totalCost} Points`;
            } else if (totalCost > availablePoints) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Not Enough Points';
            } else {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Spend Points';
            }
        }
        
        inputs.forEach(input => {
            input.addEventListener('input', updateTotal);
        });

        // Initial check in case form is pre-filled
        updateTotal();
    });
</script>