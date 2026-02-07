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
            <div class="mb-1">
                <span class="badge bg-glass border-glass text-neon-blue"><?= htmlspecialchars($user->race ?? 'Unknown Race') ?></span>
                <span class="badge bg-glass border-glass text-accent-2"><?= htmlspecialchars($user->class ?? 'Unknown Class') ?></span>
            </div>
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
            <span>Credits on Hand</span>
            <span><?= number_format($resources->credits) ?></span>
        </li>
                <li>
                    <span>Banked Credits</span>
                    <span><?= number_format($resources->banked_credits) ?></span>
                </li>
            </ul>
                                                
                                                <div class="card-breakdown" id="breakdown-income">
                                                    <!-- Credit Income Readout -->
                                                    <h4 class="breakdown-title text-neon-blue">Credit Income Readout</h4>
                                                    <p class="breakdown-formula text-muted">(Base Income + Worker Income) &times; (1 + Wealth Bonus% + Alliance Bonus%)</p>
                                                    <ul class="calculation-list">
                                                        <?php foreach ($incomeBreakdown['detailed_breakdown'] as $item): ?>
                                                            <li>
                                                                <span><i class="fas <?= htmlspecialchars($item['icon'] ?? 'fa-info-circle') ?> fa-fw me-2"></i><?= htmlspecialchars($item['label']) ?></span>
                                                                <?php if (is_numeric($item['value'])): ?>
                                                                    <span class="value-green">+ <?= number_format($item['value']) ?></span>
                                                                <?php else: ?>
                                                                    <span class="value"><?= htmlspecialchars($item['value']) ?></span>
                                                                <?php endif; ?>
                                                            </li>
                                                        <?php endforeach; ?>
                                                        <?php if ($incomeBreakdown['stat_bonus_pct'] > 0): ?>
                                                            <li>
                                                                <span><i class="fas fa-gem fa-fw me-2"></i>Wealth Bonus (<?= $incomeBreakdown['wealth_points'] ?> pts)</span>
                                                                <span class="value-green">+ <?= $incomeBreakdown['stat_bonus_pct'] * 100 ?>%</span>
                                                            </li>
                                                        <?php endif; ?>
                                                        <?php if ($incomeBreakdown['alliance_credit_bonus_pct'] > 0): ?>
                                                        <li>
                                                            <span><i class="fas fa-handshake fa-fw me-2"></i>Alliance Bonus</span>
                                                            <span class="value-green">+ <?= $incomeBreakdown['alliance_credit_bonus_pct'] * 100 ?>%</span>
                                                        </li>
                                                        <?php endif; ?>
                                                    </ul>
                                                    <div class="breakdown-total">
                                                        <span>Total:</span>
                                                        <span class="value-total">+ <?= number_format($incomeBreakdown['total_credit_income']) ?></span>
                                                    </div>
                                                    
                                                    <hr class="glow-divider my-3">

                                                    <!-- Citizen Growth Readout -->
                                                    <h4 class="breakdown-title text-neon-blue">Citizen Growth Readout</h4>
                                                    <p class="breakdown-formula text-muted">(Base Growth + Alliance Bonus)</p>
                                                    <ul class="calculation-list">
                                                         <li>
                                                            <span><i class="fas fa-user-plus fa-fw me-2"></i>Base Citizen Growth (Population Lvl <?= $incomeBreakdown['pop_level'] ?>)</span>
                                                            <span class="value">+ <?= number_format($incomeBreakdown['base_citizen_income']) ?></span>
                                                        </li>
                                                        <!-- Alliance Citizen Bonus -->
                                                        <?php if ($incomeBreakdown['alliance_citizen_bonus'] > 0): ?>
                                                        <li>
                                                            <span><i class="fas fa-handshake fa-fw me-2"></i>Alliance Bonus</span>
                                                            <span class="value-green">+ <?= number_format($incomeBreakdown['alliance_citizen_bonus']) ?></span>
                                                        </li>
                                                        <?php endif; ?>
                                                    </ul>
                                                    <div class="breakdown-total">
                                                        <span>Total:</span>
                                                        <span class="value-total">+ <?= number_format($incomeBreakdown['total_citizens']) ?></span>
                                                    </div>
                                                </div>                </div>
                
                <div class="data-card grid-col-span-1">
                    <div class="card-header">
                        <h3>Stats</h3>
                        <a href="/level-up" class="card-toggle">Spend Points</a>
                    </div>
                    <ul class="card-stats-list">
                        <li><span>Points to Spend</span>
                            <span class="value-green"><?= number_format($stats->level_up_points) ?></span>
                        </li>
                        <li>
                            <span>Net Worth</span>
                            <span class="value-total"><?= $formatted_net_worth ?></span>
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
                        <!-- Offense Readout -->
                        <h4 class="breakdown-title text-neon-blue">Offense Power Readout</h4>
                        <p class="breakdown-formula text-muted">(Base Unit Power + Armory Bonus) &times; (1 + Stat Bonus% + Alliance Bonus%)</p>
                        <ul class="calculation-list">
                            <li>
                                <span><i class="fas fa-users fa-fw me-2"></i>Base Soldier Power (<?= number_format($offenseBreakdown['unit_count']) ?> Soldiers)</span>
                                <span class="value"><?= number_format($offenseBreakdown['base_unit_power']) ?></span>
                            </li>
                            <li>
                                <span><i class="fas fa-cogs fa-fw me-2"></i>Armory Bonus (from Loadout)</span>
                                <span class="value-green">+ <?= number_format($offenseBreakdown['armory_bonus']) ?></span>
                            </li>
                            <li>
                                <span><i class="fas fa-fist-raised fa-fw me-2"></i>Strength Bonus (<?= $offenseBreakdown['stat_points'] ?> pts)</span>
                                <span class="value-green">+ <?= $offenseBreakdown['stat_bonus_pct'] * 100 ?>%</span>
                            </li>
                            <?php if ($offenseBreakdown['alliance_bonus_pct'] > 0): ?>
                            <li>
                                <span><i class="fas fa-handshake fa-fw me-2"></i>Alliance Bonus</span>
                                <span class="value-green">+ <?= $offenseBreakdown['alliance_bonus_pct'] * 100 ?>%</span>
                            </li>
                            <?php endif; ?>
                        </ul>
                        <div class="breakdown-total">
                            <span>Total:</span>
                            <span class="value-total"><?= number_format($offenseBreakdown['total']) ?></span>
                        </div>

                        <!-- Divider -->
                        <hr class="glow-divider my-3">

                        <!-- Defense Readout -->
                        <h4 class="breakdown-title text-neon-blue">Defense Rating Readout</h4>
                        <p class="breakdown-formula text-muted">(Base Unit Power + Armory Bonus) &times; (1 + Stat Bonus% + Alliance Bonus%)</p>
                        <ul class="calculation-list">
                            <li>
                                <span><i class="fas fa-shield-alt fa-fw me-2"></i>Base Guard Power (<?= number_format($defenseBreakdown['unit_count']) ?> Guards)</span>
                                <span class="value"><?= number_format($defenseBreakdown['base_unit_power']) ?></span>
                            </li>
                            <li>
                                <span><i class="fas fa-cogs fa-fw me-2"></i>Armory Bonus (from Loadout)</span>
                                <span class="value-green">+ <?= number_format($defenseBreakdown['armory_bonus']) ?></span>
                            </li>
                            <li>
                                <span><i class="fas fa-heartbeat fa-fw me-2"></i>Constitution Bonus (<?= $defenseBreakdown['stat_points'] ?> pts)</span>
                                <span class="value-green">+ <?= $defenseBreakdown['stat_bonus_pct'] * 100 ?>%</span>
                            </li>
                            <?php if ($defenseBreakdown['alliance_bonus_pct'] > 0): ?>
                            <li>
                                <span><i class="fas fa-handshake fa-fw me-2"></i>Alliance Bonus</span>
                                <span style="color: var(--accent-2);">+ <?= $defenseBreakdown['alliance_bonus_pct'] * 100 ?>%</span>
                            </li>
                            <?php endif; ?>
                        </ul>
                        <div class="breakdown-total">
                            <span>Total:</span>
                            <span class="value-total"><?= number_format($defenseBreakdown['total']) ?></span>
                        </div>

                        <!-- Divider -->
                        <hr class="glow-divider my-3">

                        <!-- Spy Power Readout -->
                        <h4 class="breakdown-title text-neon-blue">Spy Power Readout</h4>
                        <p class="breakdown-formula text-muted">(Base Unit Power + Armory Bonus) &times; (1 + Stat Bonus%)</p>
                        <ul class="calculation-list">
                            <li>
                                <span><i class="fas fa-user-secret fa-fw me-2"></i>Base Spy Power (<?= number_format($spyBreakdown['unit_count']) ?> Spies)</span>
                                <span class="value"><?= number_format($spyBreakdown['base_unit_power']) ?></span>
                            </li>
                            <li>
                                <span><i class="fas fa-cogs fa-fw me-2"></i>Armory Bonus (from Loadout)</span>
                                <span class="value-green">+ <?= number_format($spyBreakdown['armory_bonus']) ?></span>
                            </li>
                        </ul>
                        <div class="breakdown-total">
                            <span>Total:</span>
                            <span class="value-total"><?= number_format($spyBreakdown['total']) ?></span>
                        </div>

                        <!-- Divider -->
                        <hr class="glow-divider my-3">

                        <!-- Sentry Power Readout -->
                        <h4 class="breakdown-title text-neon-blue">Sentry Power Readout</h4>
                        <p class="breakdown-formula text-muted">(Base Unit Power + Armory Bonus) &times; (1 + Stat Bonus%)</p>
                        <ul class="calculation-list">
                            <li>
                                <span><i class="fas fa-eye fa-fw me-2"></i>Base Sentry Power (<?= number_format($sentryBreakdown['unit_count']) ?> Sentries)</span>
                                <span class="value"><?= number_format($sentryBreakdown['base_unit_power']) ?></span>
                            </li>
                            <li>
                                <span><i class="fas fa-cogs fa-fw me-2"></i>Armory Bonus (from Loadout)</span>
                                <span class="value-green">+ <?= number_format($sentryBreakdown['armory_bonus']) ?></span>
                            </li>
                        </ul>
                        <div class="breakdown-total">
                            <span>Total:</span>
                            <span class="value-total"><?= number_format($sentryBreakdown['total']) ?></span>
                        </div>
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
                
                <!-- NEW: Active Effects Box -->
                <?php if (!empty($activeEffects)): ?>
                <div class="data-card grid-col-span-2">
                    <div class="card-header">
                        <h3 style="color: var(--accent);">Active Effects & Assets</h3>
                    </div>
                
                    <h4 style="margin: 0 0 1rem 0; color: #fff; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">Active Buffs/Debuffs</h4>
                    <ul class="data-list" style="margin-top: 0.5rem;">
                        <?php foreach ($activeEffects as $effect): ?>
                            <li class="data-item" style="justify-content: space-between; padding: 0.75rem 1rem;">
                                <div style="display: flex; gap: 1rem; align-items: center;">
                                    <i class="fas <?= $effect['ui_icon'] ?> <?= $effect['ui_color'] ?>" style="font-size: 1.2rem; width: 25px; text-align: center;"></i>
                                    <span style="font-weight: 600; color: #fff;"><?= $effect['ui_label'] ?></span>
                                </div>
                                <span style="font-family: monospace; font-size: 1rem; color: var(--muted);">
                                    <i class="far fa-clock" style="margin-right: 5px;"></i> <?= $effect['formatted_time_left'] ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <div class="data-card grid-col-span-1">
                    <div class="card-header">
                        <h3>Structures</h3>
                        <a href="/structures" class="card-toggle">Upgrade</a>
                    </div>
                    <ul class="card-stats-list">
                        <li><span>Economy</span> <span>Lvl <?= $structures->economy_upgrade_level ?></span></li>
                        <li><span>Population</span> <span>Lvl <?= $structures->population_level ?></span></li>
                        <li><span>Armory</span> <span>Lvl <?= $structures->armory_level ?></span></li>
                        <li><span>Planetary Shield</span> <span>Lvl <?= $structures->planetary_shield_level ?></span></li>
                    </ul>
                </div>
                </div>
                
                <script src="/js/dashboard.js"></script>