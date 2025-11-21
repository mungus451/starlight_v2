<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\UserResource $resources */
/* @var \App\Models\Entities\UserStats $stats */
/* @var array $bankConfig */
?>

<style>
    :root {
        --bg: radial-gradient(circle at 10% 0%, #0c101e 0%, #050712 42%, #02030a 75%);
        --bg-panel: rgba(12, 14, 25, 0.65);
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

    /* --- Base Container --- */
    .bank-container {
        width: 100%;
        max-width: 1400px;
        margin-inline: auto;
        padding: 0; /* Let main.php handle horizontal padding */
        position: relative;
    }

    .bank-container h1 {
        text-align: center;
        margin-bottom: 0.25rem;
        font-size: clamp(2.1rem, 3vw, 2.6rem);
        letter-spacing: -0.03em;
        color: #fff;
        padding-top: 1.5rem;
    }

    /* --- Header Card --- */
    .resource-header-card {
        background: radial-gradient(circle at top, rgba(45, 209, 209, 0.12), rgba(11, 13, 24, 0.9));
        border: 1px solid rgba(45, 209, 209, 0.4);
        border-radius: var(--radius);
        padding: 1rem 1.5rem;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-around;
        gap: 1.5rem;
        box-shadow: var(--shadow);
        backdrop-filter: blur(6px);
        margin-bottom: 2rem;
    }
    .header-stat {
        text-align: center;
        flex-grow: 1;
    }
    .header-stat span {
        display: block;
        font-size: 0.9rem;
        color: var(--muted);
        margin-bottom: 0.25rem;
    }
    .header-stat strong {
        font-size: 1.5rem;
        color: #fff;
    }
    .header-stat strong.accent-gold {
        color: var(--accent-2);
    }
    .header-stat strong.accent-teal {
        color: var(--accent);
    }
    .header-stat strong.status-ok {
        color: var(--accent);
    }
    .header-stat strong.status-bad {
        color: var(--accent-red);
    }
    
    /* --- Timer Countdown --- */
    .timer-countdown {
        display: block;
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--muted);
        margin-top: 0.25rem;
    }
    
    /* --- Grid for Action Cards --- */
    .item-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr); /* 2 cards on desktop */
        gap: 1.5rem;
        max-width: 1000px; /* Constrain the forms for better readability */
        margin: 0 auto; /* Center the form grid */
    }
    .grid-col-span-2 {
        grid-column: 1 / -1; /* Makes an item span the full grid */
    }
    
    @media (max-width: 768px) {
        .item-grid {
            grid-template-columns: 1fr; /* 1 card on mobile */
        }
    }

    /* --- Action Card (from Armory/Structures) --- */
    .item-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.25rem 1.5rem;
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
        transition: transform 0.1s ease-out, border 0.1s ease-out;
    }
    .item-card:hover {
        transform: translateY(-2px);
        border: 1px solid rgba(45, 209, 209, 0.4);
    }
    .item-card h4 {
        color: #fff;
        margin: 0 0 1rem 0;
        font-size: 1.1rem;
        border-bottom: 1px solid var(--border);
        padding-bottom: 0.75rem;
    }
    
    .item-card .btn-submit {
        width: 100%;
        margin-top: 0;
        border: none;
        background: linear-gradient(120deg, var(--accent) 0%, #1f8ac5 100%);
        color: #fff;
        padding: 0.6rem 0.75rem;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: filter 0.1s ease-out, transform 0.1s ease-out;
    }
    .item-card .btn-submit:not([disabled]):hover {
        filter: brightness(1.02);
        transform: translateY(-1px);
    }
    .item-card .btn-submit:disabled {
        background: rgba(187, 76, 76, 0.15);
        border: 1px solid rgba(187, 76, 76, 0.3);
        color: rgba(255, 255, 255, 0.35);
        cursor: not-allowed;
    }

    /* --- New: Input + Max Button Group --- */
    .amount-input-group {
        display: flex;
        gap: 0.5rem;
    }
    .amount-input-group input {
        flex-grow: 1; /* Input takes up all available space */
        min-width: 50px; /* Prevents it from becoming too small */
    }
    .amount-input-group button {
        margin-top: 0;
        flex-shrink: 0; /* Prevents button from shrinking */
    }

    .amount-input-group .btn-submit {
        width: auto; /* Overrides the 100% width from .item-card .btn-submit */
    }
</style>

<div class="bank-container">
    <h1>Bank</h1>

    <div class="resource-header-card">
        <div class="header-stat">
            <span>Credits on Hand</span>
            <strong class="accent-gold" id="global-user-credits" data-credits="<?= $resources->credits ?>">
                <?= number_format($resources->credits) ?>
            </strong>
        </div>
        <div class="header-stat">
            <span>Banked Credits</span>
            <strong class="accent-teal" id="global-user-banked" data-banked="<?= $resources->banked_credits ?>">
                <?= number_format($resources->banked_credits) ?>
            </strong>
        </div>
        <div class="header-stat">
            <span>Deposits Available</span>
            <strong class="<?= $stats->deposit_charges > 0 ? 'status-ok' : 'status-bad' ?>">
                <?= $stats->deposit_charges ?> / <?= $bankConfig['deposit_max_charges'] ?>
            </strong>
            
            <span class="timer-countdown" id="deposit-timer-countdown"
                  data-last-deposit="<?= htmlspecialchars($stats->last_deposit_at ?? '') ?>"
                  data-current-charges="<?= (int)$stats->deposit_charges ?>"
                  data-max-charges="<?= (int)$bankConfig['deposit_max_charges'] ?>"
                  data-regen-hours="<?= (int)$bankConfig['deposit_charge_regen_hours'] ?>">
            </span>
        </div>
    </div>

    <div class="item-grid">
        <div class="item-card">
            <h4>Deposit</h4>
            <form action="/bank/deposit" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                <div class="form-group">
                    <label for="dep-amount-display">Amount to Deposit (Max 80%)</label>
                    <div class="amount-input-group">
                        <input type="text" id="dep-amount-display" class="formatted-amount" placeholder="e.g., 1,000,000" required>
                        <input type="hidden" name="amount" id="dep-amount-hidden" value="0">
                        
                        <button type="button" class="btn-submit" id="btn-max-deposit" 
                                data-limit="<?= $bankConfig['deposit_percent_limit'] ?? 0.8 ?>">
                            Max
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-submit" <?= $stats->deposit_charges <= 0 ? 'disabled' : '' ?>>
                    <?= $stats->deposit_charges <= 0 ? 'No Charges Left' : 'Deposit' ?>
                </button>
            </form>
        </div>

        <div class="item-card">
            <h4>Withdraw</h4>
            <form action="/bank/withdraw" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                <div class="form-group">
                    <label for="wit-amount-display">Amount to Withdraw</label>
                    <div class="amount-input-group">
                        <input type="text" id="wit-amount-display" class="formatted-amount" placeholder="e.g., 1,000,000" required>
                        <input type="hidden" name="amount" id="wit-amount-hidden" value="0">
                        <button type="button" class="btn-submit" id="btn-max-withdraw">Max</button>
                    </div>
                </div>
                <button type="submit" class="btn-submit">Withdraw</button>
            </form>
        </div>

        <div class="item-card grid-col-span-2">
            <h4>Transfer Credits</h4>
            <form action="/bank/transfer" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                <div class="form-group">
                    <label for="rec-name">Recipient Character Name</label>
                    <input type="text" name="recipient_name" id="rec-name" placeholder="e.g., EmperorZurg" required>
                </div>
                <div class="form-group">
                    <label for="tran-amount-display">Amount to Transfer (from credits on hand)</label>
                    <input type="text" id="tran-amount-display" class="formatted-amount" placeholder="e.g., 1,000,000" required>
                    <input type="hidden" name="amount" id="tran-amount-hidden" value="0">
                </div>
                <button type="submit" class="btn-submit">Transfer</button>
            </form>
        </div>
    </div>
</div>

<script src="/js/bank.js"></script>