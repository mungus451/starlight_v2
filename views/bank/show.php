<style>
    .bank-container {
        width: 100%;
        max-width: 800px;
        text-align: left;
    }
    .bank-container h1 {
        text-align: center;
    }
    .data-card {
        background: #2a2a4a;
        border: 1px solid #3a3a5a;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .data-card h3 {
        color: #f9c74f;
        margin-top: 0;
        border-bottom: 1px solid #3a3a5a;
        padding-bottom: 0.5rem;
    }
    .data-card ul {
        list-style: none;
        padding-left: 0;
        margin: 0;
    }
    .data-card li {
        font-size: 1.1rem;
        color: #e0e0e0;
        padding: 0.25rem 0;
        display: flex;
        justify-content: space-between;
    }
    .data-card li span:first-child {
        font-weight: bold;
        color: #c0c0e0;
    }
    
    .bank-forms {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    /* On larger screens, stack forms in two columns */
    @media (min-width: 768px) {
        .bank-forms {
            grid-template-columns: 1fr 1fr;
        }
        /* Make the transfer form span both columns */
        .data-card-transfer {
            grid-column: 1 / -1;
        }
    }
</style>

<div class="bank-container">
    <h1>Bank</h1>

    <div class="data-card">
        <h3>Finances</h3>
        <ul>
            <li><span>Credits on Hand:</span> <span><?= number_format($resources->credits) ?></span></li>
            <li><span>Banked Credits:</span> <span><?= number_format($resources->banked_credits) ?></span></li>
        </ul>
    </div>

    <div class="bank-forms">
        <div class="data-card">
            <h3>Deposit</h3>
            <form action="/bank/deposit" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                <div class="form-group">
                    <label for="dep-amount">Amount to Deposit</label>
                    <input type="number" name="amount" id="dep-amount" min="1" placeholder="e.g., 1000000" required>
                </div>
                <button type="submit" class="btn-submit">Deposit</button>
            </form>
        </div>

        <div class="data-card">
            <h3>Withdraw</h3>
            <form action="/bank/withdraw" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                <div class="form-group">
                    <label for="wit-amount">Amount to Withdraw</label>
                    <input type="number" name="amount" id="wit-amount" min="1" placeholder="e.g., 1000000" required>
                </div>
                <button type="submit" class="btn-submit">Withdraw</button>
            </form>
        </div>

        <div class="data-card data-card-transfer">
            <h3>Transfer Credits</h3>
            <form action="/bank/transfer" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                <div class="form-group">
                    <label for="rec-name">Recipient Character Name</label>
                    <input type="text" name="recipient_name" id="rec-name" placeholder="e.g., EmperorZurg" required>
                </div>
                <div class="form-group">
                    <label for="tran-amount">Amount to Transfer (from credits on hand)</label>
                    <input type="number" name="amount" id="tran-amount" min="1" placeholder="e.g., 1000000" required>
                </div>
                <button type="submit" class="btn-submit">Transfer</button>
            </form>
        </div>
    </div>
</div>