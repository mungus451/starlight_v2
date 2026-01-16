/**
 * Bank Logic (Advisor V2)
 * Handles vault operations, transfers, and real-time charge timers.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Tabs
    if (typeof StarlightUtils !== 'undefined') {
        StarlightUtils.initTabs({
            navSelector: '.structure-nav-btn',
            contentSelector: '.structure-category-container',
            dataAttr: 'tab-target'
        });
    }

    // 2. Initialize Input Masks (Format as you type)
    const masks = [
        { display: 'dep-amount-display', hidden: 'dep-amount-hidden' },
        { display: 'wit-amount-display', hidden: 'wit-amount-hidden' },
        { display: 'tran-amount-display', hidden: 'tran-amount-hidden' }
    ];

    masks.forEach(pair => {
        const displayEl = document.getElementById(pair.display);
        const hiddenEl = document.getElementById(pair.hidden);
        if (displayEl && hiddenEl) {
            // Using StarlightUtils global helper
            if (typeof StarlightUtils !== 'undefined') {
                StarlightUtils.setupInputMask(displayEl, hiddenEl);
            } else {
                // Fallback if Utils not loaded (Simple integer parsing)
                displayEl.addEventListener('input', () => {
                    const raw = displayEl.value.replace(/,/g, '');
                    if (!isNaN(raw) && raw.length > 0) {
                        displayEl.value = parseInt(raw).toLocaleString();
                        hiddenEl.value = parseInt(raw);
                    } else {
                        hiddenEl.value = 0;
                    }
                });
            }
        }
    });

    // 3. Max Button Logic
    
    // Deposit
    const maxDepositBtn = document.getElementById('btn-max-deposit');
    if (maxDepositBtn) {
        maxDepositBtn.addEventListener('click', () => {
            const limit = parseFloat(maxDepositBtn.getAttribute('data-limit') || 0.8);
            const onHand = parseInt(maxDepositBtn.getAttribute('data-onhand') || 0);
            const maxAmount = Math.floor(onHand * limit);
            
            setInputAmount('dep-amount-display', 'dep-amount-hidden', maxAmount);
        });
    }

    // Withdraw
    const maxWithdrawBtn = document.getElementById('btn-max-withdraw');
    if (maxWithdrawBtn) {
        maxWithdrawBtn.addEventListener('click', () => {
            const banked = parseInt(maxWithdrawBtn.getAttribute('data-banked') || 0);
            setInputAmount('wit-amount-display', 'wit-amount-hidden', banked);
        });
    }

    // 4. Charge Timer
    const timerEl = document.getElementById('deposit-timer-countdown');
    if (timerEl) {
        const lastDepositAt = timerEl.getAttribute('data-last-deposit');
        const currentCharges = parseInt(timerEl.getAttribute('data-current-charges') || 0);
        const maxCharges = parseInt(timerEl.getAttribute('data-max-charges') || 4);
        const regenHours = parseFloat(timerEl.getAttribute('data-regen-hours') || 6);

        if (currentCharges < maxCharges && lastDepositAt) {
            // Ensure UTC parsing
            // Assumption: DB Time is UTC. JS Date(UTC_String) works best with T and Z.
            const timeString = lastDepositAt.replace(' ', 'T') + (lastDepositAt.includes('Z') ? '' : 'Z');
            const lastTime = new Date(timeString);
            const regenMs = regenHours * 60 * 60 * 1000;
            const nextTime = new Date(lastTime.getTime() + regenMs);

            const updateTimer = () => {
                const now = new Date();
                const diff = nextTime - now;

                if (diff <= 0) {
                    clearInterval(interval);
                    timerEl.innerText = "Ready!";
                    timerEl.classList.add('text-success');
                    timerEl.classList.remove('text-warning');
                    return;
                }

                const h = Math.floor(diff / (1000 * 60 * 60));
                const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const s = Math.floor((diff % (1000 * 60)) / 1000);

                const hh = String(h).padStart(2, '0');
                const mm = String(m).padStart(2, '0');
                const ss = String(s).padStart(2, '0');

                timerEl.innerText = `${hh}:${mm}:${ss}`;
                timerEl.classList.add('text-warning');
            };

            // Run immediately then interval
            updateTimer();
            const interval = setInterval(updateTimer, 1000);

        } else if (currentCharges >= maxCharges) {
            timerEl.innerText = "Full";
            timerEl.classList.add('text-success');
        } else {
            // Case where < max but no lastDeposit (shouldn't happen unless manual db edit)
            timerEl.innerText = "--:--:--";
        }
    }

    // --- Helpers ---

    function setInputAmount(displayId, hiddenId, amount) {
        const display = document.getElementById(displayId);
        const hidden = document.getElementById(hiddenId);
        if (display && hidden) {
            const safeAmount = amount > 0 ? amount : 0;
            display.value = safeAmount.toLocaleString();
            hidden.value = safeAmount;
        }
    }
});