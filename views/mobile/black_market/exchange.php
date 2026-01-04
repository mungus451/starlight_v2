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
            <div style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-accent-purple); text-shadow: 0 0 15px rgba(189, 0, 255, 0.4);">
                1 💎 = <?= number_format($conversionRate) ?> ₡
            </div>
            <div style="margin-top: 0.5rem; font-size: 0.9rem; color: var(--muted);">
                Transaction Fee: <strong style="color: var(--mobile-text-primary);"><?= $feeLabel ?></strong>
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
    
    <div style="margin-top: 2rem; text-align: center;">
        <a href="/black-market/actions" class="btn btn-outline" style="width: 100%;">
            <i class="fas fa-mask"></i> Go to Undermarket
        </a>
    </div>
</div>
