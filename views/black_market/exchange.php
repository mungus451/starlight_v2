<?php
/**
 * @var \App\Models\Entities\UserResource $userResources
 * @var \App\Models\Entities\HouseFinance $houseFinances
 * @var float $conversionRate
 * @var float $feePercentage
 * @var string $csrf_token
 */
?>

<div class="container-full">
    <h1>The Void Syndicate</h1>

    <div class="tabs-nav" style="justify-content: center; margin-bottom: 2rem;">
        <a href="/black-market/converter" class="tab-link active">Crystal Exchange</a>
        <a href="/black-market/actions" class="tab-link">Undermarket</a>
    </div>

    <p style="text-align: center; color: var(--muted); margin-top: -1rem; margin-bottom: 2rem;">
        Exchange Credits for Naquadah Crystals and vice-versa. A <?= $feePercentage * 100 ?>% fee applies to all conversions.
    </p>

    <!-- Info Grid -->
    <div class="item-grid" style="margin-bottom: 2rem;">
        <div class="balance-card">
            <h3 style="margin: 0 0 1rem 0; font-size: 1.2rem; color: var(--accent-2); border-bottom: 1px solid rgba(249, 199, 79, 0.2); padding-bottom: 0.5rem;">Your Wallet</h3>
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: var(--muted);">Credits:</span>
                <span style="font-weight: 700;"><?= number_format($userResources->credits, 2) ?></span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--muted);">Naquadah Crystals:</span>
                <span style="font-weight: 700;"><?= number_format($userResources->naquadah_crystals, 4) ?> 💎</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed rgba(255,255,255,0.1);">
                <span style="color: var(--muted);">Dark Matter:</span>
                <span style="font-weight: 700; color: #d8b4fe;"><?= number_format($userResources->dark_matter, 4) ?> DM</span>
            </div>
        </div>
        <div class="balance-card">
            <h3 style="margin: 0 0 1rem 0; font-size: 1.2rem; color: var(--accent-2); border-bottom: 1px solid rgba(249, 199, 79, 0.2); padding-bottom: 0.5rem;">The House's Cut</h3>
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: var(--muted);">Credits Taxed:</span>
                <span style="font-weight: 700;"><?= number_format($houseFinances->credits_taxed, 2) ?></span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--muted);">Crystals Taxed:</span>
                <span style="font-weight: 700;"><?= number_format($houseFinances->crystals_taxed, 4) ?> 💎</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed rgba(255,255,255,0.1);">
                <span style="color: var(--muted);">Dark Matter Taxed:</span>
                <span style="font-weight: 700; color: #d8b4fe;"><?= number_format($houseFinances->dark_matter_taxed ?? 0, 4) ?> DM</span>
            </div>
        </div>
    </div>

    <!-- Converter Forms -->
    <div class="item-grid">
        <!-- Convert Credits to Crystals -->
        <div class="item-card">
            <h4>Credits -> Naquadah Crystals</h4>
            <form action="/black-market/convert" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="conversion_direction" value="credits_to_crystals">

                <div class="form-group">
                    <label for="credits-amount-display">Amount of Credits to Convert</label>
                    <input type="text" inputmode="numeric" id="credits-amount-display" placeholder="e.g., 10,000" required>
                    <input type="hidden" name="amount" id="credits-amount-hidden">
                </div>

                <div class="conversion-summary">
                    <div style="display: flex; justify-content: space-between;"><span>Fee (<?= $feePercentage * 100 ?>%):</span> <span id="c2cry-fee">0.00 Credits</span></div>
                    <div style="display: flex; justify-content: space-between;"><span>Amount after fee:</span> <span id="c2cry-after-fee">0.00 Credits</span></div>
                    <hr style="border-color: rgba(255,255,255,0.1); margin: 0.5rem 0;">
                    <div style="display: flex; justify-content: space-between; color: var(--accent); font-weight: 700;"><span>You will receive:</span> <span id="c2cry-receive">0.0000 💎</span></div>
                </div>

                <button type="submit" class="btn-submit btn-accent">Convert to Crystals</button>
            </form>
        </div>

        <!-- Convert Crystals to Credits -->
        <div class="item-card">
            <h4>Naquadah Crystals -> Credits</h4>
            <form action="/black-market/convert" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="conversion_direction" value="crystals_to_credits">

                <div class="form-group">
                    <label for="crystals-amount-display">Amount of Crystals to Convert</label>
                    <input type="text" inputmode="numeric" id="crystals-amount-display" placeholder="e.g., 100.0" required>
                    <input type="hidden" name="amount" id="crystals-amount-hidden">
                </div>

                <div class="conversion-summary">
                    <div style="display: flex; justify-content: space-between;"><span>Fee (<?= $feePercentage * 100 ?>%):</span> <span id="cry2c-fee">0.0000 💎</span></div>
                    <div style="display: flex; justify-content: space-between;"><span>Amount after fee:</span> <span id="cry2c-after-fee">0.0000 💎</span></div>
                    <hr style="border-color: rgba(255,255,255,0.1); margin: 0.5rem 0;">
                    <div style="display: flex; justify-content: space-between; color: var(--accent); font-weight: 700;"><span>You will receive:</span> <span id="cry2c-receive">0.00 Credits</span></div>
                </div>

                <button type="submit" class="btn-submit btn-accent">Convert to Credits</button>
            </form>
        </div>
    </div>

    <!-- Matter Synthesis -->
    <h2 style="text-align: center; color: var(--accent); margin: 3rem 0 1rem 0; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 2rem;">Matter Synthesis</h2>
    <p style="text-align: center; color: var(--muted); margin-bottom: 2rem;">
        Fuse raw materials into Dark Matter. <span class="text-danger">This process is irreversible.</span>
    </p>

    <div class="item-grid">
        <!-- Credits -> DM -->
        <div class="item-card" style="border-color: #a855f7; box-shadow: 0 0 15px rgba(168, 85, 247, 0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h4 style="margin: 0; color: #d8b4fe;">Credits <i class="fas fa-arrow-right"></i> Dark Matter</h4>
                <span class="badge" style="background: rgba(168, 85, 247, 0.2); color: #d8b4fe;">10k : 0.7</span>
            </div>
            
            <form action="/black-market/synthesize/credits" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                
                <div class="form-group">
                    <label>Amount of Credits to Convert</label>
                    <input type="text" id="syn-credits-display" placeholder="e.g., 10,000" style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid #a855f7; padding: 0.5rem; color: white;" required>
                    <input type="hidden" name="amount" id="syn-credits-hidden">
                </div>

                <div class="conversion-summary" style="margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between;"><span>Potential DM:</span> <span id="syn-credits-base">0.0000 DM</span></div>
                    <div style="display: flex; justify-content: space-between;"><span>Fee (30%):</span> <span id="syn-credits-fee">0.0000 DM</span></div>
                    <hr style="border-color: rgba(255,255,255,0.1); margin: 0.5rem 0;">
                    <div style="display: flex; justify-content: space-between; color: var(--accent); font-weight: 700;"><span>You will receive:</span> <span id="syn-credits-receive">0.0000 DM</span></div>
                </div>
                
                <button type="submit" class="btn-submit" style="background: #6b21a8; width: 100%;">Synthesize</button>
            </form>
        </div>

        <!-- Crystals -> DM -->
        <div class="item-card" style="border-color: #a855f7; box-shadow: 0 0 15px rgba(168, 85, 247, 0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h4 style="margin: 0; color: #d8b4fe;">Crystals <i class="fas fa-arrow-right"></i> Dark Matter</h4>
                <span class="badge" style="background: rgba(168, 85, 247, 0.2); color: #d8b4fe;">10 : 0.7</span>
            </div>

            <form action="/black-market/synthesize/crystals" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                
                <div class="form-group">
                    <label>Amount of Crystals to Convert</label>
                    <input type="text" id="syn-crystals-display" placeholder="e.g., 10" style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid #a855f7; padding: 0.5rem; color: white;" required>
                    <input type="hidden" name="amount" id="syn-crystals-hidden">
                </div>

                <div class="conversion-summary" style="margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between;"><span>Potential DM:</span> <span id="syn-crystals-base">0.0000 DM</span></div>
                    <div style="display: flex; justify-content: space-between;"><span>Fee (30%):</span> <span id="syn-crystals-fee">0.0000 DM</span></div>
                    <hr style="border-color: rgba(255,255,255,0.1); margin: 0.5rem 0;">
                    <div style="display: flex; justify-content: space-between; color: var(--accent); font-weight: 700;"><span>You will receive:</span> <span id="syn-crystals-receive">0.0000 DM</span></div>
                </div>

                <button type="submit" class="btn-submit" style="background: #6b21a8; width: 100%;">Synthesize</button>
            </form>
        </div>
    </div>
</div>

<script src="/js/converter.js"></script>