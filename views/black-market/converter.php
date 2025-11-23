<?php
/**
 * @var \App\Models\Entities\UserResource $userResources
 * @var \App\Models\Entities\HouseFinance $houseFinances
 * @var float $conversionRate
 * @var float $feePercentage
 * @var string $csrf_token
 */
?>

<h1>Naquadah Crystal Exchange</h1>
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

<script src="/js/converter.js"></script>