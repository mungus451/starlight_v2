/**
 * Bank Logic (Advisor V2)
 * Handles vault operations, transfers, and real-time charge timers.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Tabs
    initTabs();

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
            const limit = parseFloat(maxDepositBtn.dataset.limit || 0.8);
            const onHand = parseInt(maxDepositBtn.dataset.onhand || 0);
            const maxAmount = Math.floor(onHand * limit);
            
            setInputAmount('dep-amount-display', 'dep-amount-hidden', maxAmount);
        });
    }

    // Withdraw
    const maxWithdrawBtn = document.getElementById('btn-max-withdraw');
    if (maxWithdrawBtn) {
        maxWithdrawBtn.addEventListener('click', () => {
            const banked = parseInt(maxWithdrawBtn.dataset.banked || 0);
            setInputAmount('wit-amount-display', 'wit-amount-hidden', banked);
        });
    }

    // 4. Charge Timer
    const timerEl = document.getElementById('deposit-timer-countdown');
    if (timerEl) {
        const lastDepositAt = timerEl.dataset.lastDeposit;
        const currentCharges = parseInt(timerEl.dataset.currentCharges);
        const maxCharges = parseInt(timerEl.dataset.maxCharges);
        const regenHours = parseInt(timerEl.dataset.regenHours);

        if (currentCharges < maxCharges && lastDepositAt) {
            // Ensure UTC parsing by appending Z if missing (standard MySQL timestamp fix)
            const timeString = lastDepositAt.endsWith('Z') ? lastDepositAt : lastDepositAt.replace(' ', 'T') + 'Z';
            const lastTime = new Date(timeString);
            const regenMs = regenHours * 60 * 60 * 1000;
            const nextTime = new Date(lastTime.getTime() + regenMs);

            const interval = setInterval(() => {
                const now = new Date();
                const diff = nextTime - now;

                if (diff <= 0) {
                    clearInterval(interval);
                    timerEl.innerText = "Ready!";
                    timerEl.classList.add('text-success');
                    return;
                }

                const h = Math.floor(diff / (1000 * 60 * 60));
                const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const s = Math.floor((diff % (1000 * 60)) / 1000);

                timerEl.innerText = `${h}h ${m}m ${s}s`;
            }, 1000);
        } else if (currentCharges >= maxCharges) {
            timerEl.innerText = "Full";
            timerEl.classList.add('text-success');
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

    function initTabs() {
        const tabs = document.querySelectorAll('.structure-nav-btn[data-tab-target]');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                document.querySelectorAll('.structure-category-container').forEach(c => c.classList.remove('active'));
                const target = document.getElementById(tab.dataset.tabTarget);
                if (target) target.classList.add('active');
            });
        });
    }
});
