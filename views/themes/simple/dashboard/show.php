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
    /**
     * Compact number formatting: 12500 -> 12.5K, 5_300_000 -> 5.3M, 12_100_000_000_000 -> 12.1T
     */
    function sd_format_compact($value, int $precision = 1): string
    {
        if (!is_numeric($value)) return '0';
        $n = (float)$value;
        $abs = abs($n);

        $suffix = '';
        $div = 1.0;

        if ($abs >= 1e15) { $suffix = 'Q'; $div = 1e15; }
        elseif ($abs >= 1e12) { $suffix = 'T'; $div = 1e12; }
        elseif ($abs >= 1e9) { $suffix = 'B'; $div = 1e9; }
        elseif ($abs >= 1e6) { $suffix = 'M'; $div = 1e6; }
        elseif ($abs >= 1e3) { $suffix = 'K'; $div = 1e3; }

        $out = $div === 1.0 ? number_format((int)$n) : number_format($n / $div, $precision);
        // Trim trailing .0
        $out = preg_replace('/(\.\d*?[1-9])0+$/', '$1', $out);
        $out = preg_replace('/\.0$/', '', $out);

        return $out . $suffix;
    }
}

$incomePerTurn = (int)($incomeBreakdown['total_credit_income'] ?? 0);
$creditsOnHand = (int)($resources->credits ?? 0);
$bankedCredits  = (int)($resources->banked_credits ?? 0);

$levelUpPoints = (int)($stats->level_up_points ?? 0);
$attackTurns   = (int)($stats->attack_turns ?? 0);

$attrMax = 30; // UI scaling constant (tune later)
$strength = (int)($stats->strength_points ?? 0);
$constitution = (int)($stats->constitution_points ?? 0);
$wealth = (int)($stats->wealth_points ?? 0);

$attrPct = static function (int $val) use ($attrMax): int {
    if ($attrMax <= 0) return 0;
    $pct = (int)round(($val / $attrMax) * 100);
    return max(0, min(100, $pct));
};

$milOffense = (int)($offenseBreakdown['total'] ?? 0);
$milDefense = (int)($defenseBreakdown['total'] ?? 0);
$milSpy     = (int)($spyBreakdown['total'] ?? 0);
$milSentry  = (int)($sentryBreakdown['total'] ?? 0);

// Optional: provide battles to dashboard_v3.js if available.
$battlesForJs = [];
if (isset($latestBattles) && is_array($latestBattles)) {
    foreach ($latestBattles as $b) {
        // Support either entity objects or arrays.
        $battlesForJs[] = [
            'created_at'     => is_object($b) ? ($b->created_at ?? null) : ($b['created_at'] ?? null),
            'attack_result'  => is_object($b) ? ($b->attack_result ?? null) : ($b['attack_result'] ?? null),
            'attack_type'    => is_object($b) ? ($b->attack_type ?? null) : ($b['attack_type'] ?? null),
            'attacker_name'  => is_object($b) ? ($b->attacker_name ?? null) : ($b['attacker_name'] ?? null),
            'defender_name'  => is_object($b) ? ($b->defender_name ?? null) : ($b['defender_name'] ?? null),
        ];
    }
}
?>
<div class="command-bridge-container" id="command-bridge">

    <!-- ===================== TOP COMMAND BAR ===================== -->
    <section class="bridge-header">
        <div class="bridge-identity">
            <div class="bridge-avatar-wrap">
                <?php if (!empty($user->profile_picture_url)): ?>
                    <img src="/serve/avatar/<?= htmlspecialchars($user->profile_picture_url) ?>" alt="Avatar" class="bridge-avatar">
                <?php else: ?>
                    <svg class="bridge-avatar bridge-avatar-svg" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                    </svg>
                <?php endif; ?>
            </div>

            <div class="bridge-identity-text">
                <div class="bridge-name-row">
                    <h2 class="bridge-name"><?= htmlspecialchars($user->characterName) ?></h2>
                    <?php if (!empty($user->alliance_id)): ?>
                        <a class="bridge-link" href="/alliance/profile/<?= (int)$user->alliance_id ?>">View Alliance</a>
                    <?php else: ?>
                        <a class="bridge-link" href="/alliance/list">Find an Alliance</a>
                    <?php endif; ?>
                </div>
                <div class="bridge-subtitle">Command Bridge • Live Operational Snapshot</div>
            </div>
        </div>

        <div class="bridge-kpis">
            <div class="kpi">
                <span class="kpi-label">LEVEL</span>
                <span class="kpi-value"><?= (int)$stats->level ?></span>
            </div>
            <div class="kpi">
                <span class="kpi-label">NET WORTH</span>
                <span class="kpi-value"><?= htmlspecialchars($formatted_net_worth ?? sd_format_compact(($resources->banked_credits ?? 0) + ($resources->credits ?? 0))) ?></span>
            </div>
            <div class="kpi">
                <span class="kpi-label">CREDITS</span>
                <span class="kpi-value"><?= sd_format_compact($creditsOnHand, 2) ?></span>
            </div>
            <div class="kpi">
                <span class="kpi-label">POP</span>
                <span class="kpi-value"><?= sd_format_compact((int)($resources->untrained_citizens ?? 0), 1) ?></span>
            </div>
            <div class="kpi">
                <span class="kpi-label">WORKERS</span>
                <span class="kpi-value"><?= number_format((int)($resources->workers ?? 0)) ?></span>
            </div>

            <a class="bridge-icon-btn" href="/settings" title="Settings" aria-label="Settings">
                <i class="fas fa-cog"></i>
            </a>
        </div>
    </section>

    <!-- ===================== MAIN GRID ===================== -->
    <section class="bridge-grid">

        <!-- ECONOMIC OVERVIEW (WIDE) -->
        <article class="bridge-card bridge-card-wide" aria-label="Economic Overview">
            <header class="bridge-card-header">
                <h3>Economic Overview</h3>
                <a class="bridge-action card-toggle" data-target="breakdown-income">Show Breakdown</a>
            </header>

            <div class="eco-row">
                <div class="eco-primary">
                    <div class="eco-income">
                        <span class="eco-income-val">+<?= sd_format_compact($incomePerTurn, 1) ?></span>
                        <span class="eco-income-label">/ TURN</span>
                    </div>
                    <div class="eco-sub">
                        <span class="eco-sub-label">Credits on Hand</span>
                        <span class="eco-sub-val"><?= sd_format_compact($creditsOnHand, 2) ?></span>
                    </div>
                    <div class="eco-sub">
                        <span class="eco-sub-label">Banked Credits</span>
                        <span class="eco-sub-val"><?= sd_format_compact($bankedCredits, 2) ?></span>
                    </div>
                </div>

                <div class="eco-mini-graph">
                    <div class="mini-graph-label">24H SIGNAL</div>
                    <canvas id="oscilloscope-canvas" class="mini-graph-canvas" aria-label="Recent battle signal"></canvas>
                </div>
            </div>

            <div class="bridge-breakdown card-breakdown" id="breakdown-income">
                <div class="breakdown-block">
                    <strong>Total Credit Income: + <?= number_format($incomeBreakdown['total_credit_income'] ?? 0) ?></strong>
                    <ul>
                        <?php foreach (($incomeBreakdown['detailed_breakdown'] ?? []) as $item): ?>
                            <li>
                                <span><?= htmlspecialchars($item['label'] ?? '—') ?></span>
                                <?php if (isset($item['value']) && is_numeric($item['value'])): ?>
                                    <span>+ <?= number_format((float)$item['value']) ?></span>
                                <?php else: ?>
                                    <span><?= htmlspecialchars((string)($item['value'] ?? '—')) ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="breakdown-block">
                    <strong>Citizen Growth: + <?= number_format($incomeBreakdown['total_citizens'] ?? 0) ?></strong>
                    <ul>
                        <li>
                            <span>Citizen Growth (Lvl <?= (int)($incomeBreakdown['pop_level'] ?? 0) ?>)</span>
                            <span>+ <?= number_format($incomeBreakdown['base_citizen_income'] ?? 0) ?></span>
                        </li>

                        <?php if (!empty($incomeBreakdown['alliance_citizen_bonus'])): ?>
                            <li class="breakdown-accent">
                                <span>Alliance Structures</span>
                                <span>+ <?= number_format((float)$incomeBreakdown['alliance_citizen_bonus']) ?></span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </article>

        <!-- EMPIRE STATS (TALL RIGHT) -->
        <article class="bridge-card bridge-card-tall" aria-label="Empire Stats">
            <header class="bridge-card-header bridge-card-header-split">
                <h3>Empire Stats</h3>
                <a class="bridge-action" href="/level-up">Spend Points</a>
            </header>

            <div class="empire-statline empire-statline-top">
                <span class="empire-label">Attributes</span>
                <span class="empire-pill"><?= number_format($levelUpPoints) ?></span>
            </div>

            <div class="empire-attr">
                <div class="empire-attr-row">
                    <span class="empire-label">Strength</span>
                    <span class="empire-val"><?= number_format($strength) ?></span>
                </div>
                <div class="empire-bar">
                    <div class="empire-bar-fill" data-pct="<?= $attrPct($strength) ?>"></div>
                </div>

                <div class="empire-attr-row">
                    <span class="empire-label">Constitution</span>
                    <span class="empire-val"><?= number_format($constitution) ?></span>
                </div>
                <div class="empire-bar">
                    <div class="empire-bar-fill" data-pct="<?= $attrPct($constitution) ?>"></div>
                </div>

                <div class="empire-attr-row">
                    <span class="empire-label">Wealth</span>
                    <span class="empire-val"><?= number_format($wealth) ?></span>
                </div>
                <div class="empire-bar">
                    <div class="empire-bar-fill" data-pct="<?= $attrPct($wealth) ?>"></div>
                </div>
            </div>

            <div class="empire-divider"></div>

            <div class="empire-statline">
                <span class="empire-label">ATTACK TURNS</span>
                <span class="empire-val"><?= number_format($attackTurns) ?></span>
            </div>

            <div class="empire-statline">
                <span class="empire-label">SPY TURNS</span>
                <span class="empire-val"><?= number_format((int)($stats->spy_turns ?? 0)) ?></span>
            </div>

            <div class="empire-alerts">
                <div class="empire-alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Balanced: <strong>HIGH</strong></span>
                </div>
                <div class="empire-alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Intel Coverage: <strong>MODERATE</strong></span>
                </div>
            </div>
        </article>

        <!-- MILITARY COMMAND (LEFT MID) -->
        <article class="bridge-card" aria-label="Military Command">
            <header class="bridge-card-header">
                <h3>Military Command</h3>
                <a class="bridge-action card-toggle" data-target="breakdown-military">Show Breakdown</a>
            </header>

            <div class="mil-grid">
                <div class="mil-row">
                    <span class="mil-label"><i class="fas fa-bullseye"></i> OFFENSE</span>
                    <span class="mil-val mil-val-danger"><?= sd_format_compact($milOffense, 2) ?></span>
                </div>
                <div class="mil-row">
                    <span class="mil-label"><i class="fas fa-shield-alt"></i> DEFENSE</span>
                    <span class="mil-val mil-val-info"><?= sd_format_compact($milDefense, 2) ?></span>
                </div>
                <div class="mil-row">
                    <span class="mil-label">Spy Power</span>
                    <span class="mil-val"><?= sd_format_compact($milSpy, 2) ?></span>
                </div>
                <div class="mil-row">
                    <span class="mil-label">Sentry Power</span>
                    <span class="mil-val"><?= sd_format_compact($milSentry, 2) ?></span>
                </div>
            </div>

            <div class="mil-footer">
                <div class="mil-flag">Readiness: <strong>HIGH</strong></div>
                <div class="mil-flag">Intel Coverage: <strong>MODERATE</strong></div>
            </div>

            <div class="bridge-breakdown card-breakdown" id="breakdown-military">
                <div class="breakdown-block">
                    <strong>Offense Power (<?= number_format((int)($offenseBreakdown['unit_count'] ?? 0)) ?> Soldiers)</strong>
                    <ul>
                        <li><span>Base Soldier Power</span> <span><?= number_format((float)($offenseBreakdown['base_unit_power'] ?? 0)) ?></span></li>
                        <li><span>Armory Bonus (from Loadout)</span> <span>+ <?= number_format((float)($offenseBreakdown['armory_bonus'] ?? 0)) ?></span></li>
                        <li><span>Strength (<?= (int)($offenseBreakdown['stat_points'] ?? 0) ?> pts) Bonus</span> <span>+ <?= (float)($offenseBreakdown['stat_bonus_pct'] ?? 0) * 100 ?>%</span></li>

                        <?php if (!empty($offenseBreakdown['alliance_bonus_pct'])): ?>
                            <li class="breakdown-accent">
                                <span>Alliance Structures</span>
                                <span>+ <?= (float)$offenseBreakdown['alliance_bonus_pct'] * 100 ?>%</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="breakdown-block">
                    <strong>Defense Rating (<?= number_format((int)($defenseBreakdown['unit_count'] ?? 0)) ?> Guards)</strong>
                    <ul>
                        <li><span>Base Guard Power</span> <span><?= number_format((float)($defenseBreakdown['base_unit_power'] ?? 0)) ?></span></li>
                        <li><span>Armory Bonus (from Loadout)</span> <span>+ <?= number_format((float)($defenseBreakdown['armory_bonus'] ?? 0)) ?></span></li>
                        <li><span>Constitution (<?= (int)($defenseBreakdown['stat_points'] ?? 0) ?> pts) Bonus</span> <span>+ <?= (float)($defenseBreakdown['stat_bonus_pct'] ?? 0) * 100 ?>%</span></li>

                        <?php if (!empty($defenseBreakdown['alliance_bonus_pct'])): ?>
                            <li class="breakdown-accent">
                                <span>Alliance Structures</span>
                                <span>+ <?= (float)$defenseBreakdown['alliance_bonus_pct'] * 100 ?>%</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </article>

        <!-- MILITARY UNITS (CENTER MID) -->
        <article class="bridge-card" aria-label="Military Units">
            <header class="bridge-card-header bridge-card-header-split">
                <h3>Military Units</h3>
                <a class="bridge-action" href="/training">Train</a>
            </header>

            <div class="unit-row">
                <span class="unit-label">Soldiers</span>
                <span class="unit-val"><?= number_format((int)($resources->soldiers ?? 0)) ?></span>
            </div>
            <div class="unit-bar">
                <div class="unit-bar-fill" data-pct="70"></div>
            </div>

            <div class="unit-row">
                <span class="unit-label">Guards</span>
                <span class="unit-val"><?= number_format((int)($resources->guards ?? 0)) ?></span>
            </div>
            <div class="unit-bar">
                <div class="unit-bar-fill" data-pct="82"></div>
            </div>

            <div class="unit-row">
                <span class="unit-label">Spies</span>
                <span class="unit-val"><?= number_format((int)($resources->spies ?? 0)) ?></span>
            </div>
            <div class="unit-bar">
                <div class="unit-bar-fill" data-pct="60"></div>
            </div>

            <div class="unit-row">
                <span class="unit-label">Sentries</span>
                <span class="unit-val"><?= number_format((int)($resources->sentries ?? 0)) ?></span>
            </div>
            <div class="unit-bar">
                <div class="unit-bar-fill" data-pct="35"></div>
            </div>

            <div class="unit-notes">
                <div class="unit-note"><i class="fas fa-balance-scale"></i> Balanced Army</div>
                <div class="unit-note"><i class="fas fa-user-plus"></i> Next: +<?= number_format((int)($incomeBreakdown['total_citizens'] ?? 0)) ?> Citizens</div>
            </div>
        </article>

        <!-- STRUCTURES (BOTTOM RIGHT) -->
        <article class="bridge-card" aria-label="Structures">
            <header class="bridge-card-header bridge-card-header-split">
                <h3>Structures</h3>
                <a class="bridge-action" href="/structures">Upgrade</a>
            </header>

            <div class="structure-row">
                <span class="structure-label">Economy</span>
                <span class="structure-val">Lvl <?= (int)($structures->economy_upgrade_level ?? 0) ?></span>
            </div>
            <div class="structure-row">
                <span class="structure-label">Population</span>
                <span class="structure-val">Lvl <?= (int)($structures->population_level ?? 0) ?></span>
            </div>
            <div class="structure-row">
                <span class="structure-label">Armory</span>
                <span class="structure-val">Lvl <?= (int)($structures->armory_level ?? 0) ?></span>
            </div>
            <div class="structure-row">
                <span class="structure-label">Planetary Shield</span>
                <span class="structure-val">Lvl <?= (int)($structures->planetary_shield_level ?? 0) ?></span>
            </div>
        </article>

        <!-- ACTIVE EFFECTS (OPTIONAL, WIDE) -->
        <?php if (!empty($activeEffects)): ?>
            <article class="bridge-card bridge-card-wide" aria-label="Active Effects">
                <header class="bridge-card-header">
                    <h3>Active Effects & Assets</h3>
                </header>

                <div class="effects-grid">
                    <?php foreach ($activeEffects as $effect): ?>
                        <div class="effect-chip">
                            <div class="effect-left">
                                <i class="fas <?= htmlspecialchars($effect['ui_icon'] ?? 'fa-bolt') ?> <?= htmlspecialchars($effect['ui_color'] ?? '') ?>"></i>
                                <span class="effect-label"><?= htmlspecialchars($effect['ui_label'] ?? 'Effect') ?></span>
                            </div>
                            <div class="effect-right">
                                <i class="far fa-clock"></i>
                                <span class="effect-time"><?= htmlspecialchars($effect['formatted_time_left'] ?? '') ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endif; ?>

    </section>
</div>

<script>
    // Provide dashboard data for visual widgets (oscilloscope, etc.)
    window.dashboardData = window.dashboardData || {};
    window.dashboardData.battles = <?= json_encode($battlesForJs, JSON_UNESCAPED_SLASHES) ?>;
</script>

<script src="/js/dashboard.js?v=<?= time() ?>"></script>
<script src="/js/dashboard_v3.js?v=<?= time() ?>"></script>

<script>
    // Initialize simple bar fills without inline styles in markup.
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-pct]').forEach(el => {
            const pct = Math.max(0, Math.min(100, parseInt(el.getAttribute('data-pct') || '0', 10)));
            el.style.width = pct + '%';
        });
    });
</script>
