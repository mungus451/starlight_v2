<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\User $user */
/* @var \App\Models\Entities\UserResource $resources */
/* @var \App\Models\Entities\UserStats $stats */
/* @var \App\Models\Entities\UserStructure $structures */
/* @var array $incomeBreakdown */
/* @var array $offenseBreakdown */
/* @var array $defenseBreakdown */
/* @var array $spyBreakdown */
/* @var array $sentryBreakdown */
?>
<div class="dashboard-grid">
<div class="player-header">
    <div class="player-info">
        <?php if ($user->profile_picture_url): ?>
            <img src="/serve/avatar/<?= htmlspecialchars($user->profile_picture_url) ?>" alt="Avatar" class="player-avatar">
        <?php else: ?>
            <svg class="player-avatar player-avatar-svg" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
            </svg>
        <?php endif; ?>
        
        <div>
            <h2><?= htmlspecialchars($user->characterName) ?></h2>
            <span class="sub-text">
                <?php if ($user->alliance_id): ?>
                    <a href="/alliance/profile/<?= $user->alliance_id ?>">View Alliance</a>
                <?php else: ?>
                    <a href="/alliance/list">Find an Alliance</a>
                <?php endif; ?>
            </span>
        </div>
    </div>
    <div class="player-stats">
        <div class="player-stat">
            <span>Level</span>
            <strong><?= $stats->level ?></strong>
        </div>
        <div class="player-stat">
            <span>Credits</span>
            <strong><?= number_format($resources->credits) ?></strong>
        </div>
        <div class="player-stat">
            <span>Citizens</span>
            <strong><?= number_format($resources->untrained_citizens) ?></strong>
        </div>
        <div class="player-stat">
            <span>Workers</span>
            <strong><?= number_format($resources->workers) ?></strong>
        </div>
    </div>
</div>

<div class="data-card grid-col-span-2">
    <div class="card-header">
        <h3>Economic Overview</h3>
        <a class="card-toggle" data-target="breakdown-income">Show Breakdown</a>
    </div>
    
    <ul class="card-stats-list">
        <li>
            <span>Credit Income / Turn</span>
            <span class="value-green value-total">+ <?= number_format($incomeBreakdown['total_credit_income']) ?></span>
        </li>
        <li>
            <span>Bank Interest / Turn</span>
            <span class="value-green value-total">+ <?= number_format($incomeBreakdown['interest']) ?></span>
        </li>
        <li>
            <span>Credits on Hand</span>
            <span><?= number_format($resources->credits) ?></span>
        </li>
        <li>
            <span>Banked Credits</span>
            <span><?= number_format($resources->banked_credits) ?></span>
        </li>
    </ul>
    
    <div class="card-breakdown" id="breakdown-income">
        <strong>Total Credit Income: + <?= number_format($incomeBreakdown['total_credit_income']) ?></strong>
        <ul>
            <li>
                <span>Base from Economy (Lvl <?= $incomeBreakdown['econ_level'] ?>)</span>
                <span>+ <?= number_format($incomeBreakdown['econ_income']) ?></span>
            </li>
            <li>
                <span>Base from Workers (<?= number_format($incomeBreakdown['worker_count']) ?>)</span>
                <span>+ <?= number_format($incomeBreakdown['worker_income']) ?></span>
            </li>
            <li>
                <span>Subtotal (Base Production)</span>
                <span>= <?= number_format($incomeBreakdown['base_production']) ?></span>
            </li>
            <li>
                <span>Wealth Bonus (<?= $incomeBreakdown['stat_bonus_pct'] * 100 ?>%)</span>
                <span>+ <?= number_format( (int)floor($incomeBreakdown['base_production'] * $incomeBreakdown['stat_bonus_pct']) ) ?></span>
            </li>
            <!-- Alliance Credit Bonus -->
            <?php if ($incomeBreakdown['alliance_credit_bonus_pct'] > 0): ?>
            <li>
                <span style="color: var(--accent-2);">Alliance Structures (<?= $incomeBreakdown['alliance_credit_bonus_pct'] * 100 ?>%)</span>
                <span style="color: var(--accent-2);">+ <?= number_format( (int)floor($incomeBreakdown['base_production'] * $incomeBreakdown['alliance_credit_bonus_pct']) ) ?></span>
            </li>
            <?php endif; ?>
            
            <li>
                <span>Armory Bonus (from Loadout)</span>
                <span>+ <?= number_format($incomeBreakdown['armory_bonus']) ?></span>
            </li>
        </ul>
        <br>
        <strong>Total Interest Income: + <?= number_format($incomeBreakdown['interest']) ?></strong>
        <ul>
            <li>
                <span>Interest (<?= $incomeBreakdown['interest_rate_pct'] * 100 ?>% of <?= number_format($incomeBreakdown['banked_credits']) ?>)</span>
                <span>+ <?= number_format($incomeBreakdown['interest']) ?></span>
            </li>
        </ul>
        <br>
        <strong>Citizen Growth: + <?= number_format($incomeBreakdown['total_citizens']) ?></strong>
        <ul>
             <li>
                <span>Citizen Growth (Lvl <?= $incomeBreakdown['pop_level'] ?>)</span>
                <span>+ <?= number_format($incomeBreakdown['base_citizen_income']) ?></span>
            </li>
            <!-- Alliance Citizen Bonus -->
            <?php if ($incomeBreakdown['alliance_citizen_bonus'] > 0): ?>
            <li>
                <span style="color: var(--accent-2);">Alliance Structures</span>
                <span style="color: var(--accent-2);">+ <?= number_format($incomeBreakdown['alliance_citizen_bonus']) ?></span>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<div class="data-card grid-col-span-1">
    <div class="card-header">
        <h3>Stats</h3>
        <a href="/level-up" class="card-toggle">Spend Points</a>
    </div>
    <ul class="card-stats-list">
        <li>
            <span>Points to Spend</span>
            <span class="value-green"><?= number_format($stats->level_up_points) ?></span>
        </li>
        <li><span>Strength</span> <span><?= number_format($stats->strength_points) ?></span></li>
        <li><span>Constitution</span> <span><?= number_format($stats->constitution_points) ?></span></li>
        <li><span>Wealth</span> <span><?= number_format($stats->wealth_points) ?></span></li>
        <li><span>Attack Turns</span> <span><?= number_format($stats->attack_turns) ?></span></li>
    </ul>
</div>

<div class="data-card grid-col-span-2 grid-row-span-2">
    <div class="card-header">
        <h3>Military Command</h3>
        <a class="card-toggle" data-target="breakdown-military">Show Breakdown</a>
    </div>
    <ul class="card-stats-list">
        <li>
            <span>Offense Power</span>
            <span class="value-red value-total"><?= number_format($offenseBreakdown['total']) ?></span>
        </li>
        <li>
            <span>Defense Rating</span>
            <span class="value-blue value-total"><?= number_format($defenseBreakdown['total']) ?></span>
        </li>
        <li>
            <span>Spy Power</span>
            <span class="value-green"><?= number_format($spyBreakdown['total']) ?></span>
        </li>
        <li>
            <span>Sentry Power</span>
            <span class="value-green"><?= number_format($sentryBreakdown['total']) ?></span>
        </li>
    </ul>
    <div class="card-breakdown" id="breakdown-military">
        <strong>Offense Power (<?= number_format($offenseBreakdown['unit_count']) ?> Soldiers)</strong>
        <ul>
            <li><span>Base Soldier Power</span> <span><?= number_format($offenseBreakdown['base_unit_power']) ?></span></li>
            <li><span>Armory Bonus (from Loadout)</span> <span>+ <?= number_format($offenseBreakdown['armory_bonus']) ?></span></li>
            <li><span>Offense Lvl <?= $offenseBreakdown['structure_level'] ?> Bonus</span> <span>+ <?= $offenseBreakdown['structure_bonus_pct'] * 100 ?>%</span></li>
            <li><span>Strength (<?= $offenseBreakdown['stat_points'] ?> pts) Bonus</span> <span>+ <?= $offenseBreakdown['stat_bonus_pct'] * 100 ?>%</span></li>
            
            <!-- Alliance Offense Bonus -->
            <?php if ($offenseBreakdown['alliance_bonus_pct'] > 0): ?>
            <li>
                <span style="color: var(--accent-2);">Alliance Structures</span> 
                <span style="color: var(--accent-2);">+ <?= $offenseBreakdown['alliance_bonus_pct'] * 100 ?>%</span>
            </li>
            <?php endif; ?>
        </ul>
        <br>
        <strong>Defense Rating (<?= number_format($defenseBreakdown['unit_count']) ?> Guards)</strong>
        <ul>
            <li><span>Base Guard Power</span> <span><?= number_format($defenseBreakdown['base_unit_power']) ?></span></li>
            <li><span>Armory Bonus (from Loadout)</span> <span>+ <?= number_format($defenseBreakdown['armory_bonus']) ?></span></li>
            <li><span>Structure (Fort <?= $defenseBreakdown['fort_level'] ?> + Def <?= $defenseBreakdown['def_level'] ?>)</span> <span>+ <?= $defenseBreakdown['structure_bonus_pct'] * 100 ?>%</span></li>
            <li><span>Constitution (<?= $defenseBreakdown['stat_points'] ?> pts) Bonus</span> <span>+ <?= $defenseBreakdown['stat_bonus_pct'] * 100 ?>%</span></li>
            
            <!-- Alliance Defense Bonus -->
            <?php if ($defenseBreakdown['alliance_bonus_pct'] > 0): ?>
            <li>
                <span style="color: var(--accent-2);">Alliance Structures</span> 
                <span style="color: var(--accent-2);">+ <?= $defenseBreakdown['alliance_bonus_pct'] * 100 ?>%</span>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<div class="data-card grid-col-span-1">
    <div class="card-header">
        <h3>Military Units</h3>
        <a href="/training" class="card-toggle">Train</a>
    </div>
    <ul class="card-stats-list">
        <li><span>Soldiers</span> <span><?= number_format($resources->soldiers) ?></span></li>
        <li><span>Guards</span> <span><?= number_format($resources->guards) ?></span></li>
        <li><span>Spies</span> <span><?= number_format($resources->spies) ?></span></li>
        <li><span>Sentries</span> <span><?= number_format($resources->sentries) ?></span></li>
    </ul>
</div>

<div class="data-card grid-col-span-1">
    <div class="card-header">
        <h3>Structures</h3>
        <a href="/structures" class="card-toggle">Upgrade</a>
    </div>
    <ul class="card-stats-list">
        <li><span>Fortification</span> <span>Lvl <?= $structures->fortification_level ?></span></li>
        <li><span>Offense Upgrade</span> <span>Lvl <?= $structures->offense_upgrade_level ?></span></li>
        <li><span>Defense Upgrade</span> <span>Lvl <?= $structures->defense_upgrade_level ?></span></li>
        <li><span>Spy Upgrade</span> <span>Lvl <?= $structures->spy_upgrade_level ?></span></li>
        <li><span>Economy</span> <span>Lvl <?= $structures->economy_upgrade_level ?></span></li>
        <li><span>Population</span> <span>Lvl <?= $structures->population_level ?></span></li>
        <li><span>Armory</span> <span>Lvl <?= $structures->armory_level ?></span></li>
    </ul>
</div>
</div>
<script src="/js/dashboard.js"></script>