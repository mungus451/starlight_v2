<?php
/**
 * @var array $structureFormulas Array of all structure definitions.
 * @var \App\Models\Entities\UserStructure $structures User's current structure levels.
 * @var \App\Models\Entities\UserResource $resources User's current resources.
 * @var array $costs Array of upgrade costs for next level.
 * @var array $turnConfig Global turn configuration.
 * @var array $attackConfig Global attack configuration.
 * @var array $spyConfig Global spy configuration.
 * @var string $csrf_token CSRF token for forms.
 */

// --- Grouping Logic (unchanged from original) ---
$groupedStructures = [];
foreach ($structureFormulas as $key => $details) {
    $category = $details['category'] ?? 'Uncategorized';
    $details['key'] = $key;
    $groupedStructures[$category][] = $details;
}
$categoryOrder = ['Economy', 'Defense', 'Offense', 'Intel'];
?>

<style>
    /* Reset & Base Styles - Inherited from main.php or global styles */
    :root {
        --bg: radial-gradient(circle at 10% 0%, #0c101e 0%, #050712 42%, #02030a 75%);
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
        --card-bg-hover: rgba(249, 199, 79, 0.07); /* Light gold tint for hover */
        --max-level-glow: 0 0 15px rgba(249, 199, 79, 0.5);
    }

    .structures-dashboard {
        width: 100%;
        max-width: 1400px;
        margin-inline: auto;
        padding: 1.5rem 1.5rem 3.5rem;
        display: flex;
        flex-direction: column;
        gap: 2rem;
        position: relative;
    }

    .structures-dashboard h1 {
        text-align: center;
        margin-bottom: 0.5rem;
        font-size: clamp(2.2rem, 4vw, 3rem);
        letter-spacing: -0.05em;
        color: #fff;
        text-shadow: 0 0 15px rgba(45, 209, 209, 0.3);
    }

    .structures-dashboard .subtitle {
        text-align: center;
        color: var(--muted);
        font-size: 0.95rem;
        margin-top: -0.5rem;
        margin-bottom: 1.5rem;
    }

    /* --- User Resources Card --- */
    .user-resources-card {
        background: radial-gradient(circle at top, rgba(45, 209, 209, 0.15), rgba(11, 13, 24, 0.95));
        border: 1px solid rgba(45, 209, 209, 0.4);
        border-radius: var(--radius);
        padding: 1.5rem 2rem;
        box-shadow: var(--shadow);
        backdrop-filter: blur(6px);
        display: flex;
        flex-wrap: wrap;
        justify-content: center; /* Changed from space-around to center */
        gap: 2.5rem; /* Increased gap */
        margin-bottom: 2rem;
    }

    .resource-item {
        text-align: center;
        flex-basis: 150px; /* Give it a base width */
    }
    .resource-item .icon {
        font-size: 2.5rem; /* Larger icons */
        color: var(--accent);
        margin-bottom: 0.5rem;
        display: block;
    }
    .resource-item .value {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 0.25rem;
    }
    .resource-item .label {
        font-size: 0.9rem;
        color: var(--muted);
    }
    /* Specific icons */
    .icon-credits::before { content: '◎'; } /* Placeholder for Credits icon */
    .icon-energy::before { content: '⚡'; } /* Placeholder for Energy icon */
    .icon-soldiers::before { content: '⚔️'; } /* Placeholder for Soldiers icon */

    /* --- Structures Grid --- */
    .structures-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .structure-category {
        grid-column: 1 / -1; /* Span full width */
        margin-top: 1.5rem;
        margin-bottom: 1rem;
        text-align: left;
    }
    .structure-category h2 {
        font-size: 1.7rem;
        color: var(--accent-2);
        letter-spacing: 0.05em;
        text-transform: uppercase;
        margin: 0;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid rgba(249, 199, 79, 0.2);
        display: inline-block;
        position: relative;
        text-shadow: 0 0 10px rgba(249, 199, 79, 0.3);
    }
    .structure-category h2::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(to right, var(--accent-2), transparent);
    }

    /* --- Structure Card --- */
    .structure-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transition: all 0.2s ease-out;
        position: relative;
        transform: translateZ(0); /* Force GPU acceleration */
    }
    .structure-card:hover {
        transform: translateY(-5px);
        border-color: var(--accent);
        box-shadow: var(--shadow), 0 0 25px var(--accent-soft);
    }

    /* Max Level State */
    .structure-card.max-level {
        border-color: var(--accent-2);
        box-shadow: var(--shadow), var(--max-level-glow);
        transform: scale(1.01);
    }
    .structure-card.max-level:hover {
        transform: translateY(-2px) scale(1.01);
        box-shadow: var(--shadow), var(--max-level-glow);
    }


    .card-header-main {
        padding: 1rem 1.5rem;
        background: rgba(6, 7, 14, 0.4);
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .card-icon {
        font-size: 2.2rem;
        color: var(--accent);
        line-height: 1;
    }
    .card-title-group {
        flex-grow: 1;
    }
    .card-title {
        margin: 0;
        font-size: 1.3rem;
        color: var(--text);
        letter-spacing: -0.02em;
    }
    .card-level {
        font-size: 0.9rem;
        color: var(--muted);
        font-weight: 500;
    }

    .card-body-main {
        padding: 1.25rem 1.5rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .card-description {
        font-size: 0.95rem;
        color: var(--muted);
        line-height: 1.5;
    }

    .card-benefit {
        background: rgba(45, 209, 209, 0.08);
        border: 1px solid var(--accent-soft);
        border-radius: 8px;
        padding: 0.75rem;
        font-size: 0.9rem;
        color: var(--accent);
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .card-benefit .icon {
        font-size: 1.2rem;
    }

    .card-costs-next {
        border-top: 1px dashed rgba(255, 255, 255, 0.05);
        padding-top: 1rem;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        justify-content: space-between;
    }
    .cost-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        color: var(--muted);
    }
    .cost-item.insufficient .value {
        color: var(--accent-red);
    }
    .cost-item .icon {
        font-size: 1.1rem;
        color: var(--accent-2);
    }
    .cost-item .value {
        font-weight: 600;
        color: var(--text);
    }

    .card-footer-actions {
        padding: 1.25rem 1.5rem;
        border-top: 1px solid var(--border);
        background: rgba(6, 7, 14, 0.4);
    }

    .btn-upgrade {
        width: 100%;
        padding: 0.8rem 1.25rem;
        border: none;
        border-radius: 10px;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        background: linear-gradient(135deg, var(--accent) 0%, #1a9c9c 100%);
        color: #02030a;
        box-shadow: 0 5px 20px rgba(45, 209, 209, 0.3);
    }
    .btn-upgrade:not([disabled]):hover {
        filter: brightness(1.1);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(45, 209, 209, 0.4);
    }
    .btn-upgrade[disabled] {
        background: rgba(187, 76, 76, 0.2);
        color: rgba(255, 255, 255, 0.4);
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
        border: 1px solid rgba(187, 76, 76, 0.3);
    }
    
    .max-level-badge {
        background: linear-gradient(135deg, var(--accent-2) 0%, #d4a940 100%);
        color: #02030a;
        padding: 0.8rem 1.25rem;
        border-radius: 10px;
        font-size: 1.1rem;
        font-weight: 700;
        text-align: center;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        box-shadow: 0 5px 20px rgba(249, 199, 79, 0.3);
    }


    /* Responsive adjustments */
    @media (max-width: 768px) {
        .structures-dashboard {
            padding: 1rem;
        }
        .user-resources-card {
            flex-direction: column;
            gap: 1.5rem;
        }
        .resource-item {
            flex-basis: auto;
        }
        .structures-grid {
            grid-template-columns: 1fr;
        }
        .structure-category h2 {
            font-size: 1.4rem;
        }
        .card-title {
            font-size: 1.2rem;
        }
        .btn-upgrade {
            font-size: 1rem;
            padding: 0.7rem 1rem;
        }
    }
</style>

<div class="structures-dashboard">
    <h1>Strategic Structures</h1>
    <p class="subtitle">Build and upgrade your infrastructure to expand your influence and power.</p>

    <div class="user-resources-card">
        <div class="resource-item">
            <span class="icon icon-credits"></span>
            <div class="value"><?= number_format($resources->credits) ?></div>
            <div class="label">Credits</div>
        </div>
        <!-- Add other resources relevant to structures here if needed -->

        <div class="resource-item">
            <span class="icon icon-soldiers"></span>
            <div class="value"><?= number_format($resources->soldiers) ?></div>
            <div class="label">Soldiers</div>
        </div>
    </div>

    <div class="structures-grid">
        <?php foreach ($categoryOrder as $categoryName):
            if (!isset($groupedStructures[$categoryName])) continue; ?>

            <div class="structure-category">
                <h2><?= htmlspecialchars($categoryName) ?></h2>
            </div>

            <?php foreach ($groupedStructures[$categoryName] as $details):
                $key = $details['key'];
                $columnName = $key . '_level';
                $currentLevel = $structures->{$columnName} ?? 0;
                $upgradeCost = $costs[$key] ?? 0;
                $isMaxLevel = ($upgradeCost === 0 && isset($costs[$key])); // True if cost is explicitly 0
                $nextLevel = $currentLevel + 1;
                $displayName = $details['name'] ?? 'Unknown';
                $description = $details['description'] ?? 'No description available.';
                $canAfford = $resources->credits >= $upgradeCost;
                
                // --- Benefit Text Logic (from original code) ---
                $benefitText = '';
                switch ($key) {
                    case 'economy_upgrade':
                        $val = $turnConfig['credit_income_per_econ_level'] ?? 0;
                        $benefitText = "+ " . number_format($val) . " Credits / Turn";
                        break;
                    case 'population':
                        $val = $turnConfig['citizen_growth_per_pop_level'] ?? 0;
                        $benefitText = "+ " . number_format($val) . " Citizens / Turn";
                        break;
                    case 'offense_upgrade':
                        $val = ($attackConfig['power_per_offense_level'] ?? 0) * 100;
                        $benefitText = "+ " . $val . "% Soldier Power";
                        break;
                    case 'fortification':
                        $val = ($attackConfig['power_per_fortification_level'] ?? 0) * 100;
                        $benefitText = "+ " . $val . "% Guard Power";
                        break;
                    case 'defense_upgrade':
                        $val = ($attackConfig['power_per_defense_level'] ?? 0) * 100;
                        $benefitText = "+ " . $val . "% Defense Power";
                        break;
                    case 'spy_upgrade':
                        $val = ($spyConfig['offense_power_per_level'] ?? 0) * 100;
                        $benefitText = "+ " . $val . "% Spy/Sentry Power";
                        break;
                    case 'armory':
                        $benefitText = "Unlocks & Upgrades Item Tiers";
                        break;
                }
                
                // Determine icon based on category for visual distinction
                $categoryIcon = '';
                switch ($categoryName) {
                    case 'Economy': $categoryIcon = '💰'; break;
                    case 'Defense': $categoryIcon = '🛡️'; break;
                    case 'Offense': $categoryIcon = '⚔️'; break;
                    case 'Intel': $categoryIcon = '📡'; break;
                    default: $categoryIcon = '⚙️'; break;
                }
            ?>
                <div class="structure-card <?= $isMaxLevel ? 'max-level' : '' ?>">
                    <div class="card-header-main">
                        <span class="card-icon"><?= $categoryIcon ?></span>
                        <div class="card-title-group">
                            <h3 class="card-title"><?= htmlspecialchars($displayName) ?></h3>
                            <p class="card-level">Level: <?= $currentLevel ?></p>
                        </div>
                    </div>
                    <div class="card-body-main">
                        <p class="card-description"><?= htmlspecialchars($description) ?></p>
                        
                        <?php if ($benefitText): ?>
                            <div class="card-benefit">
                                <span class="icon">✨</span>
                                <?= htmlspecialchars($benefitText) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!$isMaxLevel): ?>
                            <div class="card-costs-next">
                                <div class="cost-item <?= !$canAfford ? 'insufficient' : '' ?>">
                                    <span class="icon icon-credits"></span>
                                    <span class="value"><?= number_format($upgradeCost) ?></span>
                                    <span class="label">Credits</span>
                                </div>
                                <!-- Add other resource costs here if structures cost more than just credits -->
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer-actions">
                        <?php if ($isMaxLevel): ?>
                            <div class="max-level-badge">Max Level Achieved!</div>
                        <?php else: ?>
                            <form action="/structures/upgrade" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                <input type="hidden" name="structure_key" value="<?= htmlspecialchars($key) ?>">

                                <button type="submit" class="btn-upgrade" <?= !$canAfford ? 'disabled' : '' ?>>
                                    <?= !$canAfford ? 'Insufficient Credits' : 'Upgrade to Level ' . $nextLevel ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
</div>
