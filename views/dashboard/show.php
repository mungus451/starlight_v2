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

<style>
    :root {
        --bg-panel: rgba(12, 14, 25, 0.68);
        --card: radial-gradient(circle at 30% -10%, rgba(45, 209, 209, 0.08), rgba(13, 15, 27, 0.75));
        --border: rgba(255, 255, 255, 0.035);
        --accent: #2dd1d1;
        --accent-2: #f9c74f;
        --accent-green: #4CAF50;
        --accent-red: #e53e3e;
        --accent-blue: #7683f5;
        --text: #eff1ff;
        --muted: #a8afd4;
        --radius: 18px;
        --shadow: 0 14px 35px rgba(0, 0, 0, 0.4);
    }

    /* --- Base Grid --- */
    .dashboard-grid {
        text-align: left;
        width: 100%;
        margin: 0 auto;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        grid-template-rows: auto auto auto 1fr;
        gap: 1.5rem;
        position: relative;
    }

    /* --- Grid Spans --- */
    .grid-col-span-1 { grid-column: span 1; }
    .grid-col-span-2 { grid-column: span 2; }
    .grid-col-span-3 { grid-column: span 3; }
    .grid-row-span-2 { grid-row: span 2; }
    
    /* --- Responsive Grid Logic --- */
    @media (max-width: 1200px) {
        .dashboard-grid {
            grid-template-columns: 1fr 1fr;
        }
        .grid-col-span-2 { grid-column: span 2; }
        /* Reset row spans on tablet to prevent gaps */
        .grid-row-span-2 { grid-row: span 1; } 
    }

    @media (max-width: 768px) {
        .dashboard-grid {
            display: flex; /* Switch to Flexbox for simpler vertical stacking */
            flex-direction: column;
            gap: 1rem;
        }
        
        /* Reset all grid properties for mobile components */
        .grid-col-span-1, .grid-col-span-2, .grid-col-span-3, .grid-row-span-2 {
            grid-column: auto;
            grid-row: auto;
            width: 100%;
        }
    }

    /* --- Player Header --- */
    .player-header {
        grid-column: 1 / -1;
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1rem 1.5rem;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 1.5rem;
        box-shadow: var(--shadow);
        backdrop-filter: blur(6px);
    }
    .player-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .player-avatar {
        width: 80px; /* Slightly smaller for better mobile fit */
        height: 80px;
        flex-shrink: 0;
        border-radius: 50%;
        background: #1e1e3f;
        border: 2px solid var(--accent);
        object-fit: cover;
    }
    .player-avatar-svg {
        padding: 1rem;
        color: var(--muted);
    }
    
    .player-info h2 {
        margin: 0;
        font-size: 1.4rem;
        color: #fff;
        line-height: 1.2;
    }
    .player-info .sub-text {
        font-size: 0.9rem;
        color: var(--muted);
        display: block;
        margin-top: 0.25rem;
    }
    .player-info .sub-text a {
        color: var(--accent);
        text-decoration: none;
        font-weight: 600;
        padding: 0.5rem 0; /* Touch padding */
    }
    
    .player-stats {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        text-align: right;
        justify-content: flex-end;
    }
    .player-stat {
        min-width: 80px;
    }
    .player-stat span {
        font-size: 0.75rem;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        display: block;
        margin-bottom: 0.25rem;
    }
    .player-stat strong {
        font-size: 1.2rem;
        color: #fff;
        font-weight: 600;
    }
    
    @media (max-width: 768px) {
        .player-header {
            flex-direction: column;
            align-items: flex-start;
            padding: 1.25rem;
        }
        .player-stats {
            width: 100%;
            justify-content: space-between;
            text-align: left;
            border-top: 1px solid var(--border);
            padding-top: 1rem;
        }
        .player-stat {
            text-align: center;
            min-width: auto;
            flex: 1;
        }
    }
    
    /* --- Main Data Card --- */
    .data-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.25rem;
        box-shadow: var(--shadow);
        backdrop-filter: blur(6px);
        display: flex;
        flex-direction: column;
    }
    
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        border-bottom: 1px solid var(--border);
        padding-bottom: 0.75rem;
    }
    .card-header h3 {
        color: #fff;
        margin: 0;
        font-size: 1.1rem;
        letter-spacing: 0.02em;
    }
    
    /* Touch-optimized Toggle */
    .card-toggle {
        font-size: 0.85rem;
        color: var(--muted);
        cursor: pointer;
        text-decoration: none;
        /* Hitbox expansion */
        padding: 0.75rem;
        margin: -0.75rem; 
        user-select: none;
    }
    @media (hover: hover) {
        .card-toggle:hover {
            color: var(--accent);
            text-decoration: underline;
        }
    }
    .card-toggle:active {
        color: var(--accent);
        opacity: 0.8;
    }
    
    /* Stats List */
    .card-stats-list {
        list-style: none;
        padding: 0;
        margin: 0 0 0.5rem 0;
        flex-grow: 1;
    }
    .card-stats-list li {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0.25rem; /* Increased vertical padding for touch spacing */
        border-bottom: 1px solid rgba(58, 58, 90, 0.08);
    }
    .card-stats-list li:last-child {
        border-bottom: none;
    }
    .card-stats-list li span:first-child {
        font-size: 0.9rem;
        color: var(--muted);
    }
    .card-stats-list li span:last-child {
        font-size: 1.1rem;
        color: var(--text);
        font-weight: 600;
    }
    
    /* Value Colors */
    .value-total {
        font-size: 1.4rem !important;
        font-weight: 700 !important;
    }
    .value-green { color: var(--accent-green) !important; }
    .value-blue { color: var(--accent-blue) !important; }
    .value-red { color: var(--accent-red) !important; }

    /* Breakdown (Inline Card) */
    .card-breakdown {
        display: none;
        background: rgba(5, 7, 18, 0.4);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 1rem;
        margin-top: 0.5rem;
    }
    .card-breakdown.active {
        display: block;
        animation: fadeIn 0.2s ease-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .card-breakdown ul {
        list-style: none;
        padding: 0;
        margin: 0.5rem 0 1rem 0;
    }
    .card-breakdown li {
        display: flex;
        justify-content: space-between;
        font-size: 0.85rem;
        color: var(--muted);
        padding: 0.35rem 0;
    }
    .card-breakdown li span:last-child {
        color: var(--text);
        font-weight: 600;
    }
</style>

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
                    <span>+ <?= number_format($incomeBreakdown['total_citizens']) ?></span>
                </li>
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
            </ul>
            <br>
            <strong>Defense Rating (<?= number_format($defenseBreakdown['unit_count']) ?> Guards)</strong>
            <ul>
                <li><span>Base Guard Power</span> <span><?= number_format($defenseBreakdown['base_unit_power']) ?></span></li>
                <li><span>Armory Bonus (from Loadout)</span> <span>+ <?= number_format($defenseBreakdown['armory_bonus']) ?></span></li>
                <li><span>Structure (Fort <?= $defenseBreakdown['fort_level'] ?> + Def <?= $defenseBreakdown['def_level'] ?>)</span> <span>+ <?= $defenseBreakdown['structure_bonus_pct'] * 100 ?>%</span></li>
                <li><span>Constitution (<?= $defenseBreakdown['stat_points'] ?> pts) Bonus</span> <span>+ <?= $defenseBreakdown['stat_bonus_pct'] * 100 ?>%</span></li>
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