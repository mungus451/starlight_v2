<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\UserResource $resources */
/* @var array $costs (e.g., ['soldiers' => ['credits' => 1000, 'citizens' => 1]]) */
?>

<style>
    :root {
        --bg: radial-gradient(circle at 10% 0%, #0c101e 0%, #050712 42%, #02030a 75%);
        --bg-panel: rgba(12, 14, 25, 0.65);
        --card: radial-gradient(circle at 30% -10%, rgba(45, 209, 209, 0.07), rgba(13, 15, 27, 0.6));
        --border: rgba(255, 255, 255, 0.03);
        --accent: #2dd1d1; /* Main Accent (Teal) */
        --accent-soft: rgba(45, 209, 209, 0.12);
        --accent-2: #f9c74f; /* Secondary Accent (Gold) */
        --accent-red: #e53e3e;
        --text: #eff1ff;
        --muted: #a8afd4;
        --radius: 18px;
        --shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
    }

    /* --- Base Container --- */
    .training-container {
        width: 100%;
        max-width: 1400px;
        margin-inline: auto;
        padding: 0; /* Let main.php handle horizontal padding */
        position: relative;
    }

    .training-container h1 {
        text-align: center;
        margin-bottom: 0.25rem;
        font-size: clamp(2.1rem, 3vw, 2.6rem);
        letter-spacing: -0.03em;
        color: #fff;
        padding-top: 1.5rem;
    }

    /* --- Header Card --- */
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
    .header-stat strong.accent-gold {
        color: var(--accent-2);
    }
    .header-stat strong.accent-teal {
        color: var(--accent);
    }
    
    /* --- Grid for Unit Cards --- */
    .item-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr); /* 3 cards on desktop */
        gap: 1.5rem;
    }
    
    @media (max-width: 980px) {
        .item-grid {
            grid-template-columns: repeat(2, 1fr); /* 2 cards on tablet */
        }
    }
    @media (max-width: 768px) {
        .item-grid {
            grid-template-columns: 1fr; /* 1 card on mobile */
        }
    }

    /* --- Unit Card (from Armory/Structures) --- */
    .item-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.25rem 1.5rem;
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
        transition: transform 0.1s ease-out, border 0.1s ease-out;
    }
    .item-card:hover {
        transform: translateY(-2px);
        border: 1px solid rgba(45, 209, 209, 0.4);
    }
    .item-card h4 {
        color: #fff;
        margin: 0 0 1rem 0;
        font-size: 1.1rem;
        border-bottom: 1px solid var(--border);
        padding-bottom: 0.75rem;
    }
    
    .item-info { list-style: none; padding: 0; margin: 0 0 1rem 0; flex-grow: 1; /* Pushes form to bottom */ }
    .item-info li {
        font-size: 0.9rem;
        color: var(--muted);
        padding: 0.35rem 0;
        display: flex;
        justify-content: space-between;
        border-bottom: 1px solid var(--border);
    }
    .item-info li:last-child { border: none; }
    .item-info li strong {
        color: var(--text);
        font-weight: 600;
    }
    .item-info li .cost-credits { color: var(--accent-2); }
    .item-info li .cost-citizens { color: var(--accent); }
    
    .amount-input-group {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }
    .amount-input-group input {
        flex-grow: 1; /* Input takes up all available space */
        min-width: 50px; /* Prevents it from becoming too small */
    }
    .amount-input-group button {
        margin-top: 0;
        flex-shrink: 0; /* Prevents button from shrinking */
    }
    
    /* --- CSS FIX: Base button styles --- */
    .item-card .btn-submit {
        margin-top: 0;
        border: none;
        background: linear-gradient(120deg, var(--accent) 0%, #1f8ac5 100%);
        color: #fff;
        padding: 0.6rem 0.75rem;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: filter 0.1s ease-out, transform 0.1s ease-out;
    }

    .item-card .btn-submit:not([disabled]):hover {
        filter: brightness(1.02);
        transform: translateY(-1px);
    }
    .item-card .btn-submit[disabled] {
        background: rgba(187, 76, 76, 0.15);
        border: 1px solid rgba(187, 76, 76, 0.3);
        color: rgba(255, 255, 255, 0.35);
        cursor: not-allowed;
    }
    
    /* --- CSS FIX: Specific width for Train button ONLY --- */
    .item-card .btn-train-submit {
        width: 100%;
    }
</style>

<div class="training-container">
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

    <div class="item-grid">
        <?php foreach ($costs as $unitKey => $unitData): ?>
            <?php
            // Find the property name on the $resources object.
            $ownedKey = $unitKey; // This should work directly
            $ownedCount = $resources->{$ownedKey} ?? 0;
            ?>
            <div class="item-card">
                <form action="/training/train" method="POST" class="train-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    <input type="hidden" name="unit_type" value="<?= $unitKey ?>">
                    
                    <h4><?= htmlspecialchars(ucfirst($unitKey)) ?></h4>

                    <ul class="item-info">
                        <li>
                            <span>Cost (Credits):</span> 
                            <strong class="cost-credits"><?= number_format($unitData['credits']) ?></strong>
                        </li>
                        <li>
                            <span>Cost (Citizens):</span> 
                            <strong class="cost-citizens"><?= number_format($unitData['citizens']) ?></strong>
                        </li>
                        <li>
                            <span>Currently Owned:</span> 
                            <strong><?= number_format($ownedCount) ?></strong>
                        </li>
                    </ul>
                    
                    <div class="form-group">
                        <div class="amount-input-group">
                            <input type="number" name="amount" class="train-amount" min="1" placeholder="Amount" required
                                   data-credit-cost="<?= $unitData['credits'] ?>"
                                   data-citizen-cost="<?= $unitData['citizens'] ?>"
                            >
                            <button type="button" class="btn-submit btn-max-train">Max</button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit btn-train-submit">Train</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="/js/training.js"></script>