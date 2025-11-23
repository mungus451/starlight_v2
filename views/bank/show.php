<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\UserResource $resources */
/* @var \App\Models\Entities\UserStats $stats */
/* @var array $bankConfig */
?>

<div class="container-full">
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