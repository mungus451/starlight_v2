<?php
/**
 * @var \App\Models\Entities\UserResource $userResources
 * @var \App\Models\Entities\HouseFinance $houseFinances
 * @var float $conversionRate
 * @var float $feePercentage
 * @var string $csrf_token
 */
?>

<style>
    :root {
        --bg: radial-gradient(circle at 10% 0%, #0c101e 0%, #050712 42%, #02030a 75%);
        --card: radial-gradient(circle at 30% -10%, rgba(45, 209, 209, 0.07), rgba(13, 15, 27, 0.6));
        --border: rgba(255, 255, 255, 0.03);
        --accent: #2dd1d1; /* Main Accent (Teal) */
        --accent-soft: rgba(45, 209, 209, 0.12);
        --accent-2: #f9c74f; /* Secondary Accent (Gold) */
        --accent-red: #e53e3e;
        --text: #eff1ff;
        --muted: #a8afd4;
        --radius: 18px;
        --shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
    }
    .converter-dashboard {
        width: 100%;
        max-width: 1200px;
        margin-inline: auto;
        padding: 1.5rem 1.5rem 3.5rem;
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }
    .converter-dashboard h1 {
        text-align: center;
        margin-bottom: 0.5rem;
        font-size: clamp(2.2rem, 4vw, 3rem);
        letter-spacing: -0.05em;
        color: #fff;
        text-shadow: 0 0 15px rgba(249, 199, 79, 0.4);
    }
    .converter-dashboard .subtitle {
        text-align: center;
        color: var(--muted);
        font-size: 0.95rem;
        margin-top: -0.5rem;
        margin-bottom: 1.5rem;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-bottom: 1rem;
    }
    .balance-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.5rem;
        box-shadow: var(--shadow);
    }
    .balance-card h3 {
        margin: 0 0 1rem 0;
        font-size: 1.2rem;
        color: var(--accent-2);
        border-bottom: 1px solid rgba(249, 199, 79, 0.2);
        padding-bottom: 0.5rem;
    }
    .balance-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 1.1rem;
        color: var(--text);
        margin-bottom: 0.5rem;
    }
    .balance-item .label {
        color: var(--muted);
    }
    .balance-item .value {
        font-weight: 700;
    }

    .converter-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
    .converter-card {
        background: radial-gradient(circle at top, rgba(45, 209, 209, 0.1), rgba(11, 13, 24, 0.9));
        border: 1px solid rgba(45, 209, 209, 0.2);
        border-radius: var(--radius);
        padding: 2rem;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        box-shadow: var(--shadow);
    }
    .converter-card h2 {
        margin: 0;
        font-size: 1.5rem;
        text-align: center;
    }
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .form-group label {
        font-weight: 600;
        color: var(--muted);
    }
    .form-group .input-field {
        width: 100%;
        padding: 0.75rem 1rem;
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid var(--border);
        border-radius: 10px;
        color: var(--text);
        font-size: 1.1rem;
        transition: border-color 0.2s;
    }
    .form-group .input-field:focus {
        outline: none;
        border-color: var(--accent);
    }
    .conversion-summary {
        background: rgba(0, 0, 0, 0.2);
        border-radius: 8px;
        padding: 1rem;
        font-size: 0.9rem;
        color: var(--muted);
    }
    .conversion-summary div {
        display: flex;
        justify-content: space-between;
    }
    .conversion-summary .final-amount {
        color: var(--accent);
        font-weight: 700;
    }

    .btn-convert {
        width: 100%;
        padding: 0.8rem 1.25rem;
        border: none;
        border-radius: 10px;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        background: linear-gradient(135deg, var(--accent) 0%, #1a9c9c 100%);
        color: #02030a;
        box-shadow: 0 5px 20px rgba(45, 209, 209, 0.3);
    }
    .btn-convert:hover {
        filter: brightness(1.1);
        transform: translateY(-2px);
    }

    @media (max-width: 900px) {
        .converter-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="converter-dashboard">
    <h1>Naquadah Crystal Exchange</h1>
    <p class="subtitle">Exchange Credits for Naquadah Crystals and vice-versa. A <?= $feePercentage * 100 ?>% fee applies to all conversions.</p>

    <div class="info-grid">
        <div class="balance-card">
            <h3>Your Wallet</h3>
            <div class="balance-item">
                <span class="label">Credits:</span>
                <span class="value"><?= number_format($userResources->credits, 2) ?></span>
            </div>
            <div class="balance-item">
                <span class="label">Naquadah Crystals:</span>
                <span class="value"><?= number_format($userResources->naquadah_crystals, 4) ?> 💎</span>
            </div>
        </div>
        <div class="balance-card">
            <h3>The House's Cut</h3>
            <div class="balance-item">
                <span class="label">Credits Taxed:</span>
                <span class="value"><?= number_format($houseFinances->credits_taxed, 2) ?></span>
            </div>
            <div class="balance-item">
                <span class="label">Crystals Taxed:</span>
                <span class="value"><?= number_format($houseFinances->crystals_taxed, 4) ?> 💎</span>
            </div>
        </div>
    </div>

    <div class="converter-grid">
        <!-- Convert Credits to Crystals -->
        <div class="converter-card">
            <h2>Credits -> Naquadah Crystals</h2>
            <form action="/black-market/convert" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="conversion_direction" value="credits_to_crystals">

                <div class="form-group">
                    <label for="credits-amount-display">Amount of Credits to Convert</label>
                    <input type="text" inputmode="numeric" class="input-field" id="credits-amount-display" placeholder="e.g., 10,000" required>
                    <input type="hidden" name="amount" id="credits-amount-hidden">
                </div>

                <div class="conversion-summary">
                    <div><span>Fee (<?= $feePercentage * 100 ?>%):</span> <span id="c2cry-fee">0.00 Credits</span></div>
                    <div><span>Amount after fee:</span> <span id="c2cry-after-fee">0.00 Credits</span></div>
                    <hr style="border-color: rgba(255,255,255,0.1); margin: 0.5rem 0;">
                    <div class="final-amount"><span>You will receive:</span> <span id="c2cry-receive">0.0000 💎</span></div>
                </div>

                <button type="submit" class="btn-convert">Convert to Crystals</button>
            </form>
        </div>

        <!-- Convert Crystals to Credits -->
        <div class="converter-card">
            <h2>Naquadah Crystals -> Credits</h2>
            <form action="/black-market/convert" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="conversion_direction" value="crystals_to_credits">

                <div class="form-group">
                    <label for="crystals-amount-display">Amount of Crystals to Convert</label>
                    <input type="text" inputmode="numeric" class="input-field" id="crystals-amount-display" placeholder="e.g., 100.0" required>
                    <input type="hidden" name="amount" id="crystals-amount-hidden">
                </div>

                <div class="conversion-summary">
                    <div><span>Fee (<?= $feePercentage * 100 ?>%):</span> <span id="cry2c-fee">0.0000 💎</span></div>
                    <div><span>Amount after fee:</span> <span id="cry2c-after-fee">0.0000 💎</span></div>
                    <hr style="border-color: rgba(255,255,255,0.1); margin: 0.5rem 0;">
                    <div class="final-amount"><span>You will receive:</span> <span id="cry2c-receive">0.00 Credits</span></div>
                </div>

                <button type="submit" class="btn-convert">Convert to Credits</button>
            </form>
        </div>
    </div>
</div>

<!-- This section will be added in the next step -->
<script src="/js/converter.js"></script>
