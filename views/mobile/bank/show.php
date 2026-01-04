<?php
// --- Mobile Bank View ---
/* @var \App\Models\Entities\UserResource $resources */
/* @var \App\Models\Entities\UserStats $stats */
/* @var array $bankConfig */
/* @var string $csrf_token */

$interestRate = ($bankConfig['interest_rate'] ?? 0.0) * 100;
$depositLimit = floor($resources->credits * ($bankConfig['deposit_percent_limit'] ?? 0.8));
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">Galactic Reserve</h1>
        <p style="color: var(--mobile-text-secondary); font-size: 0.9rem; margin: 0.5rem 1rem 0 1rem; text-align: center;">Secure your credits and earn compound interest.</p>
    </div>

    <!-- Status Card -->
    <div class="mobile-card" style="text-align: center; border-color: var(--mobile-accent-blue);">
        <div class="mobile-card-header" style="justify-content: center;">
            <h3 style="color: var(--mobile-accent-blue);"><i class="fas fa-university"></i> Bank Balance</h3>
        </div>
        <div class="mobile-card-content" style="display: block;">
            <div style="font-family: 'Orbitron', sans-serif; font-size: 2.5rem; color: var(--mobile-accent-blue); text-shadow: 0 0 15px rgba(0, 229, 255, 0.4);">
                <?= number_format($resources->banked_credits) ?>
            </div>
            <div style="display: flex; justify-content: space-around; margin-top: 1rem; font-size: 0.9rem;">
                <div>
                    <span class="text-muted">Interest</span><br>
                    <strong style="color: var(--mobile-accent-green);">+<?= $interestRate ?>%</strong> / turn
                </div>
                <div>
                    <span class="text-muted">Charges</span><br>
                    <strong style="<?= $stats->deposit_charges > 0 ? 'color: var(--mobile-accent-green);' : 'color: var(--mobile-accent-green);' ?>">
                        <?= $stats->deposit_charges ?> / <?= $bankConfig['deposit_max_charges'] ?>
                    </strong>
                </div>
            </div>
        </div>
    </div>

    <!-- WRAPPER FOR TABS LOGIC -->
    <div class="nested-tabs-container">
        
        <!-- Tab Navigation -->
        <div class="mobile-tabs nested-tabs">
            <a href="#" class="tab-link active" data-tab-target="tab-withdraw">Withdraw</a>
            <a href="#" class="tab-link" data-tab-target="tab-deposit">Deposit</a>
            <a href="#" class="tab-link" data-tab-target="tab-transfer">Transfer</a>
        </div>

        <!-- Tab Content -->
        <div id="tab-content">
            
            <!-- Withdraw Tab -->
            <div id="tab-withdraw" class="nested-tab-content active">
                <div class="mobile-card">
                    <div class="mobile-card-content" style="display: block;">
                        <p class="text-center text-muted">Available in Bank: <strong style="color: var(--mobile-text-primary);"><?= number_format($resources->banked_credits) ?></strong></p>
                        
                        <form action="/bank/withdraw" method="POST" style="margin-top: 1rem;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            
                            <div class="form-group">
                                <label for="withdraw_amount">Amount</label>
                                <input type="number" name="amount" id="withdraw_amount" class="mobile-input" placeholder="0" min="1" max="<?= $resources->banked_credits ?>" style="width: 100%; margin-bottom: 0.5rem;">
                                <button type="button" class="btn btn-sm" style="width: 100%;" onclick="document.getElementById('withdraw_amount').value = '<?= $resources->banked_credits ?>'">MAX (<?= number_format($resources->banked_credits) ?>)</button>
                            </div>

                            <button type="submit" class="btn btn-accent" style="width: 100%; margin-top: 1rem;">
                                <i class="fas fa-money-bill-wave"></i> Withdraw
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Deposit Tab -->
            <div id="tab-deposit" class="nested-tab-content">
                <div class="mobile-card">
                    <div class="mobile-card-content" style="display: block;">
                        <p class="text-center text-muted">Available on Hand: <strong style="color: var(--mobile-text-primary);"><?= number_format($resources->credits) ?></strong></p>
                        <p class="text-center text-muted" style="font-size: 0.85rem;">Deposit Limit (80%): <strong style="color: var(--mobile-text-secondary);"><?= number_format($depositLimit) ?></strong></p>

                        <form action="/bank/deposit" method="POST" style="margin-top: 1rem;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            
                            <div class="form-group">
                                <label for="deposit_amount">Amount</label>
                                <input type="number" name="amount" id="deposit_amount" class="mobile-input" placeholder="0" min="1" max="<?= $depositLimit ?>" style="width: 100%; margin-bottom: 0.5rem;">
                                <button type="button" class="btn btn-sm" style="width: 100%;" onclick="document.getElementById('deposit_amount').value = '<?= $depositLimit ?>'">MAX (<?= number_format($depositLimit) ?>)</button>
                            </div>

                            <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;" <?= $stats->deposit_charges > 0 ? '' : 'disabled' ?>>
                                <i class="fas fa-piggy-bank"></i> Deposit
                            </button>
                            <?php if ($stats->deposit_charges <= 0): ?>
                                <p class="text-center text-danger" style="margin-top: 0.5rem; font-size: 0.8rem;">No deposit charges remaining.</p>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Transfer Tab -->
            <div id="tab-transfer" class="nested-tab-content">
                <div class="mobile-card">
                    <div class="mobile-card-content" style="display: block;">
                        <p class="text-center text-muted">Available on Hand: <strong style="color: var(--mobile-text-primary);"><?= number_format($resources->credits) ?></strong></p>
                        
                        <form action="/bank/transfer" method="POST" style="margin-top: 1rem;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            
                            <div class="form-group">
                                <label for="recipient_name">Recipient Name</label>
                                <input type="text" name="recipient_name" id="recipient_name" class="mobile-input" placeholder="Character Name">
                            </div>

                            <div class="form-group">
                                <label for="transfer_amount">Amount</label>
                                <input type="number" name="amount" id="transfer_amount" class="mobile-input" placeholder="0" min="1" max="<?= $resources->credits ?>">
                            </div>

                            <button type="submit" class="btn btn-warning" style="width: 100%; margin-top: 1rem;">
                                <i class="fas fa-exchange-alt"></i> Transfer
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div> <!-- End nested-tabs-container -->
</div>
