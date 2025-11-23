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

// --- Grouping Logic ---
$groupedStructures = [];
foreach ($structureFormulas as $key => $details) {
    $category = $details['category'] ?? 'Uncategorized';
    $details['key'] = $key;
    $groupedStructures[$category][] = $details;
}
$categoryOrder = ['Economy', 'Defense', 'Offense', 'Intel'];
?>

<div class="container-full">
    <h1>Strategic Structures</h1>
    <p class="subtitle" style="text-align: center; color: var(--muted); margin-top: -1rem; margin-bottom: 2rem;">
        Build and upgrade your infrastructure to expand your influence and power.
    </p>

    <div class="resource-header-card">
        <div class="header-stat">
            <span>Credits</span>
            <strong class="accent-gold"><?= number_format($resources->credits) ?></strong>
        </div>
        <div class="header-stat">
            <span>Soldiers</span>
            <strong class="accent-red"><?= number_format($resources->soldiers) ?></strong>
        </div>
        <div class="header-stat">
            <span>Guards</span>
            <strong class="accent-blue"><?= number_format($resources->guards) ?></strong>
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
                $isMaxLevel = ($upgradeCost === 0 && isset($costs[$key])); 
                $nextLevel = $currentLevel + 1;
                $displayName = $details['name'] ?? 'Unknown';
                $description = $details['description'] ?? 'No description available.';
                $canAfford = $resources->credits >= $upgradeCost;
                
                // --- Benefit Text Logic ---
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
                
                // Determine icon
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
                                    <span class="icon">◎</span>
                                    <span class="value"><?= number_format($upgradeCost) ?></span>
                                    <span>Credits</span>
                                </div>
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