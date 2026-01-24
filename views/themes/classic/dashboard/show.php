<?php
// --- Helper variables from the controller ---
// /* @var \App\Models\Entities\User $user */
// /* @var \App\Models\Entities\UserResource $resources */
// /* @var \App\Models\Entities\UserStats $stats */
// /* @var \App\Models\Entities\UserStructure $structures */
// /* @var array $incomeBreakdown */
// /* @var array $offenseBreakdown */
// /* @var array $defenseBreakdown */
// /* @var array $spyBreakdown */
// /* @var array $sentryBreakdown */

if (!function_exists('sd_format_compact')) {
    function sd_format_compact($value, int $precision = 1): string {
        if (!is_numeric($value)) return '0';
        $n = (float)$value;
        $abs = abs($n);
        $suffix = '';
        $div = 1.0;
        if ($abs >= 1e12) { $suffix = 'T'; $div = 1e12; }
        elseif ($abs >= 1e9) { $suffix = 'B'; $div = 1e9; }
        elseif ($abs >= 1e6) { $suffix = 'M'; $div = 1e6; }
        elseif ($abs >= 1e3) { $suffix = 'K'; $div = 1e3; }
        $out = $div === 1.0 ? number_format((int)$n) : number_format($n / $div, $precision);
        $out = preg_replace('/\.0$/', '', $out);
        return $out . $suffix;
    }
}

// --- Pre-calculation for view logic ---
$incomePerTurn = (int)($incomeBreakdown['total_credit_income'] ?? 0);
$creditsOnHand = (int)($resources->credits ?? 0);
$bankedCredits  = (int)($resources->banked_credits ?? 0);

$levelUpPoints = (int)($stats->level_up_points ?? 0);
$attackTurns   = (int)($stats->attack_turns ?? 0);

$milOffense = (int)($offenseBreakdown['total'] ?? 0);
$milDefense = (int)($defenseBreakdown['total'] ?? 0);
$milSpy     = (int)($spyBreakdown['total'] ?? 0);
$milSentry  = (int)($sentryBreakdown['total'] ?? 0);

$soldiers = (int)($resources->soldiers ?? 0);
$guards = (int)($resources->guards ?? 0);
$spies = (int)($resources->spies ?? 0);
$sentries = (int)($resources->sentries ?? 0);
$maxMilitaryUnit = max(1, $soldiers, $guards, $spies, $sentries);
$unitPct = static function(int $val) use ($maxMilitaryUnit): int {
    return (int)round(($val / $maxMilitaryUnit) * 100);
};

$isSecurityHeavy = $guards > $soldiers;
$isIntelLeaning = $spies > $sentries;
?>
<link rel="stylesheet" href="/css/classic_dashboard.css?v=<?= time() ?>">

<div class="command-bridge-container" id="command-bridge">

    <!-- ===================== 1. TOP COMMAND BAR (STATUS STRIP) ===================== -->
    <section class="status-strip">
        <div class="player-id">
             <?php if (!empty($user->profile_picture_url)): ?>
                <img src="/serve/avatar/<?= htmlspecialchars($user->profile_picture_url) ?>" alt="Avatar" class="player-avatar">
            <?php else: ?>
                <svg class="player-avatar player-avatar-svg" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
            <?php endif; ?>
            <div>
                <h2 class="player-name"><?= htmlspecialchars($user->characterName) ?></h2>
                <span class="sub-text">
                    <?php if (!empty($user->alliance_id)): ?>
                        <a href="/alliance/profile/<?= (int)$user->alliance_id ?>">View Alliance</a>
                    <?php else: ?>
                        <a href="/alliance/list">Find an Alliance</a>
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <div class="status-metrics">
            <div class="metric">
                <span class="metric-label">Level</span>
                <span class="metric-value highlight"><?= (int)$stats->level ?></span>
            </div>
            <div class="metric">
                <span class="metric-label">Net Worth</span>
                <span class="metric-value"><?= htmlspecialchars($formatted_net_worth ?? '0') ?></span>
            </div>
            <div class="metric">
                <span class="metric-label">Credits</span>
                <span class="metric-value"><?= sd_format_compact($creditsOnHand, 2) ?></span>
            </div>
             <div class="metric">
                <span class="metric-label">Population</span>
                <span class="metric-value"><?= sd_format_compact((int)($resources->untrained_citizens ?? 0), 1) ?></span>
            </div>
            <div class="metric">
                <span class="metric-label">Workers</span>
                <span class="metric-value secondary"><?= number_format((int)($resources->workers ?? 0)) ?></span>
            </div>
        </div>
    </section>

    <!-- ===================== 9. MAIN GRID (ROWS OF INTENT) ===================== -->
    <section class="dashboard-grid-classic">

        <!-- Row 2: Economy + Stats -->
        <div class="data-card grid-col-span-2 economy-panel">
            <div class="card-header">
                <h3>Economic Overview</h3>
                <a class="card-toggle" data-target="breakdown-income">Show Breakdown</a>
            </div>
            <div class="hero-metric">
                +<?= sd_format_compact($incomePerTurn, 1) ?> / TURN
                <span class="micro-trend text-success">▲</span>
            </div>
            <div class="secondary-metric"><span class="label">Credits:</span> <?= sd_format_compact($creditsOnHand, 2) ?></div>
            <div class="tertiary-metric"><span class="label">Banked:</span> <?= sd_format_compact($bankedCredits, 2) ?></div>
        </div>

        <div class="data-card grid-col-span-1 stats-panel">
            <div class="card-header">
                <h3>Stats</h3>
                <a href="/level-up" class="card-toggle">Spend Points</a>
            </div>
            <div class="stat-group-title">Action Economy</div>
            <ul class="card-stats-list">
                <?php if ($levelUpPoints > 0): ?>
                <li class="action-item">
                    <span>Points to Spend</span>
                    <span class="value-green"><?= number_format($levelUpPoints) ?></span>
                </li>
                <?php else: ?>
                <li><span>Points to Spend</span> <span><?= number_format($levelUpPoints) ?></span></li>
                <?php endif; ?>
                <li><span>Attack Turns</span> <span><?= number_format($attackTurns) ?></span></li>
            </ul>

            <div class="stat-group-title">Empire Attributes</div>
             <ul class="card-stats-list">
                <li><span>Strength</span> <span><?= number_format($stats->strength_points) ?></span></li>
                <li><span>Constitution</span> <span><?= number_format($stats->constitution_points) ?></span></li>
                <li><span>Wealth</span> <span><?= number_format($stats->wealth_points) ?></span></li>
            </ul>
        </div>

        <!-- Row 3: Military + Structures -->
        <div class="data-card grid-col-span-1 military-command-panel">
             <div class="card-header">
                <h3>Military Command</h3>
                <a class="card-toggle" data-target="breakdown-military">Show Breakdown</a>
            </div>
            <div class="power-comparison">
                <span class="power-label">OFFENSE</span>
                <span class="power-value offense"><?= sd_format_compact($milOffense, 2) ?> <span class="power-trend text-success">▲</span></span>
            </div>
             <div class="power-comparison">
                <span class="power-label">DEFENSE</span>
                <span class="power-value defense"><?= sd_format_compact($milDefense, 2) ?> <span class="power-trend text-danger">▼</span></span>
            </div>
            <hr class="intel-divider">
            <div class="power-comparison">
                <span class="power-label">Spy Power</span>
                <span class="power-value"><?= sd_format_compact($milSpy, 2) ?></span>
            </div>
            <div class="power-comparison">
                <span class="power-label">Sentry Power</span>
                <span class="power-value"><?= sd_format_compact($milSentry, 2) ?></span>
            </div>
            <div class="readiness-label">Military Readiness: High</div>
        </div>

        <div class="data-card grid-col-span-1">
            <div class="card-header">
                <h3>Military Units</h3>
                <a href="/training" class="card-toggle">Train</a>
            </div>
            <ul class="card-stats-list">
                <li class="unit-list-item">
                    <div class="unit-bar" style="width: <?= $unitPct($soldiers) ?>%;"></div>
                    <span>Soldiers</span> <span><?= number_format($soldiers) ?></span>
                </li>
                <li class="unit-list-item">
                    <div class="unit-bar" style="width: <?= $unitPct($guards) ?>%;"></div>
                    <span>Guards</span> <span><?= number_format($guards) ?></span>
                </li>
                <li class="unit-list-item">
                    <div class="unit-bar" style="width: <?= $unitPct($spies) ?>%;"></div>
                    <span>Spies</span> <span><?= number_format($spies) ?></span>
                </li>
                <li class="unit-list-item">
                    <div class="unit-bar" style="width: <?= $unitPct($sentries) ?>%;"></div>
                    <span>Sentries</span> <span><?= number_format($sentries) ?></span>
                </li>
            </ul>
            <?php if ($isSecurityHeavy): ?><div class="unit-imbalance-flag">Composition: Security-heavy</div><?php endif; ?>
            <?php if ($isIntelLeaning): ?><div class="unit-imbalance-flag">Composition: Intel-leaning</div><?php endif; ?>
        </div>

        <div class="data-card grid-col-span-1 structures-panel">
            <div class="card-header">
                <h3>Structures</h3>
                <a href="/structures" class="card-toggle">Upgrade</a>
            </div>
            <ul class="card-stats-list">
                <li>
                    <div>
                        <span>Economy</span> <span class="structure-desc">→ +Income</span>
                    </div>
                    <span>Lvl <?= $structures->economy_upgrade_level ?></span>
                    <div class="structure-next-impact">Next: +1.5% income</div>
                </li>
                <li>
                    <div>
                        <span>Population</span> <span class="structure-desc">→ +Citizens</span>
                    </div>
                    <span>Lvl <?= $structures->population_level ?></span>
                    <div class="structure-next-impact">Next: +100k citizens</div>
                </li>
                <li>
                    <div>
                        <span>Armory</span> <span class="structure-desc">→ +Military Scaling</span>
                    </div>
                    <span>Lvl <?= $structures->armory_level ?></span>
                     <div class="structure-next-impact">Next: +0.5% power</div>
                </li>
                <li>
                    <div>
                        <span>Planetary Shield</span> <span class="structure-desc">→ +Defense Cap</span>
                    </div>
                    <span>Lvl <?= $structures->planetary_shield_level ?></span>
                    <div class="structure-next-impact">Next: +2.0% shield</div>
                </li>
            </ul>
        </div>
    </section>
</div>

<script src="/js/dashboard.js"></script>

