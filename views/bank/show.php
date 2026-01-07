<?php
// --- Bank View (Advisor V2) ---
/* @var \App\Models\Entities\UserResource $resources */
/* @var \App\Models\Entities\UserStats $stats */
/* @var array $bankConfig */
/* @var string $csrf_token */

// Calculate Charge Percentage for Visual Meter
$maxCharges = (int)$bankConfig['deposit_max_charges'];
$currentCharges = (int)$stats->deposit_charges;
?>

<div class="structures-page-content">
    
    <!-- 1. Page Header -->
    <div class="page-header-container">
        <h1 class="page-title-neon">Interstellar Banking Clan</h1>
        <p class="page-subtitle-tech">
            Secure Asset Management // Imperial Treasury // Faction Banking
        </p>
        <div class="flex-center gap-2 mt-2">
            <div class="badge bg-dark border-secondary">
                <i class="fas fa-bolt text-warning"></i> Deposit Charges: <?= $currentCharges ?> / <?= $maxCharges ?>
            </div>
            <div class="badge bg-dark border-secondary">
                <i class="fas fa-percent text-success"></i> Interest Rate: 0.03%
            </div>
        </div>
    </div>

    <!-- 2. Navigation Deck -->
    <div class="structure-nav-container mb-4">
        <button class="structure-nav-btn active" data-tab-target="vault-content">
            <i class="fas fa-vault"></i> Personal Vault
        </button>
        <button class="structure-nav-btn" data-tab-target="transfer-content">
            <i class="fas fa-exchange-alt"></i> Transfer
        </button>
        <!-- Future: Alliance Bank Tab -->
        <!-- <button class="structure-nav-btn disabled"><i class="fas fa-users-cog"></i> Alliance Bank (Coming Soon)</button> -->
    </div>

    <!-- 3. Content Deck -->
    <div class="structure-deck">
        
        <!-- VAULT TAB -->
        <div id="vault-content" class="structure-category-container active">
            
            <!-- Hero: Net Worth Summary -->
            <div class="structure-card mb-4" style="border-color: var(--accent-gold);">
                <div class="card-body-main p-4 text-center">
                    <h2 class="text-uppercase text-muted font-08 mb-2">Total Liquid Assets</h2>
                    <div class="display-4 fw-bold text-light mb-3">
                        <i class="fas fa-coins text-warning me-2"></i> 
                        <?= number_format($resources->credits + $resources->banked_credits) ?>
                    </div>
                    
                    <div class="d-flex justify-content-center gap-4">
                        <div class="text-center">
                            <span class="d-block text-success font-08">SECURE (Banked)</span>
                            <span class="d-block fw-bold fs-5"><?= number_format($resources->banked_credits) ?></span>
                        </div>
                        <div class="vr bg-secondary opacity-50"></div>
                        <div class="text-center">
                            <span class="d-block text-danger font-08">EXPOSED (On Hand)</span>
                            <span class="d-block fw-bold fs-5"><?= number_format($resources->credits) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Grid -->
            <div class="structures-grid">
                
                <!-- Deposit Card -->
                <div class="structure-card">
                    <div class="card-header-main">
                        <span class="card-icon"><i class="fas fa-arrow-down"></i></span>
                        <div class="card-title-group">
                            <h3 class="card-title">Deposit</h3>
                            <p class="card-level text-muted">Secure your credits.</p>
                        </div>
                    </div>
                    
                    <div class="card-body-main">
                        <form action="/bank/deposit" method="POST" id="deposit-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                            
                            <div class="mb-3">
                                <label class="text-muted font-07 text-uppercase mb-1">Amount (Max 80%)</label>
                                <div class="input-group">
                                    <input type="text" id="dep-amount-display" class="form-control bg-dark border-secondary text-light formatted-amount" placeholder="0" required>
                                    <input type="hidden" name="amount" id="dep-amount-hidden" value="0">
                                    <button type="button" class="btn btn-outline-info" id="btn-max-deposit" 
                                            data-limit="<?= $bankConfig['deposit_percent_limit'] ?? 0.8 ?>"
                                            data-onhand="<?= $resources->credits ?>">
                                        MAX
                                    </button>
                                </div>
                            </div>

                            <!-- Charge Meter -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between font-07 text-muted mb-1">
                                    <span>Deposit Charges</span>
                                    <span id="deposit-timer-countdown"
                                          data-last-deposit="<?= htmlspecialchars($stats->last_deposit_at ?? '') ?>"
                                          data-current-charges="<?= $currentCharges ?>"
                                          data-max-charges="<?= $maxCharges ?>"
                                          data-regen-hours="<?= (int)$bankConfig['deposit_charge_regen_hours'] ?>">
                                    </span>
                                </div>
                                <div class="charge-meter d-flex gap-1">
                                    <?php for($i=0; $i<$maxCharges; $i++): ?>
                                        <div class="charge-cell <?= $i < $currentCharges ? 'active' : '' ?>"></div>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100" <?= $currentCharges <= 0 ? 'disabled' : '' ?>>
                                <i class="fas fa-lock me-2"></i> <?= $currentCharges <= 0 ? 'Recharging...' : 'Deposit Funds' ?>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Withdraw Card -->
                <div class="structure-card">
                    <div class="card-header-main">
                        <span class="card-icon"><i class="fas fa-arrow-up"></i></span>
                        <div class="card-title-group">
                            <h3 class="card-title">Withdraw</h3>
                            <p class="card-level text-muted">Access liquid funds.</p>
                        </div>
                    </div>
                    
                    <div class="card-body-main">
                        <form action="/bank/withdraw" method="POST" id="withdraw-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                            
                            <div class="mb-3">
                                <label class="text-muted font-07 text-uppercase mb-1">Amount</label>
                                <div class="input-group">
                                    <input type="text" id="wit-amount-display" class="form-control bg-dark border-secondary text-light formatted-amount" placeholder="0" required>
                                    <input type="hidden" name="amount" id="wit-amount-hidden" value="0">
                                    <button type="button" class="btn btn-outline-info" id="btn-max-withdraw" data-banked="<?= $resources->banked_credits ?>">
                                        MAX
                                    </button>
                                </div>
                            </div>

                            <div class="alert alert-dark font-08 text-muted mb-3 border-secondary">
                                <i class="fas fa-info-circle me-1"></i> No fees or limits on withdrawals.
                            </div>

                            <button type="submit" class="btn btn-outline-warning w-100">
                                <i class="fas fa-unlock me-2"></i> Withdraw Funds
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>

        <!-- TRANSFER TAB -->
        <div id="transfer-content" class="structure-category-container">
            <div class="structure-card mx-auto" style="max-width: 600px;">
                <div class="card-header-main">
                    <span class="card-icon"><i class="fas fa-paper-plane"></i></span>
                    <div class="card-title-group">
                        <h3 class="card-title">Wire Transfer</h3>
                        <p class="card-level text-muted">Send credits to another commander.</p>
                    </div>
                </div>
                
                <div class="card-body-main">
                    <form action="/bank/transfer" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                        
                        <div class="mb-3">
                            <label class="text-muted font-07 text-uppercase mb-1">Recipient Name</label>
                            <input type="text" name="recipient_name" class="form-control bg-dark border-secondary text-light" placeholder="Commander Name" required>
                        </div>

                        <div class="mb-3">
                            <label class="text-muted font-07 text-uppercase mb-1">Amount</label>
                            <input type="text" id="tran-amount-display" class="form-control bg-dark border-secondary text-light formatted-amount" placeholder="0" required>
                            <input type="hidden" name="amount" id="tran-amount-hidden" value="0">
                        </div>

                        <div class="alert alert-dark font-08 text-warning mb-3 border-secondary">
                            <i class="fas fa-exclamation-triangle me-1"></i> Transfers are irreversible. Ensure the recipient name is correct.
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-check-circle me-2"></i> Initiate Transfer
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="/js/bank.js?v=<?= time() ?>"></script>

<style>
/* Charge Meter Styling */
.charge-meter {
    height: 8px;
    width: 100%;
    background: rgba(0,0,0,0.3);
    border-radius: 4px;
    overflow: hidden;
}
.charge-cell {
    flex: 1;
    background: rgba(255,255,255,0.1);
    transition: all 0.3s ease;
}
.charge-cell.active {
    background: var(--accent); /* Cyan/Neon Blue */
    box-shadow: 0 0 5px var(--accent);
}
</style>
