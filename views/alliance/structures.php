<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\Alliance $alliance */
/* @var \App\Models\Entities\AllianceStructureDefinition[] $definitions */
/* @var array $costs (e.g., ['citadel_shield' => 1000000]) */
/* @var array $currentLevels (e.g., ['citadel_shield' => 0]) */
/* @var bool $canManage */
?>

<style>
    :root {
        --card: radial-gradient(circle at 30% -10%, rgba(45, 209, 209, 0.07), rgba(13, 15, 27, 0.6));
        --border: rgba(255, 255, 255, 0.03);
        --accent: #2dd1d1;
        --accent-2: #f9c74f;
        --text: #eff1ff;
        --muted: #a8afd4;
        --radius: 18px;
        --shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
    }
    
    /* Re-using styles from personal structures page */
    .structures-container {
        width: 100%;
        max-width: 1400px;
        text-align: left;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        margin-inline: auto;
        padding: 1.5rem 0 3.5rem;
        position: relative;
    }
    .structures-container h1 {
        text-align: center;
        margin-bottom: 0.25rem;
        font-size: clamp(2.1rem, 3vw, 2.6rem);
        letter-spacing: -0.03em;
        color: #fff;
    }
    .structures-subtitle {
        text-align: center;
        color: var(--muted);
        margin-bottom: 1rem;
        font-size: 0.85rem;
        margin-top: -1rem;
    }

    /* Top Grid for Finances + Back Button */
    .top-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
    .controls-card {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
        justify-content: flex-end;
    }
    .btn-control {
        padding: 0.6rem 1rem;
        border: 1px solid var(--border);
        background: var(--card);
        color: var(--muted);
        font-weight: 600;
        font-size: 0.85rem;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none; /* For the <a> tag */
    }
    .btn-control:hover {
        background: rgba(255,255,255, 0.03);
        color: #fff;
        border-color: rgba(45, 209, 209, 0.4);
    }

    /* Data card for bank balance */
    .data-card {
        background: radial-gradient(circle at top, rgba(45, 209, 209, 0.12), rgba(11, 13, 24, 0.9));
        border: 1px solid rgba(45, 209, 209, 0.4);
        border-radius: var(--radius);
        padding: 1.25rem 1.5rem 1.1rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        box-shadow: var(--shadow);
    }
    .data-card h3 {
        color: #fff;
        margin-top: 0;
        border-bottom: 1px solid rgba(233, 219, 255, 0.04);
        padding-bottom: 0.5rem;
        font-size: 1rem;
    }
    .data-card ul { list-style: none; padding: 0; margin: 0; }
    .data-card li {
        font-size: 0.9rem;
        color: #e0e0e0;
        padding: 0.35rem 0;
        display: flex;
        justify-content: space-between;
    }
    .data-card li span:first-child { color: rgba(239, 241, 255, 0.7); }
    .data-badge {
        background: rgba(249, 199, 79, 0.14); /* Gold background */
        color: var(--accent-2); /* Gold text */
        border: 1px solid rgba(249, 199, 79, 0.45);
        padding: 0.25rem 0.5rem;
        border-radius: 999px;
        font-size: 0.75rem;
    }

    /* Main grid for structures */
    .structure-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1.5rem;
    }
    
    /* Structure card (from structures/show.php) */
    .structure-card {
        background: radial-gradient(circle at 30% -10%, rgba(45, 209, 209, 0.07), rgba(13, 15, 27, 0.6));
        border: 1px solid rgba(255, 255, 255, 0.01);
        border-radius: var(--radius);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
    }
    .card-header {
        padding: 1rem 1.25rem 1rem;
        background: rgba(6, 7, 14, 0.35);
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }
    .card-header h4 {
        margin: 0;
        font-size: 1rem;
        color: #fff;
    }
    .card-header-stat {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--accent);
        padding-top: 0.35rem;
    }
    .card-header span {
        font-size: 0.75rem;
        color: rgba(232, 232, 255, 0.35);
    }
    .card-body {
        padding: 1rem 1.25rem;
        font-size: 0.85rem;
        color: #e0e0e0;
        flex-grow: 1;
    }
    .card-body p {
        margin: 0 0 0.75rem 0;
        line-height: 1.35;
        color: rgba(232, 232, 255, 0.85);
    }
    .card-body .cost {
        font-size: 0.78rem;
        color: #c0c0e0;
        margin-bottom: 0.35rem;
        display: flex;
        justify-content: space-between;
    }
    .card-body .cost strong {
        color: #fff;
        font-size: 0.8rem;
    }
    .cost-pill {
        background: rgba(249, 199, 79, 0.04);
        border: 1px solid rgba(249, 199, 79, 0.25);
        border-radius: 999px;
        padding: 0.3rem 0.55rem;
        font-size: 0.68rem;
        color: #fff;
    }
    .card-footer {
        padding: 0.7rem 1rem 0.8rem;
        background: rgba(2, 3, 10, 0.9);
        border-top: 1px solid rgba(255, 255, 255, 0.03);
        margin-top: auto;
    }
    .card-footer .btn-submit {
        width: 100%;
        margin-top: 0;
        border: none;
        background: linear-gradient(120deg, #2dd1d1 0%, #1f8ac5 100%);
        color: #fff;
        padding: 0.6rem 0.75rem;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
    }
    .card-footer .btn-submit[disabled] {
        background: rgba(187, 76, 76, 0.15);
        border: 1px solid rgba(187, 76, 76, 0.3);
        color: rgba(255, 255, 255, 0.35);
        cursor: not-allowed;
    }
    
    @media (max-width: 980px) {
        .top-grid, .structure-grid {
            grid-template-columns: 1fr;
        }
        .controls-card {
            justify-content: center;
        }
    }
</style>

<div class="structures-container">
    <h1>Alliance Structures</h1>
    <p class="structures-subtitle">Purchase and upgrade global structures using your alliance bank.</p>

    <div class="top-grid">
        <div class="data-card">
            <h3>Alliance Treasury</h3>
            <ul>
                <li>
                    <span>Bank Credits:</span>
                    <span class="data-badge"><?= number_format($alliance->bank_credits) ?></span>
                </li>
            </ul>
        </div>
        
        <div class="controls-card">
            <a href="/alliance/profile/<?= $alliance->id ?>" class="btn-control">
                &laquo; Back to Alliance Profile
            </a>
        </div>
    </div>
    
    <div class="structure-grid">

        <?php foreach ($definitions as $def):
            $key = $def->structure_key;
            $currentLevel = $currentLevels[$key] ?? 0;
            $nextLevel = $currentLevel + 1;
            $upgradeCost = $costs[$key] ?? 0;
            $canAfford = $alliance->bank_credits >= $upgradeCost;
        ?>
            <div class="structure-card">
                <div class="card-header">
                    <h4><?= htmlspecialchars($def->name) ?></h4>
                    <span class="card-header-stat"><?= htmlspecialchars($def->bonus_text) ?></span>
                    <span>Current Level: <?= $currentLevel ?></span>
                </div>

                <div class="card-body">
                    <p><?= htmlspecialchars($def->description) ?></p>
                    <div class="cost">
                        <span>Next Level</span>
                        <strong><?= $nextLevel ?></strong>
                    </div>
                    <div class="cost">
                        <span>Upgrade Cost</span>
                        <span class="cost-pill"><?= number_format($upgradeCost) ?> C</span>
                    </div>
                    <?php if (!$canAfford): ?>
                        <div class="cost" style="color: rgba(252, 122, 122, 0.9);">
                            <span>Status</span>
                            <span>Not enough credits in bank</span>
                        </div>
                    <?php else: ?>
                        <div class="cost" style="color: rgba(191, 255, 217, 0.8);">
                            <span>Status</span>
                            <span>Affordable</span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($canManage): // Only show form to users with permission ?>
                    <div class="card-footer">
                        <form action="/alliance/structures/upgrade" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                            <input type="hidden" name="structure_key" value="<?= htmlspecialchars($key) ?>">

                            <button type="submit" class="btn-submit" <?= !$canAfford ? 'disabled' : '' ?>>
                                <?= $canAfford ? 'Upgrade to Level ' . $nextLevel : 'Not Enough Credits' ?>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

    </div> 
</div>