<?php
/* @var array $costs */
/* @var array $bounties */
/* @var array $targets */
/* @var string $csrf_token */
/* @var array $viewContext */

// Ticker Data (Mock or Passed)
$tickerItems = [
    ['text' => 'Global Crystal Price: +2.4%', 'class' => ''],
    ['text' => 'ALERT: Sector 7 Quarantine Active', 'class' => 'warning'],
    ['text' => 'Recent Transaction: 500k Credits Laundered via [REDACTED]', 'class' => 'gold'],
    ['text' => 'Void Container Decrypted: RARE ITEM FOUND', 'class' => ''],
    ['text' => 'Mercenary Demand Spiking in Outer Rim', 'class' => ''],
    ['text' => 'System Update: Protocol v2.1.0 Online', 'class' => ''],
];
?>

<link rel="stylesheet" href="/css/black_market_v2.css?v=<?= time() ?>">

<!-- Background Atmosphere -->
<div class="void-particles">
    <?php for($i=0; $i<20; $i++): ?>
        <div class="void-particle" style="left: <?= rand(0,100) ?>%; animation-delay: <?= rand(0,10) ?>s; animation-duration: <?= rand(10,20) ?>s;"></div>
    <?php endfor; ?>
</div>

<div class="structures-page-content">
    
    <!-- Header Area -->
    <div class="page-header-container">
        <h1 class="page-title-neon glitch-text" data-text="THE VOID SYNDICATE">THE VOID SYNDICATE</h1>
        <p class="page-subtitle-tech">SYSTEM_ACCESS_GRANTED // ENCRYPTION_LEVEL_0</p>
    </div>

    <!-- The Syndicate Ticker -->
    <div class="bm-ticker-container">
        <div class="bm-ticker-track">
            <?php foreach ($tickerItems as $item): ?>
                <div class="bm-ticker-item <?= $item['class'] ?>">
                    <i class="fas fa-caret-right"></i> <?= htmlspecialchars($item['text']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Navigation -->
    <div class="tabs-nav mb-4 justify-content-center">
        <a href="/black-market/converter" class="tab-link">Crystal Exchange</a>
        <a href="/black-market/actions" class="tab-link active">The Undermarket</a>
    </div>

    <!-- Category Deck -->
    <div class="structure-nav-container">
        <button class="structure-nav-btn active" data-tab-target="cat-specops">
            <i class="fas fa-satellite-dish"></i> Special Ops
        </button>
        <button class="structure-nav-btn" data-tab-target="cat-consumables">
            <i class="fas fa-syringe"></i> Consumables
        </button>
        <button class="structure-nav-btn" data-tab-target="cat-mercs">
            <i class="fas fa-user-shield"></i> Mercenaries
        </button>
        <button class="structure-nav-btn" data-tab-target="cat-finance">
            <i class="fas fa-coins"></i> Financial
        </button>
        <button class="structure-nav-btn" data-tab-target="cat-bounties">
            <i class="fas fa-crosshairs"></i> Bounty Board
        </button>
    </div>

    <!-- Content Deck -->
    <div class="structure-deck">
        
        <!-- SPECIAL OPS -->
        <div id="cat-specops" class="structure-category-container active">
            <!-- Hero: Void Container -->
            <div class="bm-card mb-4 risk-high" style="min-height: auto;">
                <div class="bm-scanline"></div>
                <div class="bm-scanner-bar"></div>
                <div class="bm-card-content" style="flex-direction: row; align-items: stretch;">
                    <div class="bm-card-header" style="flex: 0 0 300px; border-bottom: none; border-right: 1px solid var(--bm-glass-border); flex-direction: column; justify-content: center; text-align: center;">
                        <div class="card-icon" style="font-size: 3rem; width: 80px; height: 80px; margin-bottom: 1rem;">📦</div>
                        <h4 class="text-neon-blue">Void Container</h4>
                        <span class="text-muted">High-Risk Decryption</span>
                    </div>
                    <div class="bm-card-body">
                        <p class="text-light" style="font-size: 1.1rem; line-height: 1.6;">
                            A sealed container recovered from the fringes of known space. 
                            Possible contents: <span class="text-accent">Massive Credits</span>, <span class="text-purple">Dark Matter</span>, or <span class="text-info">Elite Mercenaries</span>.
                        </p>
                        <div class="d-flex align-items-center justify-content-between mt-auto">
                            <span class="text-warning font-monospace" style="font-size: 1.2rem;">
                                COST: <?= number_format($costs['void_container']) ?> 💎
                            </span>
                            
                            <form action="/black-market/buy/lootbox" method="POST" style="width: 250px;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <div class="btn-decrypt-container">
                                    <button class="btn-decrypt">
                                        <div class="btn-decrypt-progress"></div>
                                        <span class="btn-decrypt-text"><i class="fas fa-lock decrypt-lock-icon"></i> DECRYPT</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="black-market-grid">
                <!-- Shadow Contract -->
                <div class="bm-card">
                    <div class="bm-scanline"></div>
                    <div class="bm-card-content">
                        <div class="bm-card-header">
                            <div class="card-icon"><i class="fas fa-user-ninja"></i></div>
                            <div>
                                <h4 style="margin:0;">Shadow Contract</h4>
                                <span class="text-muted small">Anonymous Strike</span>
                            </div>
                        </div>
                        <div class="bm-card-body">
                            <p class="text-muted">Mask your identity during a strike. Logs show "The Void Syndicate".</p>
                            <form action="/black-market/shadow" method="POST" class="mt-auto">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <select name="target_name" class="form-select bg-dark text-light border-secondary mb-2" required>
                                    <option value="" selected disabled>Select Target...</option>
                                    <?php foreach ($targets as $target): ?>
                                        <option value="<?= htmlspecialchars($target['character_name']) ?>"><?= htmlspecialchars($target['character_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-primary w-full">Execute (<?= number_format($costs['shadow_contract']) ?> 💎)</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Radar Jamming -->
                <div class="bm-card">
                    <div class="bm-card-content">
                        <div class="bm-card-header">
                            <div class="card-icon"><i class="fas fa-satellite-dish"></i></div>
                            <div><h4 style="margin:0;">Radar Jamming</h4><span class="text-muted small">Signal Scrambler</span></div>
                        </div>
                        <div class="bm-card-body">
                            <p class="text-muted">Blocks all incoming spy attempts for <span class="text-accent">4 hours</span>.</p>
                            <div class="d-flex justify-content-between align-items-center mt-auto mb-2">
                                <span class="text-warning"><?= number_format($costs['radar_jamming']) ?> 💎</span>
                            </div>
                            <form action="/black-market/buy/radar_jamming" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <button class="btn btn-outline-info w-full">Jam Signals</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Safehouse (Gamified) -->
                <div class="bm-card risk-high">
                    <div class="bm-scanline"></div>
                    <div class="bm-card-content">
                        <div class="bm-card-header">
                            <div class="card-icon"><i class="fas fa-shield-virus"></i></div>
                            <div><h4 style="margin:0;">Safehouse</h4><span class="text-muted small">Grid Ghosting</span></div>
                        </div>
                        <div class="bm-card-body">
                            <p class="text-muted">Immune to attacks for <span class="text-accent">4h</span>. Attack/Spy breaks silence.</p>
                            
                            <?php if (!empty($isSafehouseCooldown)): ?>
                                <div class="badge bg-dark border-warning text-warning w-full text-center p-2 mt-auto">
                                    <i class="fas fa-sync-alt fa-spin me-1"></i> REBOOTING
                                </div>
                            <?php else: ?>
                                <div class="mt-auto">
                                    <span class="text-warning d-block mb-2 text-center"><?= number_format($costs['safehouse']) ?> 💎</span>
                                    <form action="/black-market/buy/safehouse" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <div class="btn-decrypt-container" style="height: 40px; margin-top: 0;">
                                            <button class="btn-decrypt">
                                                <div class="btn-decrypt-progress"></div>
                                                <span class="btn-decrypt-text"><i class="fas fa-lock"></i> GO DARK</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- High Risk Protocol -->
                <div class="bm-card risk-high">
                    <div class="bm-card-content">
                        <div class="bm-card-header">
                            <div class="card-icon text-danger"><i class="fas fa-radiation"></i></div>
                            <div><h4 style="margin:0;">High Risk</h4><span class="text-danger small">Combat Stance</span></div>
                        </div>
                        <div class="bm-card-body">
                            <p class="text-muted"><span class="text-success">+50% Income</span>. Disables Safehouse. +Vuln.</p>
                            <?php if (!empty($isHighRiskActive)): ?>
                                <div class="badge bg-danger text-white w-full text-center p-2 mt-auto">PROTOCOL ACTIVE</div>
                                <form action="/black-market/buy/terminate_high_risk" method="POST" class="mt-2">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <button class="btn btn-sm btn-outline-danger w-full">Terminate</button>
                                </form>
                            <?php else: ?>
                                <span class="text-warning d-block mb-2 mt-auto text-center"><?= number_format($costs['high_risk_buff']) ?> 💎</span>
                                <form action="/black-market/buy/high_risk" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <button class="btn btn-danger w-full">Initiate</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Safehouse Cracker -->
                <div class="bm-card">
                    <div class="bm-card-content">
                        <div class="bm-card-header">
                            <div class="card-icon"><i class="fas fa-unlock"></i></div>
                            <div><h4 style="margin:0;">Cracker</h4><span class="text-muted small">Target Acquisition</span></div>
                        </div>
                        <div class="bm-card-body">
                            <p class="text-muted">Bribe officials for safehouse location. 1 attack permitted.</p>
                            <span class="text-warning d-block mb-2 mt-auto text-center"><?= number_format($costs['safehouse_cracker']) ?> 💎</span>
                            <form action="/black-market/buy/safehouse_cracker" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <button class="btn btn-outline-danger w-full">Bribe Official</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CONSUMABLES -->
        <div id="cat-consumables" class="structure-category-container">
            <div class="black-market-grid">
                <!-- Neural Rewiring -->
                <div class="bm-card">
                    <div class="bm-card-content">
                        <div class="bm-card-header">
                            <div class="card-icon"><i class="fas fa-brain"></i></div>
                            <div><h4 style="margin:0;">Rewiring</h4><span class="text-muted small">Stat Reset</span></div>
                        </div>
                        <div class="bm-card-body">
                            <p class="text-muted">Wipe neural pathways. Reallocate all stats.</p>
                            <span class="text-warning d-block mb-2 mt-auto text-center"><?= number_format($costs['stat_respec']) ?> 💎</span>
                            <form action="/black-market/buy/respec" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <button class="btn btn-outline-info w-full">Reset Stats</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Stim-Pack -->
                <div class="bm-card">
                    <div class="bm-card-content">
                        <div class="bm-card-header">
                            <div class="card-icon"><i class="fas fa-bolt"></i></div>
                            <div><h4 style="margin:0;">Stim-Pack</h4><span class="text-muted small">Neural Overclock</span></div>
                        </div>
                        <div class="bm-card-body">
                            <p class="text-muted">Restore <span class="text-success">50 Attack Turns</span> instantly.</p>
                            <span class="text-warning d-block mb-2 mt-auto text-center"><?= number_format($costs['turn_refill']) ?> 💎</span>
                            <form action="/black-market/buy/refill" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <button class="btn btn-outline-info w-full">Inject</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Citizens -->
                <div class="bm-card">
                    <div class="bm-card-content">
                        <div class="bm-card-header">
                            <div class="card-icon"><i class="fas fa-user-friends"></i></div>
                            <div><h4 style="margin:0;">Import</h4><span class="text-muted small">Population Growth</span></div>
                        </div>
                        <div class="bm-card-body">
                            <p class="text-muted">Import <span class="text-info">50,000 Citizens</span>.</p>
                            <span class="text-warning d-block mb-2 mt-auto text-center"><?= number_format($costs['citizen_package']) ?> 💎</span>
                            <form action="/black-market/buy/citizens" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <button class="btn btn-outline-info w-full">Process Import</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MERCENARIES -->
        <div id="cat-mercs" class="structure-category-container">
            <div class="black-market-grid">
                <?php if (($viewContext['structures']->mercenary_outpost_level ?? 0) > 0): ?>
                <div class="bm-card">
                    <div class="bm-scanline"></div>
                    <div class="bm-card-content">
                        <div class="bm-card-header">
                            <div class="card-icon"><i class="fas fa-shield-alt"></i></div>
                            <div><h4 style="margin:0;">Outpost</h4><span class="text-muted small">Mercenary Draft</span></div>
                        </div>
                        <div class="bm-card-body">
                            <p class="text-muted">Draft units using <span class="text-purple">Dark Matter</span>.</p>
                            <form action="/black-market/draft" method="POST" class="mt-auto">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <div class="row g-2 mb-2">
                                    <div class="col-7">
                                        <select name="unit_type" class="form-select bg-dark text-light border-secondary">
                                            <option value="soldiers">Soldiers</option>
                                            <option value="guards">Guards</option>
                                            <option value="spies">Spies</option>
                                            <option value="sentries">Sentries</option>
                                        </select>
                                    </div>
                                    <div class="col-5">
                                        <input type="number" name="quantity" class="form-control" placeholder="Qty" min="1">
                                    </div>
                                </div>
                                <button class="btn btn-outline-info w-full">Draft</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                    <div class="alert alert-warning text-center w-100">
                        <i class="fas fa-lock"></i> Construct a Mercenary Outpost to access this sector.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- FINANCIAL -->
        <div id="cat-finance" class="structure-category-container">
            <div class="black-market-grid">
                <div class="bm-card">
                    <div class="bm-card-content">
                        <div class="bm-card-header">
                            <div class="card-icon"><i class="fas fa-coins"></i></div>
                            <div><h4 style="margin:0;">Laundering</h4><span class="text-muted small">Untraceable Chips</span></div>
                        </div>
                        <div class="bm-card-body">
                            <p class="text-muted">Convert Credits to Chips (1.15 : 1). Safe from theft.</p>
                            <form action="/black-market/launder" method="POST" class="mt-auto">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <div class="mb-2">
                                    <input type="hidden" name="amount" id="launder-amount-hidden" value="0">
                                    <input type="text" id="launder-amount-display" class="form-control" placeholder="Amount" required>
                                </div>
                                <button class="btn btn-outline-success w-full">Launder</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BOUNTIES -->
        <div id="cat-bounties" class="structure-category-container">
            <div class="bm-card risk-high" style="min-height: auto;">
                <div class="bm-scanline"></div>
                <div class="bm-card-content">
                    <div class="bm-card-header">
                        <div class="card-icon text-danger"><i class="fas fa-crosshairs"></i></div>
                        <div><h4 style="margin:0;">Bounty Board</h4><span class="text-muted small">Public Contracts</span></div>
                    </div>
                    <div class="bm-card-body">
                        <div class="row">
                            <div class="col-md-7">
                                <div class="bg-dark p-3 rounded" style="max-height: 300px; overflow-y: auto;">
                                    <table class="table table-dark table-hover mb-0">
                                        <thead><tr class="text-muted small text-uppercase"><th>Target</th><th class="text-end">Reward</th></tr></thead>
                                        <tbody>
                                            <?php if (empty($bounties)): ?>
                                                <tr><td colspan="2" class="text-center text-muted">No active bounties.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($bounties as $b): ?>
                                                    <tr>
                                                        <td><strong class="text-light"><?= htmlspecialchars($b['target_name']) ?></strong></td>
                                                        <td class="text-end text-warning"><?= number_format($b['amount']) ?> 💎</td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <form action="/black-market/bounty/place" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <div class="mb-3">
                                        <select name="target_name" class="form-select bg-dark text-light border-secondary" required>
                                            <option value="" selected disabled>Select Target</option>
                                            <?php foreach ($targets as $target): ?>
                                                <option value="<?= htmlspecialchars($target['character_name']) ?>"><?= htmlspecialchars($target['character_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <input type="hidden" name="amount" id="bounty-amount-hidden" value="0">
                                        <input type="text" id="bounty-amount-display" class="form-control" placeholder="Reward (Min 100)" required>
                                    </div>
                                    <button class="btn btn-danger w-full">POST CONTRACT</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="/js/black_market_ui.js?v=<?= time() ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Input Masks ---
        const launderDisplay = document.getElementById('launder-amount-display');
        const launderHidden = document.getElementById('launder-amount-hidden');
        if (launderDisplay && launderHidden) StarlightUtils.setupInputMask(launderDisplay, launderHidden);

        const bountyDisplay = document.getElementById('bounty-amount-display');
        const bountyHidden = document.getElementById('bounty-amount-hidden');
        if (bountyDisplay && bountyHidden) StarlightUtils.setupInputMask(bountyDisplay, bountyHidden);

        // --- Category Tabs Switcher ---
        const navBtns = document.querySelectorAll('.structure-nav-btn');
        const categories = document.querySelectorAll('.structure-category-container');

        navBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                navBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                const targetId = btn.getAttribute('data-tab-target');
                categories.forEach(cat => {
                    cat.classList.toggle('active', cat.id === targetId);
                });
            });
        });
    });
</script>
