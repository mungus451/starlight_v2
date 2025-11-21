/**
 * Bank Page Logic
 * Handles deposits, withdrawals, transfers, and the recharge timer.
 * Dependencies: StarlightUtils (utils.js)
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. Initialize Input Masks ---
    // We map specific display inputs to their hidden counterparts
    const masks = [
        { display: 'dep-amount-display', hidden: 'dep-amount-hidden' },
        { display: 'wit-amount-display', hidden: 'wit-amount-hidden' },
        { display: 'tran-amount-display', hidden: 'tran-amount-hidden' }
    ];

    masks.forEach(pair => {
        const displayEl = document.getElementById(pair.display);
        const hiddenEl = document.getElementById(pair.hidden);
        if (displayEl && hiddenEl) {
            StarlightUtils.setupInputMask(displayEl, hiddenEl);
        }
    });

    // --- 2. Data Retrieval ---
    // We grab global user data from the DOM headers (standardized across pages)
    const creditsEl = document.getElementById('global-user-credits');
    const bankedEl = document.getElementById('global-user-banked');
    
    const USER_CREDITS = creditsEl ? parseInt(creditsEl.getAttribute('data-credits'), 10) : 0;
    const USER_BANKED = bankedEl ? parseInt(bankedEl.getAttribute('data-banked'), 10) : 0;

    // --- 3. Max Button Logic ---
    
    // Deposit Max (Configurable Limit)
    const maxDepositBtn = document.getElementById('btn-max-deposit');
    if (maxDepositBtn) {
        maxDepositBtn.addEventListener('click', function() {
            // Read the limit percentage from the button itself (e.g., data-limit="0.80")
            const limitPercent = parseFloat(this.getAttribute('data-limit') || 0.8);
            const maxAmount = Math.floor(USER_CREDITS * limitPercent);
            
            const displayInput = document.getElementById('dep-amount-display');
            const hiddenInput = document.getElementById('dep-amount-hidden');
            
            if (displayInput && hiddenInput) {
                displayInput.value = StarlightUtils.formatNumber(maxAmount);
                hiddenInput.value = maxAmount > 0 ? maxAmount : 0;
            }
        });
    }

    // Withdraw Max
    const maxWithdrawBtn = document.getElementById('btn-max-withdraw');
    if (maxWithdrawBtn) {
        maxWithdrawBtn.addEventListener('click', function() {
            const displayInput = document.getElementById('wit-amount-display');
            const hiddenInput = document.getElementById('wit-amount-hidden');
            
            if (displayInput && hiddenInput) {
                displayInput.value = StarlightUtils.formatNumber(USER_BANKED);
                hiddenInput.value = USER_BANKED > 0 ? USER_BANKED : 0;
            }
        });
    }

    // --- 4. Deposit Timer Logic ---
    const timerEl = document.getElementById('deposit-timer-countdown');
    
    if (timerEl) {
        // Read config from data attributes
        const lastDepositAt = timerEl.getAttribute('data-last-deposit'); // "2023-10-27 10:00:00" or empty
        const currentCharges = parseInt(timerEl.getAttribute('data-current-charges'), 10);
        const maxCharges = parseInt(timerEl.getAttribute('data-max-charges'), 10);
        const regenHours = parseInt(timerEl.getAttribute('data-regen-hours'), 10);

        // Only run if we are not full and have a previous deposit time
        if (currentCharges < maxCharges && lastDepositAt) {
            
            // Parse MySQL UTC timestamp to JS Date object
            // We append 'Z' to ensure JS treats it as UTC
            const lastDepositTime = new Date(lastDepositAt.replace(' ', 'T') + 'Z');
            const regenMillis = regenHours * 60 * 60 * 1000;
            const nextRegenTime = new Date(lastDepositTime.getTime() + regenMillis);

            const timerInterval = setInterval(function() {
                const now = new Date();
                const diff = nextRegenTime - now;

                if (diff <= 0) {
                    clearInterval(timerInterval);
                    timerEl.textContent = "Reloading to claim charge...";
                    window.location.reload();
                    return;
                }

                // Calc parts
                const hours = Math.floor(diff / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                // Pad and display
                const hh = String(hours).padStart(2, '0');
                const mm = String(minutes).padStart(2, '0');
                const ss = String(seconds).padStart(2, '0');

                timerEl.textContent = `Next charge in: ${hh}:${mm}:${ss}`;
                
            }, 1000);
        } else if (currentCharges >= maxCharges) {
            timerEl.textContent = "Charges are full";
        }
    }
});