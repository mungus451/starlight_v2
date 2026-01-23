<div id="glossary-top" class="row mb-4">
    <div class="col-12">
        <h1 class="page-title text-center"><i class="fas fa-book-open text-neon-blue"></i> Game Glossary</h1>
        <p class="text-center text-muted">A comprehensive database of Starlight Dominion technology and resources.</p>
    </div>
</div>

<!-- Navigation Tabs -->
<div class="tabs-nav mb-4 justify-content-center">
    <a class="tab-link active" data-tab="structures">Structures</a>
    <a class="tab-link" data-tab="units">Units</a>
    <a class="tab-link" data-tab="armory">Armory</a>
    <a class="tab-link" data-tab="edicts">Edicts</a>
    <a class="tab-link" data-tab="directives">Directives</a>
    <a class="tab-link" data-tab="theater-ops">Theater Ops</a>
    <a class="tab-link" data-tab="resources">Resources</a>
</div>

<!-- ======================= STRUCTURES TAB ======================= -->
<div id="structures" class="tab-content active">
    <div class="structures-grid">
        <?php foreach ($structures as $key => $building): ?>
            <div class="structure-card">
                <div class="card-header-main">
                    <div class="card-icon">
                        <?php 
                        // Map structure types to icons
                        $icon = match($key) {
                            'planetary_shield' => 'fa-shield-alt',
                            'economy_upgrade', 'bank' => 'fa-coins',
                            'population', 'mercenary_outpost' => 'fa-users',
                            'armory' => 'fa-cogs',
                            'neural_uplink', 'subspace_scanner' => 'fa-user-secret',
                            default => 'fa-building'
                        };
                        ?>
                        <i class="fas <?= $icon ?>"></i>
                    </div>
                    <div class="card-title-group">
                        <h3 class="card-title"><?= htmlspecialchars($building['name']) ?></h3>
                        <span class="card-category text-muted"><?= htmlspecialchars($building['category'] ?? 'General') ?></span>
                    </div>
                </div>

                <div class="card-body-main">
                    <p class="text-light mb-3" style="min-height: 40px; font-size: 0.9em;">
                        <?= htmlspecialchars($building['description']) ?>
                    </p>

                    <div class="stats-row" style="display: flex; justify-content: space-between; font-size: 0.85em; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
                        <div>
                            <span class="text-muted">Base Cost:</span><br>
                            <span class="text-warning"><?= number_format($building['base_cost']) ?></span>
                        </div>
                        <div>
                            <span class="text-muted">Scale Factor:</span><br>
                            <span class="text-info">x<?= $building['multiplier'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ======================= UNITS TAB ======================= -->
<div id="units" class="tab-content">
    <div class="structures-grid">
        <?php foreach ($units as $key => $unit): 
            // Skip configuration values that aren't units
            if (!is_array($unit) || !isset($unit['credits'])) continue;
        ?>
            <div class="structure-card">
                <div class="card-header-main">
                    <div class="card-icon">
                        <?php 
                        $icon = match($key) {
                            'workers' => 'fa-hammer text-warning',
                            'soldiers' => 'fa-crosshairs text-danger',
                            'guards' => 'fa-shield-alt text-success',
                            'spies' => 'fa-user-secret text-info',
                            'sentries' => 'fa-eye text-primary',
                            default => 'fa-user'
                        };
                        
                        $role = match($key) {
                            'workers' => 'Resource Generation',
                            'soldiers' => 'Offensive Combat',
                            'guards' => 'Defensive Combat',
                            'spies' => 'Espionage & Theft',
                            'sentries' => 'Counter-Espionage',
                            default => 'Unit'
                        };

                        $desc = match($key) {
                            'workers' => 'The backbone of your economy. Each worker generates 100 Credits per turn.',
                            'soldiers' => 'Frontline troops trained to attack enemy empires and plunder resources.',
                            'guards' => 'Defenders of your realm. They protect your citizens and resources from invasion.',
                            'spies' => 'Covert operatives used to gather intelligence and steal from rivals.',
                            'sentries' => 'Specialized security units that detect and neutralize enemy spies.',
                            default => 'Standard unit.'
                        };
                        ?>
                        <i class="fas <?= $icon ?>"></i>
                    </div>
                    <div class="card-title-group">
                        <h3 class="card-title"><?= ucfirst($key) ?></h3>
                        <span class="card-category text-muted"><?= $role ?></span>
                    </div>
                </div>

                <div class="card-body-main">
                    <p class="text-light mb-3" style="min-height: 40px; font-size: 0.9em;">
                        <?= $desc ?>
                    </p>

                    <div class="stats-row" style="display: flex; justify-content: space-between; font-size: 0.85em; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
                        <div>
                            <span class="text-muted">Credit Cost:</span><br>
                            <span class="text-warning"><?= number_format($unit['credits']) ?></span>
                        </div>
                        <div>
                            <span class="text-muted">Citizens:</span><br>
                            <span class="text-info"><?= number_format($unit['citizens']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
            
    <div class="alert alert-info mt-4">
        <i class="fas fa-info-circle"></i> <strong>Note:</strong> Unit power scales with your Stats (Strength, Defense, etc.) and equipment from the Armory.
    </div>
</div>

<!-- ======================= ARMORY TAB ======================= -->
<div id="armory" class="tab-content" style="padding: 15px;">
    <div class="row">
        <div class="col-12">
            <!-- Dynamic Armory Container -->
            <div id="armory-dynamic-container"></div>
        </div>
    </div>
</div>

<!-- ======================= EDICTS TAB ======================= -->
<div id="edicts" class="tab-content">
    <div class="structures-grid">
        <?php foreach ($edicts as $key => $edict): ?>
            <div class="structure-card">
                <div class="card-header-main">
                    <div class="card-icon">
                        <?php 
                        $icon = match($edict['type']) {
                            'economic' => 'fa-chart-line text-warning',
                            'military' => 'fa-crosshairs text-danger',
                            'espionage' => 'fa-user-secret text-info',
                            'special' => 'fa-star text-neon-blue',
                            default => 'fa-scroll'
                        };
                        ?>
                        <i class="fas <?= $icon ?>"></i>
                    </div>
                    <div class="card-title-group">
                        <h3 class="card-title"><?= htmlspecialchars($edict['name']) ?></h3>
                        <span class="card-category text-muted"><?= ucfirst($edict['type']) ?> Policy</span>
                    </div>
                </div>

                <div class="card-body-main">
                    <p class="text-light mb-3" style="min-height: 40px; font-size: 0.9em;">
                        <?= htmlspecialchars($edict['description']) ?>
                    </p>
                    
                    <div class="mb-3 text-muted small fst-italic">
                        "<?= htmlspecialchars($edict['lore']) ?>"
                    </div>

                    <div class="stats-row" style="display: flex; justify-content: space-between; font-size: 0.85em; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
                        <div>
                            <span class="text-muted">Upkeep:</span><br>
                            <?php if ($edict['upkeep_cost'] > 0): ?>
                                <span class="text-warning"><?= $edict['upkeep_cost'] ?> <?= ucfirst($edict['upkeep_resource']) ?></span>
                            <?php else: ?>
                                <span class="text-success">None</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ======================= DIRECTIVES TAB ======================= -->
<div id="directives" class="tab-content">
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="text-neon-blue"><i class="fas fa-satellite-dish"></i> Alliance Command Directives</h3>
            <p class="text-muted">Directives are strategic mandates set by Alliance Leaders to coordinate members toward a unified objective. Completing directives earns the alliance permanent Merit Badges.</p>
        </div>
    </div>

    <div class="structures-grid">
        <?php foreach ($directives as $key => $dir): ?>
            <div class="structure-card">
                <div class="card-header-main">
                    <div class="card-icon">
                        <i class="fas <?= $dir['icon'] ?> text-neon-blue"></i>
                    </div>
                    <div class="card-title-group">
                        <h3 class="card-title"><?= htmlspecialchars($dir['name']) ?></h3>
                        <span class="card-category text-muted">Command Directive</span>
                    </div>
                </div>

                <div class="card-body-main">
                    <p class="text-light mb-3" style="min-height: 40px; font-size: 0.9em;">
                        <?= htmlspecialchars($dir['description']) ?>
                    </p>

                    <div class="stats-row" style="display: flex; justify-content: space-between; font-size: 0.85em; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
                        <div>
                            <span class="text-muted">Primary Goal:</span><br>
                            <span class="text-info"><?= $dir['goal'] ?></span>
                        </div>
                        <div>
                            <span class="text-muted">Merit Badge:</span><br>
                            <span class="text-warning"><?= $dir['badge'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="alert alert-info mt-4">
        <i class="fas fa-award"></i> <strong>Merit Badges:</strong> Badges upgrade visually as your alliance completes more directives of that type (Bronze &rarr; Silver &rarr; Gold &rarr; Platinum &rarr; Diamond &rarr; Starlight).
    </div>
</div>

<!-- ======================= THEATER OPS TAB ======================= -->
<div id="theater-ops" class="tab-content">
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="text-neon-blue"><i class="fas fa-tasks"></i> Theater Operations</h3>
            <p class="text-muted">Temporary, high-intensity missions that require active contributions from alliance members. Completing these operations grants powerful global buffs.</p>
        </div>
    </div>

    <div class="structures-grid">
        <?php foreach ($allianceOps as $key => $op): ?>
            <div class="structure-card">
                <div class="card-header-main">
                    <div class="card-icon">
                        <i class="fas <?= $op['icon'] ?> text-neon-blue"></i>
                    </div>
                    <div class="card-title-group">
                        <h3 class="card-title"><?= htmlspecialchars($op['name']) ?></h3>
                        <span class="card-category text-muted">Alliance Operation</span>
                    </div>
                </div>

                <div class="card-body-main">
                    <p class="text-light mb-3" style="min-height: 40px; font-size: 0.9em;">
                        <?= htmlspecialchars($op['description']) ?>
                    </p>

                    <div class="stats-row" style="display: flex; justify-content: space-between; font-size: 0.85em; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
                        <div>
                            <span class="text-muted">Requirement:</span><br>
                            <span class="text-warning"><?= $op['requirement'] ?></span>
                        </div>
                        <div>
                            <span class="text-muted">Reward:</span><br>
                            <span class="text-success"><?= $op['reward'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="alert alert-info mt-4">
        <i class="fas fa-info-circle"></i> <strong>Alliance Energy (AE):</strong> Participating in operations generates Alliance Energy, which leaders can spend on Tactical Strikes against rival alliances.
    </div>
</div>

</div>

<!-- ======================= RESOURCES TAB ======================= -->
<div id="resources" class="tab-content">
    <div class="row">
        <?php foreach ($resources as $resKey => $resource): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100" style="background: rgba(13, 17, 23, 0.95); border: 1px solid rgba(255,255,255,0.1);">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas <?= $resource['icon'] ?> fa-3x <?= $resource['color'] ?>"></i>
                        </div>
                        <h4 class="card-title text-light mb-2"><?= $resource['name'] ?></h4>
                        <p class="card-text text-muted mb-4"><?= $resource['description'] ?></p>
                        
                        <div class="text-start p-3 rounded" style="background: rgba(0,0,0,0.3);">
                            <small class="text-uppercase text-muted" style="font-size: 0.75em;">Primary Source</small>
                            <div class="text-light"><?= $resource['source'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    // Inject Armory Data for Dynamic JS
    window.armoryData = <?= json_encode($armory) ?>;

    // Simple Tab Switcher Logic
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof StarlightUtils !== 'undefined') {
            StarlightUtils.initTabs({
                storageKey: 'starlight_glossary_active_tab'
            });
        }
    });
</script>
<script src="/js/glossary_armory.js?v=<?= time() ?>"></script>
