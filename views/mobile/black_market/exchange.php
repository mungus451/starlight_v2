<?php
// --- Mobile Black Market Exchange View ---
/* @var float $conversionRate */
/* @var float $feePercentage */
/* @var \App\Models\Entities\UserResource $userResources */
/* @var string $csrf_token */

$feeLabel = ($feePercentage * 100) . '%';
$maxBuy = floor($userResources->credits / ($conversionRate * (1 + $feePercentage)));
$maxSell = floor($userResources->naquadah_crystals);
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">The Exchange</h1>
        <p style="color: var(--mobile-text-secondary); font-size: 0.9rem; margin: 0.5rem 1rem 0 1rem; text-align: center;">Convert between Imperial Credits and Naquadah Crystals.</p>
    </div>

    <!-- Status Card -->
    <div class="mobile-card" style="text-align: center; border-color: var(--mobile-accent-purple);">
        <div class="mobile-card-header" style="justify-content: center;">
            <h3 style="color: var(--mobile-accent-purple);"><i class="fas fa-sync-alt"></i> Market Rate</h3>
        </div>
        <div class="mobile-card-content" style="display: block;">
            <div style="font-family: 'Orbitron', sans-serif; font-size: 1.8rem; color: var(--mobile-accent-purple); text-shadow: 0 0 15px rgba(189, 0, 255, 0.4);">
                1 💎 = <?= number_format($conversionRate) ?> ₡
            </div>
            <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--muted); border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.75rem;">
                Transaction Fee: <strong style="color: var(--mobile-text-primary);"><?= $feeLabel ?></strong>
            </div>

            <div style="margin-top: 0.75rem;">
                <div style="font-size: 0.75rem; color: var(--muted); margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 1px;">Synthesis Rates</div>
                <div style="display: flex; justify-content: center; gap: 1rem; font-family: 'Orbitron', sans-serif; font-size: 0.9rem; color: #d8b4fe;">
                    <span>10k ₡ : 0.7 DM</span>
                    <span style="color: rgba(255,255,255,0.2);">|</span>
                    <span>10 💎 : 0.7 DM</span>
                </div>
            </div>
        </div>
    </div>

    <!-- WRAPPER FOR TABS LOGIC -->
    <div class="nested-tabs-container">
        
        <!-- Tab Navigation -->
        <div class="mobile-tabs nested-tabs">
            <a href="#" class="tab-link active" data-tab-target="tab-buy">Buy Crystals</a>
            <a href="#" class="tab-link" data-tab-target="tab-sell">Sell Crystals</a>
        </div>

        <!-- Tab Content -->
        <div id="tab-content">
            
            <!-- Buy Crystals Tab -->
            <div id="tab-buy" class="nested-tab-content active">
                <div class="mobile-card">
                    <div class="mobile-card-content" style="display: block;">
                        <p class="text-center text-muted">Available Credits: <strong style="color: var(--mobile-text-primary);"><?= number_format($userResources->credits) ?></strong></p>
                        
                        <form action="/black-market/convert" method="POST" style="margin-top: 1rem;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <input type="hidden" name="conversion_direction" value="credits_to_crystals">
                            
                            <div class="form-group">
                                <label for="buy_amount">Amount (Credits to Spend)</label>
                                <input type="number" name="amount" id="buy_amount" class="mobile-input" placeholder="0" min="1" max="<?= $userResources->credits ?>" style="width: 100%; margin-bottom: 0.5rem;">
                                <button type="button" class="btn btn-sm" style="width: 100%;" onclick="document.getElementById('buy_amount').value = '<?= floor($userResources->credits) ?>'">MAX (<?= number_format($userResources->credits) ?>)</button>
                            </div>

                            <button type="submit" class="btn btn-accent" style="width: 100%; margin-top: 1rem;">
                                <i class="fas fa-shopping-cart"></i> Buy Crystals
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sell Crystals Tab -->
            <div id="tab-sell" class="nested-tab-content">
                <div class="mobile-card">
                    <div class="mobile-card-content" style="display: block;">
                        <p class="text-center text-muted">Available Crystals: <strong style="color: var(--mobile-text-primary);"><?= number_format($userResources->naquadah_crystals) ?></strong></p>

                        <form action="/black-market/convert" method="POST" style="margin-top: 1rem;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <input type="hidden" name="conversion_direction" value="crystals_to_credits">
                            
                            <div class="form-group">
                                <label for="sell_amount">Amount (Crystals to Sell)</label>
                                <input type="number" name="amount" id="sell_amount" class="mobile-input" placeholder="0" min="1" max="<?= $userResources->naquadah_crystals ?>" style="width: 100%; margin-bottom: 0.5rem;">
                                <button type="button" class="btn btn-sm" style="width: 100%;" onclick="document.getElementById('sell_amount').value = '<?= floor($userResources->naquadah_crystals) ?>'">MAX (<?= number_format($userResources->naquadah_crystals) ?>)</button>
                            </div>

                            <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">
                                <i class="fas fa-hand-holding-usd"></i> Sell Crystals
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div> <!-- End nested-tabs-container -->

    <!-- MATTER SYNTHESIS -->
    <div style="margin: 2rem 0; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.5rem;">
        <h3 style="text-align: center; color: var(--mobile-accent-purple); font-family: 'Orbitron', sans-serif; margin-bottom: 0.5rem;">Matter Synthesis</h3>
        <p class="text-center text-muted" style="font-size: 0.85rem; margin-bottom: 1.5rem;">Fuse raw materials into Dark Matter.</p>

        <!-- Credits -> DM -->
        <div class="mobile-card" style="border-color: #a855f7; box-shadow: 0 0 10px rgba(168, 85, 247, 0.15);">
            <div class="mobile-card-header" style="background: rgba(168, 85, 247, 0.1);">
                <h4 style="margin: 0; color: #d8b4fe; font-size: 1rem;">Credits <i class="fas fa-arrow-right"></i> Dark Matter</h4>
            </div>
            <div class="mobile-card-content" style="display: block;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 0.85rem;">
                    <span class="text-muted">Rate:</span>
                    <strong style="color: #d8b4fe;">10,000 : 0.7</strong>
                </div>

                <form action="/black-market/synthesize/credits" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    
                    <div class="form-group">
                        <label>Credits to Convert</label>
                        <input type="text" id="mob-syn-credits-display" class="mobile-input" placeholder="e.g., 10,000" style="border-color: #a855f7;" required>
                        <input type="hidden" name="amount" id="mob-syn-credits-hidden">
                    </div>

                    <div class="conversion-summary" style="margin-top: 1rem; font-size: 0.8rem; background: rgba(0,0,0,0.2); padding: 0.75rem; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;"><span>Base DM:</span> <span id="mob-syn-credits-base">0.0000 DM</span></div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;"><span>Fee (30%):</span> <span id="mob-syn-credits-fee">0.0000 DM</span></div>
                        <hr style="border-color: rgba(255,255,255,0.05); margin: 0.5rem 0;">
                        <div style="display: flex; justify-content: space-between; color: #d8b4fe; font-weight: 700;"><span>You will receive:</span> <span id="mob-syn-credits-receive">0.0000 DM</span></div>
                    </div>
                    
                    <button type="submit" class="btn btn-accent" style="width: 100%; margin-top: 1rem; background: #7e22ce; border-color: #7e22ce;">Synthesize</button>
                </form>
            </div>
        </div>

        <!-- Crystals -> DM -->
        <div class="mobile-card" style="border-color: #a855f7; box-shadow: 0 0 10px rgba(168, 85, 247, 0.15);">
            <div class="mobile-card-header" style="background: rgba(168, 85, 247, 0.1);">
                <h4 style="margin: 0; color: #d8b4fe; font-size: 1rem;">Crystals <i class="fas fa-arrow-right"></i> Dark Matter</h4>
            </div>
            <div class="mobile-card-content" style="display: block;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 0.85rem;">
                    <span class="text-muted">Rate:</span>
                    <strong style="color: #d8b4fe;">10 : 0.7</strong>
                </div>

                <form action="/black-market/synthesize/crystals" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    
                    <div class="form-group">
                        <label>Crystals to Convert</label>
                        <input type="text" id="mob-syn-crystals-display" class="mobile-input" placeholder="e.g., 10" style="border-color: #a855f7;" required>
                        <input type="hidden" name="amount" id="mob-syn-crystals-hidden">
                    </div>

                    <div class="conversion-summary" style="margin-top: 1rem; font-size: 0.8rem; background: rgba(0,0,0,0.2); padding: 0.75rem; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;"><span>Base DM:</span> <span id="mob-syn-crystals-base">0.0000 DM</span></div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;"><span>Fee (30%):</span> <span id="mob-syn-crystals-fee">0.0000 DM</span></div>
                        <hr style="border-color: rgba(255,255,255,0.05); margin: 0.5rem 0;">
                        <div style="display: flex; justify-content: space-between; color: #d8b4fe; font-weight: 700;"><span>You will receive:</span> <span id="mob-syn-crystals-receive">0.0000 DM</span></div>
                    </div>
                    
                    <button type="submit" class="btn btn-accent" style="width: 100%; margin-top: 1rem; background: #7e22ce; border-color: #7e22ce;">Synthesize</button>
                </form>
            </div>
        </div>
    </div>
    
    <div style="margin-top: 2rem; text-align: center;">
        <a href="/black-market/actions" class="btn btn-outline" style="width: 100%;">
            <i class="fas fa-mask"></i> Go to Undermarket
        </a>
    </div>
</div>

<script src="/js/converter.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Init Mobile Tabs
        const tabLinks = document.querySelectorAll('.nested-tabs .tab-link');
        const tabContents = document.querySelectorAll('.nested-tab-content');

        tabLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = link.getAttribute('data-tab-target');

                tabLinks.forEach(l => l.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));

                link.classList.add('active');
                document.getElementById(targetId).classList.add('active');
            });
        });
    });
</script>
