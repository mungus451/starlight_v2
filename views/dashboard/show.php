<?php if ($this->session->get('is_mobile')): ?>

    <?php
    // =================================================================
    // MOBILE "COMMAND CONSOLE" VIEW
    // =================================================================
    ?>
    <!-- Image Lightbox Structure -->
    <div id="avatar-lightbox" class="image-lightbox">
        <span class="lightbox-close">&times;</span>
        <img class="lightbox-content" id="lightbox-img">
    </div>

    <div class="mobile-content">
        <div class="player-hub">
            <a href="#" id="avatar-trigger">
                <?php if ($user->profile_picture_url): ?>
                    <img src="/serve/avatar/<?= htmlspecialchars($user->profile_picture_url) ?>" alt="Avatar" class="player-avatar large">
                <?php else: ?>
                    <svg class="player-avatar large" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                    </svg>
                <?php endif; ?>
            </a>
            <h2 class="player-name"><?= htmlspecialchars($user->characterName) ?></h2>
            <?php if ($user->alliance_id): ?>
                <a href="/alliance/profile/<?= $user->alliance_id ?>" class="player-alliance-link">View Alliance</a>
            <?php else: ?>
                <a href="/alliance/list" class="player-alliance-link">Find an Alliance</a>
            <?php endif; ?>
            <div class="key-stats-grid">
                <div class="key-stat">
                    <span class="stat-label">Level</span>
                    <strong class="stat-value"><?= $stats->level ?></strong>
                </div>
                <div class="key-stat">
                    <span class="stat-label">Citizens</span>
                    <strong class="stat-value"><?= number_format($resources->untrained_citizens) ?></strong>
                </div>
                <div class="key-stat">
                    <span class="stat-label">Turns</span>
                    <strong class="stat-value"><?= number_format($stats->attack_turns) ?></strong>
                </div>
            </div>
        </div>

<!-- Tab Navigation -->
<div id="dashboard-tabs" class="mobile-tabs">
    <a href="#" class="tab-link active" data-tab="overview">Overview</a>
    <a href="#" class="tab-link" data-tab="economics">Economics</a>
    <a href="#" class="tab-link" data-tab="military">Military</a>
    <a href="#" class="tab-link" data-tab="structures">Structures</a>
</div>

        <div id="tab-content">
            <?php require __DIR__ . '/partials/_mobile_overview.php'; ?>
        </div>
    </div>
    
<?php else: ?>

    <?php
    // =================================================================
    // DESKTOP GRID VIEW (Restored Original Content)
    // =================================================================
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
                <div class="player-stat"><span>Level</span><strong><?= $stats->level ?></strong></div>
                <div class="player-stat"><span>Credits</span><strong><?= number_format($resources->credits) ?></strong></div>
                <div class="player-stat"><span>Citizens</span><strong><?= number_format($resources->untrained_citizens) ?></strong></div>
                <div class="player-stat"><span>Workers</span><strong><?= number_format($resources->workers) ?></strong></div>
            </div>
        </div>

        <div class="data-card grid-col-span-2">
            <div class="card-header">
                <h3>Economic Overview</h3>
                <a class="card-toggle" data-target="breakdown-income">Show Breakdown</a>
            </div>
            <ul class="card-stats-list">
                <li><span>Credit Income / Turn</span><span class="value-green value-total">+ <?= number_format($incomeBreakdown['total_credit_income']) ?></span></li>
                <li><span>Bank Interest / Turn</span><span class="value-green value-total">+ <?= number_format($incomeBreakdown['interest']) ?></span></li>
                <li><span>Credits on Hand</span><span><?= number_format($resources->credits) ?></span></li>
                <li><span>Banked Credits</span><span><?= number_format($resources->banked_credits) ?></span></li>
                <li><span>Research Data</span><span><?= number_format($resources->research_data) ?></span></li>
                <li><span>Research Data / Turn</span><span class="value-green value-total">+ <?= number_format($incomeBreakdown['research_data_income']) ?></span></li>
                <li><span>Dark Matter</span><span><?= number_format($resources->dark_matter) ?></span></li>
                <li><span>Dark Matter / Turn</span><span class="value-green value-total">+ <?= number_format($incomeBreakdown['dark_matter_income']) ?></span></li>
            </ul>
        </div>
        
        <div class="data-card grid-col-span-1">
            <div class="card-header"><h3>Stats</h3><a href="/level-up" class="card-toggle">Spend Points</a></div>
            <ul class="card-stats-list">
                <li><span>Points to Spend</span><span class="value-green"><?= number_format($stats->level_up_points) ?></span></li>
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
                <li><span>Offense Power</span><span class="value-red value-total"><?= number_format($offenseBreakdown['total']) ?></span></li>
                <li><span>Defense Rating</span><span class="value-blue value-total"><?= number_format($defenseBreakdown['total']) ?></span></li>
                <li><span>Spy Power</span><span class="value-green"><?= number_format($spyBreakdown['total']) ?></span></li>
                <li><span>Sentry Power</span><span class="value-green"><?= number_format($sentryBreakdown['total']) ?></span></li>
            </ul>
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

        <?php if (!empty($activeEffects) || $resources->untraceable_chips > 0): ?>
        <div class="data-card grid-col-span-2">
            <div class="card-header">
                <h3 style="color: var(--accent);">Active Effects & Assets</h3>
            </div>
            <?php if ($resources->untraceable_chips > 0): ?>
            <p>Untraceable Chips: <?= number_format($resources->untraceable_chips) ?></p>
            <?php endif; ?>
            <?php if (!empty($activeEffects)): ?>
            <p>Active Buffs/Debuffs are present.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
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
                <li><span>Quantum Research Lab</span> <span>Lvl <?= $structures->quantum_research_lab_level ?></span></li>
                <li><span>Nanite Forge</span> <span>Lvl <?= $structures->nanite_forge_level ?></span></li>
                <li><span>Dark Matter Siphon</span> <span>Lvl <?= $structures->dark_matter_siphon_level ?></span></li>
                <li><span>Planetary Shield</span> <span>Lvl <?= $structures->planetary_shield_level ?></span></li>
            </ul>
        </div>
    </div>
    <script src="/js/dashboard.js"></script>
<?php endif; ?>