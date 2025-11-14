<style>
    :root {
        --bg: radial-gradient(circle at 10% 0%, #0c101e 0%, #050712 42%, #02030a 75%);
        --bg-panel: rgba(12, 14, 25, 0.65);
        --card: rgba(17, 20, 34, 0.75);
        --border: rgba(255, 255, 255, 0.03);
        --accent: #2dd1d1; /* calmer teal */
        --accent-soft: rgba(45, 209, 209, 0.12);
        --accent-2: #f9c74f;
        --text: #eff1ff;
        --muted: #a8afd4;
        --radius: 18px;
        --shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
    }

    .structures-container {
        width: 100%;
        max-width: 1400px;
        text-align: left;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr)); /* 2 columns now */
        gap: 1.5rem;
        margin-inline: auto;
        padding: 1.5rem 1.5rem 3.5rem;
        position: relative;
    }

    /* faint grid overlay for sci-fi feel */
    .structures-container::before {
        content: "";
        position: absolute;
        inset: -80px -120px 0;
        background-image:
            linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 0),
            linear-gradient(0deg, rgba(255,255,255,0.015) 1px, transparent 0);
        background-size: 120px 120px;
        pointer-events: none;
        z-index: -1;
    }

    .structures-container h1 {
        text-align: center;
        grid-column: 1 / -1;
        margin-bottom: 0.25rem;
        font-size: clamp(2.1rem, 3vw, 2.6rem);
        letter-spacing: -0.03em;
        color: #fff;
    }

    .structures-subtitle {
        grid-column: 1 / -1;
        text-align: center;
        color: var(--muted);
        margin-bottom: 1rem;
        font-size: 0.85rem;
    }

    /* Sidebar / info card */
    .data-card {
        background: radial-gradient(circle at top, rgba(45, 209, 209, 0.12), rgba(11, 13, 24, 0.9));
        border: 1px solid rgba(45, 209, 209, 0.4);
        border-radius: var(--radius);
        padding: 1.25rem 1.5rem 1.1rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        box-shadow: var(--shadow);
        backdrop-filter: blur(6px);
    }
    .data-card h3 {
        color: #fff;
        margin-top: 0;
        border-bottom: 1px solid rgba(233, 219, 255, 0.04);
        padding-bottom: 0.5rem;
        font-size: 1rem;
        letter-spacing: 0.02em;
    }
    .data-card ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .data-card li {
        font-size: 0.9rem;
        color: #e0e0e0;
        padding: 0.35rem 0;
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: center;
    }
    .data-card li span:first-child {
        font-weight: 500;
        color: rgba(239, 241, 255, 0.7);
    }
    .data-badge {
        background: rgba(45, 209, 209, 0.14);
        color: #fefefe;
        border: 1px solid rgba(45, 209, 209, 0.45);
        padding: 0.25rem 0.5rem;
        border-radius: 999px;
        font-size: 0.75rem;
    }

    /* Category header */
    .structure-category-header {
        grid-column: 1 / -1;
        color: #ffffff;
        font-size: 0.95rem;
        font-weight: 600;
        border-bottom: 1px solid rgba(249, 199, 79, 0.06);
        padding-bottom: 0.3rem;
        margin: 0.6rem 0 0.2rem 0;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .structure-category-header::before {
        content: "";
        width: 5px;
        height: 18px;
        border-radius: 999px;
        background: linear-gradient(180deg, #2dd1d1, rgba(2, 3, 10, 0));
        box-shadow: 0 0 20px rgba(45, 209, 209, 0.35);
    }

    /* Structure cards */
    .structure-card {
        background: radial-gradient(circle at 30% -10%, rgba(45, 209, 209, 0.07), rgba(13, 15, 27, 0.6));
        border: 1px solid rgba(255, 255, 255, 0.01);
        border-radius: var(--radius);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
        transition: transform 0.15s ease-out, border 0.15s ease-out;
        position: relative;
    }
    .structure-card:hover {
        transform: translateY(-2px);
        border: 1px solid rgba(45, 209, 209, 0.4);
    }

    .card-header {
        padding: 1rem 1.25rem 0.6rem;
        background: rgba(6, 7, 14, 0.35);
        cursor: pointer;
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }
    .card-header h4 {
        margin: 0;
        font-size: 1rem;
        color: #fff;
        display: flex;
        gap: 0.35rem;
        align-items: center;
    }
    .card-header span {
        font-size: 0.75rem;
        color: rgba(232, 232, 255, 0.35);
    }

    .card-body {
        padding: 0 1.25rem;
        font-size: 0.85rem;
        color: #e0e0e0;
        flex-grow: 1;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.4s ease-out, padding-top 0.4s ease-out, padding-bottom 0.4s ease-out;
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
        gap: 0.5rem;
        align-items: center;
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
        padding: 0 1rem;
        background: rgba(2, 3, 10, 0.9);
        border-top: none;
        margin-top: auto;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.4s ease-out, padding-top 0.4s ease-out, padding-bottom 0.4s ease-out, border-top 0.4s ease-out;
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
        transition: filter 0.1s ease-out, transform 0.1s ease-out;
    }
    .card-footer .btn-submit:not([disabled]):hover {
        filter: brightness(1.02);
        transform: translateY(-1px);
    }
    .card-footer .btn-submit[disabled] {
        background: rgba(187, 76, 76, 0.15);
        border: 1px solid rgba(187, 76, 76, 0.3);
        color: rgba(255, 255, 255, 0.35);
        cursor: not-allowed;
    }

    /* Expanded state (kept the same class hooks) */
    .structure-card.is-expanded .card-body {
        max-height: 480px;
        padding-top: 1rem;
        padding-bottom: 1rem;
    }
    .structure-card.is-expanded .card-footer {
        max-height: 140px;
        padding-top: 0.7rem;
        padding-bottom: 0.8rem;
        border-top: 1px solid rgba(255, 255, 255, 0.03);
    }

    /* Responsive */
    @media (max-width: 980px) {
        .structures-container {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php
// --- Grouping Logic (unchanged) ---
$groupedStructures = [];
foreach ($structureFormulas as $key => $details) {
    $category = $details['category'] ?? 'Uncategorized';
    $details['key'] = $key;
    $groupedStructures[$category][] = $details;
}
$categoryOrder = ['Economy', 'Defense', 'Offense', 'Intel'];
?>

<div class="structures-container">
    <h1>Structures</h1>
    <p class="structures-subtitle">Expand a structure to view its description, next level, and upgrade cost.</p>

    <!-- Sidebar: Finances -->
    <div class="data-card">
        <h3>Finances</h3>
        <ul>
            <li>
                <span>Credits:</span>
                <span class="data-badge"><?= number_format($resources->credits) ?></span>
            </li>
        </ul>
    </div>

    <?php
    foreach ($categoryOrder as $categoryName):
        if (!isset($groupedStructures[$categoryName])) continue;

        $structuresInCategory = $groupedStructures[$categoryName];
    ?>
        <h2 class="structure-category-header"><?= htmlspecialchars($categoryName) ?></h2>

        <?php foreach ($structuresInCategory as $details):
            $key = $details['key'];
            $columnName = $key . '_level';
            $currentLevel = $structures->{$columnName} ?? 0;
            $nextLevel = $currentLevel + 1;
            $upgradeCost = $costs[$key] ?? 0;
            $displayName = $details['name'] ?? 'Unknown';
            $description = $details['description'] ?? 'No description available.';
            $canAfford = $resources->credits >= $upgradeCost;
        ?>
            <div class="structure-card">
                <div class="card-header">
                    <h4><?= htmlspecialchars($displayName) ?></h4>
                    <span>Current Level: <?= $currentLevel ?></span>
                </div>

                <div class="card-body">
                    <p><?= htmlspecialchars($description) ?></p>
                    <div class="cost">
                        <span>Next Level</span>
                        <strong><?= $nextLevel ?></strong>
                    </div>
                    <div class="cost">
                        <span>Cost</span>
                        <span class="cost-pill"><?= number_format($upgradeCost) ?> C</span>
                    </div>
                    <?php if (!$canAfford): ?>
                        <div class="cost" style="color: rgba(252, 122, 122, 0.9);">
                            <span>Status</span>
                            <span>Not enough credits</span>
                        </div>
                    <?php else: ?>
                        <div class="cost" style="color: rgba(191, 255, 217, 0.8);">
                            <span>Status</span>
                            <span>Affordable</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-footer">
                    <form action="/structures/upgrade" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                        <input type="hidden" name="structure_key" value="<?= htmlspecialchars($key) ?>">

                        <button type="submit" class="btn-submit" <?= !$canAfford ? 'disabled' : '' ?>>
                            <?= $canAfford ? 'Upgrade to Level ' . $nextLevel : 'Not Enough Credits' ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const allHeaders = document.querySelectorAll('.card-header');
        allHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const card = this.closest('.structure-card');
                if (card) {
                    card.classList.toggle('is-expanded');
                }
            });
        });
    });
</script>
