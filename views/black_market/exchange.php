<?php
/**
 * @var \App\Models\Entities\UserResource $userResources
 * @var \App\Models\Entities\HouseFinance $houseFinances
 * @var float $conversionRate
 * @var float $feePercentage
 * @var string $csrf_token
 */
?>

<link rel="stylesheet" href="/css/black_market_v2.css?v=<?= time() ?>">

<!-- Background Atmosphere -->
<div class="void-particles">
    <?php for($i=0; $i<20; $i++): ?>
        <div class="void-particle" style="left: <?= rand(0,100) ?>%; animation-delay: <?= rand(0,10) ?>s; animation-duration: <?= rand(10,20) ?>s;"></div>
    <?php endfor; ?>
</div>

<div class="structures-page-content">
    <div class="page-header-container">
        <h1 class="page-title-neon glitch-text" data-text="THE VOID SYNDICATE">THE VOID SYNDICATE</h1>
        <p class="page-subtitle-tech">SYSTEM_ACCESS_GRANTED // ENCRYPTION_LEVEL_0</p>
    </div>

    <div class="tabs-nav mb-4 justify-content-center">
        <a href="/black-market/converter" class="tab-link active">Crystal Exchange</a>
        <a href="/black-market/actions" class="tab-link">The Undermarket</a>
    </div>

    <!-- Quick Wallet Bar (Always Visible) -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="dashboard-card text-center py-3" style="border-left: 3px solid var(--accent);">
                <span class="text-muted small text-uppercase fw-bold">Credits</span>
                <h3 class="mb-0 mt-1"><?= number_format($userResources->credits, 2) ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card text-center py-3" style="border-left: 3px solid var(--accent-2);">
                <span class="text-muted small text-uppercase fw-bold">Naquadah Crystals</span>
                <h3 class="mb-0 mt-1 text-warning"><?= number_format($userResources->naquadah_crystals, 4) ?> <i class="fas fa-gem small"></i></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card text-center py-3" style="border-left: 3px solid #a855f7;">
                <span class="text-muted small text-uppercase fw-bold">Dark Matter</span>
                <h3 class="mb-0 mt-1" style="color: #d8b4fe;"><?= number_format($userResources->dark_matter, 4) ?> <i class="fas fa-atom small"></i></h3>
            </div>
        </div>
    </div>

    <!-- Category Navigation Deck -->
    <div class="structure-nav-container">
        <button class="structure-nav-btn active" data-tab-target="cat-exchange">
            <i class="fas fa-exchange-alt"></i> Currency Exchange
        </button>
        <button class="structure-nav-btn" data-tab-target="cat-synthesis">
            <i class="fas fa-atom"></i> Matter Synthesis
        </button>
    </div>

    <!-- Central Viewing Containers -->
    <div class="structure-deck">

        <!-- CATEGORY: CURRENCY EXCHANGE -->
        <div id="cat-exchange" class="structure-category-container active">
            <div class="row g-4">
                <!-- Credits -> Crystals -->
                <div class="col-md-6">
                    <div class="bm-card h-100">
                        <div class="bm-card-content">
                            <div class="bm-card-header">
                                <div class="card-icon"><i class="fas fa-coins"></i></div>
                                <div><h4 style="margin:0;">Credits <i class="fas fa-arrow-right mx-2 small text-muted"></i> Crystals</h4><span class="text-muted small">Acquire Naquadah</span></div>
                            </div>
                            <div class="bm-card-body">
                                <form action="/black-market/convert" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <input type="hidden" name="conversion_direction" value="credits_to_crystals">

                                    <div class="form-group mb-3">
                                        <label class="text-muted small mb-1">Amount to Convert</label>
                                        <div class="input-group">
                                            <input type="text" inputmode="numeric" id="credits-amount-display" class="form-control bg-dark text-light border-secondary" placeholder="0" required>
                                            <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('credits-amount-display').value = '<?= (int)$userResources->credits ?>'; document.getElementById('credits-amount-display').dispatchEvent(new Event('input'));">MAX</button>
                                        </div>
                                        <input type="hidden" name="amount" id="credits-amount-hidden">
                                    </div>

                                    <div class="bg-dark p-3 rounded mb-3 border border-secondary" style="font-size: 0.9rem;">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Exchange Rate:</span>
                                            <span>1,000 : 1.00</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Syndicate Fee (<?= $feePercentage * 100 ?>%):</span>
                                            <span id="c2cry-fee" class="text-danger">0.00 Credits</span>
                                        </div>
                                        <hr class="my-2 border-secondary">
                                        <div class="d-flex justify-content-between fw-bold">
                                            <span class="text-light">Net Crystals:</span>
                                            <span id="c2cry-receive" class="text-neon-blue">0.0000 💎</span>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-full py-2">PURCHASE CRYSTALS</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Crystals -> Credits -->
                <div class="col-md-6">
                    <div class="bm-card h-100">
                        <div class="bm-card-content">
                            <div class="bm-card-header">
                                <div class="card-icon text-warning"><i class="fas fa-gem"></i></div>
                                <div><h4 style="margin:0;">Crystals <i class="fas fa-arrow-right mx-2 small text-muted"></i> Credits</h4><span class="text-muted small">Liquidate Assets</span></div>
                            </div>
                            <div class="bm-card-body">
                                <form action="/black-market/convert" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <input type="hidden" name="conversion_direction" value="crystals_to_credits">

                                    <div class="form-group mb-3">
                                        <label class="text-muted small mb-1">Amount to Convert</label>
                                        <div class="input-group">
                                            <input type="text" inputmode="numeric" id="crystals-amount-display" class="form-control bg-dark text-light border-secondary" placeholder="0" required>
                                            <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('crystals-amount-display').value = '<?= floor($userResources->naquadah_crystals) ?>'; document.getElementById('crystals-amount-display').dispatchEvent(new Event('input'));">MAX</button>
                                        </div>
                                        <input type="hidden" name="amount" id="crystals-amount-hidden">
                                    </div>

                                    <div class="bg-dark p-3 rounded mb-3 border border-secondary" style="font-size: 0.9rem;">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Exchange Rate:</span>
                                            <span>1.00 : 1,000</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Syndicate Fee (<?= $feePercentage * 100 ?>%):</span>
                                            <span id="cry2c-fee" class="text-danger">0.0000 💎</span>
                                        </div>
                                        <hr class="my-2 border-secondary">
                                        <div class="d-flex justify-content-between fw-bold">
                                            <span class="text-light">Net Credits:</span>
                                            <span id="cry2c-receive" class="text-neon-blue">0.00 Credits</span>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-full py-2">SELL CRYSTALS</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CATEGORY: MATTER SYNTHESIS -->
        <div id="cat-synthesis" class="structure-category-container">
            <div class="alert alert-dark border-secondary d-flex align-items-center mb-4" role="alert">
                <i class="fas fa-exclamation-triangle text-warning me-3" style="font-size: 1.5rem;"></i>
                <div>
                    <strong class="text-warning">WARNING: Irreversible Process</strong><br>
                    <span class="text-muted small">Matter Synthesis fuses raw materials into Dark Matter. This process cannot be undone.</span>
                </div>
            </div>

            <div class="row g-4">
                <!-- Credits -> DM -->
                <div class="col-md-6">
                    <div class="bm-card border-neon" style="border-color: rgba(168, 85, 247, 0.4);">
                        <div class="bm-card-content">
                            <div class="bm-card-header" style="background: rgba(168, 85, 247, 0.05);">
                                <div class="card-icon" style="color: #d8b4fe;"><i class="fas fa-microchip"></i></div>
                                <div><h4 style="margin:0; color: #d8b4fe;">Credits <i class="fas fa-long-arrow-alt-right mx-1 small"></i> Dark Matter</h4><span class="text-muted small">Yield: 10k -> 0.70</span></div>
                            </div>
                            <div class="bm-card-body">
                                <form action="/black-market/synthesize/credits" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <div class="form-group mb-3">
                                        <label class="text-muted small mb-1">Amount to Synthesize</label>
                                        <input type="text" id="syn-credits-display" class="form-control bg-dark text-light border-secondary" placeholder="0" required>
                                        <input type="hidden" name="amount" id="syn-credits-hidden">
                                    </div>
                                    <div class="bg-dark p-3 rounded mb-3 border border-secondary" style="font-size: 0.9rem;">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Base Yield:</span>
                                            <span id="syn-credits-base">0.0000 DM</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Synthesis Loss (30%):</span>
                                            <span id="syn-credits-fee" class="text-danger">0.0000 DM</span>
                                        </div>
                                        <hr class="my-2 border-secondary">
                                        <div class="d-flex justify-content-between fw-bold">
                                            <span class="text-light">Final Output:</span>
                                            <span id="syn-credits-receive" style="color: #d8b4fe;">0.0000 DM</span>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn w-full py-2" style="background: #6b21a8; color: white;">INITIATE SYNTHESIS</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Crystals -> DM -->
                <div class="col-md-6">
                    <div class="bm-card border-neon" style="border-color: rgba(168, 85, 247, 0.4);">
                        <div class="bm-card-content">
                            <div class="bm-card-header" style="background: rgba(168, 85, 247, 0.05);">
                                <div class="card-icon" style="color: #d8b4fe;"><i class="fas fa-atom"></i></div>
                                <div><h4 style="margin:0; color: #d8b4fe;">Crystals <i class="fas fa-long-arrow-alt-right mx-1 small"></i> Dark Matter</h4><span class="text-muted small">Yield: 10 -> 0.70</span></div>
                            </div>
                            <div class="bm-card-body">
                                <form action="/black-market/synthesize/crystals" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <div class="form-group mb-3">
                                        <label class="text-muted small mb-1">Amount to Synthesize</label>
                                        <input type="text" id="syn-crystals-display" class="form-control bg-dark text-light border-secondary" placeholder="0" required>
                                        <input type="hidden" name="amount" id="syn-crystals-hidden">
                                    </div>
                                    <div class="bg-dark p-3 rounded mb-3 border border-secondary" style="font-size: 0.9rem;">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Base Yield:</span>
                                            <span id="syn-crystals-base">0.0000 DM</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Synthesis Loss (30%):</span>
                                            <span id="syn-crystals-fee" class="text-danger">0.0000 DM</span>
                                        </div>
                                        <hr class="my-2 border-secondary">
                                        <div class="d-flex justify-content-between fw-bold">
                                            <span class="text-light">Final Output:</span>
                                            <span id="syn-crystals-receive" style="color: #d8b4fe;">0.0000 DM</span>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn w-full py-2" style="background: #6b21a8; color: white;">INITIATE SYNTHESIS</button>
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
<script src="/js/converter.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Category Tabs Switcher (Inline Logic) ---
        const navBtns = document.querySelectorAll('.structure-nav-btn');
        const categories = document.querySelectorAll('.structure-category-container');

        navBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // 1. Toggle Button State
                navBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                // 2. Toggle Category Visibility
                const targetId = btn.getAttribute('data-tab-target');
                categories.forEach(cat => {
                    if (cat.id === targetId) {
                        cat.classList.add('active');
                    } else {
                        cat.classList.remove('active');
                    }
                });
            });
        });
    });
</script>
