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
    <a class="tab-link" data-tab="black-market">Black Market</a>
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
                            'fortification', 'defense_upgrade', 'planetary_shield', 'phase_bunker' => 'fa-shield-alt',
                            'offense_upgrade', 'weapon_vault', 'nanite_forge', 'ion_cannon_network' => 'fa-crosshairs',
                            'spy_upgrade', 'quantum_research_lab', 'embassy', 'neural_uplink', 'subspace_scanner' => 'fa-user-secret',
                            'economy_upgrade', 'accounting_firm', 'bank', 'fusion_plant', 'orbital_trade_port', 'banking_datacenter' => 'fa-coins',
                            'population', 'cloning_vats', 'war_college', 'mercenary_outpost' => 'fa-users',
                            'armory' => 'fa-cogs',
                            'naquadah_mining_complex' => 'fa-gem',
                            'dark_matter_siphon' => 'fa-atom',
                            'protoform_vat' => 'fa-flask',
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
                        <?php if (isset($building['base_crystal_cost'])): ?>
                            <div>
                                <span class="text-muted">Crystals:</span><br>
                                <span class="text-neon-blue"><?= number_format($building['base_crystal_cost']) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($building['base_dark_matter_cost'])): ?>
                            <div>
                                <span class="text-muted">Dark Matter:</span><br>
                                <span style="color: #a855f7;"><?= number_format($building['base_dark_matter_cost']) ?></span>
                            </div>
                        <?php endif; ?>
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
<div id="armory" class="tab-content" style="background-color: rgba(0, 0, 0, 0.4); padding: 15px; border-radius: 8px;">
    <div class="row">
        <div class="col-md-3 mb-4">
            <!-- Unit Filter Buttons (could be implemented as sub-tabs or scroll links) -->
            <div class="list-group bg-transparent">
                <?php foreach ($armory as $unitKey => $unitData): ?>
                    <a href="#armory-<?= $unitKey ?>" class="list-group-item list-group-item-action bg-dark text-light border-secondary">
                        <?= htmlspecialchars($unitData['title']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="col-md-9">
            <?php foreach ($armory as $unitKey => $unitData): ?>
                <div id="armory-<?= $unitKey ?>" class="mb-5">
                    <h3 class="border-bottom border-secondary pb-2 mb-4 text-neon-blue">
                        <?= htmlspecialchars($unitData['title']) ?>
                    </h3>
                    
                    <?php foreach ($unitData['categories'] as $catKey => $category): ?>
                        <div class="card bg-dark border-secondary mb-4">
                            <div class="card-header border-secondary">
                                <h5 class="mb-0 text-uppercase text-muted" style="font-size: 0.9rem; letter-spacing: 1px;">
                                    <?= htmlspecialchars($category['title']) ?>
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-dark table-sm mb-0 align-middle">
                                        <thead>
                                            <tr class="text-muted" style="font-size: 0.85em;">
                                                <th class="ps-3">Item Name</th>
                                                <th>Effect</th>
                                                <th>Cost</th>
                                                <th>Req Level</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($category['items'] as $itemKey => $item): ?>
                                                <tr>
                                                    <td class="ps-3">
                                                        <strong class="text-light"><?= htmlspecialchars($item['name']) ?></strong><br>
                                                        <span class="text-muted small">
                                                            <?= htmlspecialchars($item['notes'] ?? '') ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if (isset($item['attack'])): ?>
                                                            <span class="text-danger"><i class="fas fa-fist-raised"></i> +<?= number_format($item['attack']) ?> ATK</span>
                                                        <?php elseif (isset($item['defense'])): ?>
                                                            <span class="text-success"><i class="fas fa-shield-alt"></i> +<?= number_format($item['defense']) ?> DEF</span>
                                                        <?php elseif (isset($item['credit_bonus'])): ?>
                                                            <span class="text-warning"><i class="fas fa-coins"></i> +<?= number_format($item['credit_bonus']) ?>% Inc</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="text-warning"><?= number_format($item['cost']) ?> cr</span>
                                                        <?php if (!empty($item['cost_crystals'])): ?>
                                                            <br><span class="text-neon-blue"><?= number_format($item['cost_crystals']) ?> crys</span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($item['cost_dark_matter'])): ?>
                                                            <br><span style="color: #a855f7;"><?= number_format($item['cost_dark_matter']) ?> DM</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-muted text-center">
                                                        <?= isset($item['armory_level_req']) ? 'Lvl ' . $item['armory_level_req'] : '-' ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="text-end">
                        <a href="#glossary-top" class="text-muted small text-decoration-none back-to-top-link">
                            <i class="fas fa-arrow-up"></i> Back to Top
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
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

<!-- ======================= BLACK MARKET TAB ======================= -->
<div id="black-market" class="tab-content">
    <div class="row mb-5">
        <div class="col-12">
            <h3 class="border-bottom border-secondary pb-2 mb-4 text-neon-blue">Market Services</h3>
            <div class="table-responsive">
                <table class="table table-dark table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Item / Service</th>
                            <th>Description</th>
                            <th>Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $costs = $blackMarket['costs'] ?? [];
                        $quantities = $blackMarket['quantities'] ?? [];
                        
                        $services = [
                            'stat_respec' => ['name' => 'Stat Respec', 'desc' => 'Reset and redistribute your core stat points.'],
                            'turn_refill' => ['name' => 'Turn Refill', 'desc' => 'Instantly restores ' . ($quantities['turn_refill_amount'] ?? 50) . ' turns.'],
                            'citizen_package' => ['name' => 'Citizen Stimulus', 'desc' => 'Adds ' . number_format($quantities['citizen_package_amount'] ?? 50000) . ' citizens to your population.'],
                            'void_container' => ['name' => 'Void Container', 'desc' => 'A mysterious container from the Void. Contains random loot.'],
                            'shadow_contract' => ['name' => 'Shadow Contract', 'desc' => 'Hires a mercenary fleet for temporary protection.'],
                            'radar_jamming' => ['name' => 'Radar Jamming', 'desc' => 'Prevents others from spying on you for a short duration.'],
                            'safehouse' => ['name' => 'Safehouse Protocol', 'desc' => 'Protect your empire from all attacks for 4 hours.'],
                            'safehouse_cracker' => ['name' => 'Safehouse Cracker', 'desc' => 'Bypass one enemy safehouse protection.'],
                            'high_risk_buff' => ['name' => 'High Risk Booster', 'desc' => 'Grants significant bonuses but with dangerous side effects.']
                        ];
                        
                        foreach ($services as $key => $info):
                            if (!isset($costs[$key])) continue;
                        ?>
                        <tr>
                            <td class="fw-bold text-light"><?= $info['name'] ?></td>
                            <td class="text-muted"><?= $info['desc'] ?></td>
                            <td class="text-neon-blue"><?= number_format($costs[$key]) ?> Crystals</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h3 class="border-bottom border-secondary pb-2 mb-4 text-purple">Void Container Probability Matrix</h3>
            <p class="text-muted mb-4">
                The Void Container extracts items from across the multiverse. 
                <span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Warning:</span> Not all outcomes are beneficial.
            </p>
            
            <div class="table-responsive">
                <table class="table table-dark table-sm table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Outcome</th>
                            <th>Type</th>
                            <th>Probability</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $loot = $blackMarket['void_container_loot'] ?? [];
                        $totalWeight = array_sum(array_column($loot, 'weight'));
                        
                        // Sort by probability desc
                        uasort($loot, function($a, $b) {
                            return $b['weight'] <=> $a['weight'];
                        });

                        foreach ($loot as $key => $item):
                            $chance = ($item['weight'] / $totalWeight) * 100;
                            $rowClass = '';
                            $typeLabel = '';
                            
                            if (str_contains($key, 'trap') || str_contains($key, 'ambush') || str_contains($key, 'debuff') || str_contains($key, 'cursed')) {
                                $rowClass = 'text-danger';
                                $typeLabel = 'DANGER';
                            } elseif (str_contains($key, 'jackpot')) {
                                $rowClass = 'text-warning fw-bold';
                                $typeLabel = 'JACKPOT';
                            } elseif ($key === 'space_dust' || $key === 'scrap_metal') {
                                $rowClass = 'text-muted';
                                $typeLabel = 'Junk';
                            } else {
                                $rowClass = 'text-success';
                                $typeLabel = 'Reward';
                            }
                        ?>
                        <tr>
                            <td class="<?= $rowClass ?>">
                                <?= htmlspecialchars($item['label']) ?>
                                <?php if(isset($item['text'])): ?>
                                    <div class="small text-muted fst-italic"><?= htmlspecialchars($item['text']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary text-light"><?= $typeLabel ?></span>
                            </td>
                            <td>
                                <div class="progress" style="height: 20px; background: rgba(255,255,255,0.1);">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?= $chance ?>%;" aria-valuenow="<?= $chance ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?= number_format($chance, 1) ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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
    // Simple Tab Switcher Logic
    document.addEventListener('DOMContentLoaded', () => {
        const tabs = document.querySelectorAll('.tab-link');
        const contents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active class from all
                tabs.forEach(t => t.classList.remove('active'));
                contents.forEach(c => c.classList.remove('active'));

                // Add active to current
                tab.classList.add('active');
                const targetId = tab.getAttribute('data-tab');
                document.getElementById(targetId).classList.add('active');
            });
        });
        
        // Armory scroll spy/smooth scroll
        const armoryLinks = document.querySelectorAll('a[href^="#armory-"]');
        armoryLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = link.getAttribute('href').substring(1);
                const target = document.getElementById(targetId);
                
                // Switch to armory tab if not active
                const armoryTab = document.querySelector('[data-tab="armory"]');
                if (!armoryTab.classList.contains('active')) {
                    armoryTab.click();
                }
                
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    });
</script>
