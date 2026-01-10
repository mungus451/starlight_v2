/**
 * Alliance Profile Page Logic
 * Handles donations, loan requests, repayments, and tab navigation.
 * Dependencies: StarlightUtils (utils.js)
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // --- 0. Initialize Tabs ---
    if (typeof StarlightUtils !== 'undefined') {
        StarlightUtils.initTabs({
            storageKey: 'alliance_profile_active_tab',
            defaultTab: 'tab-ops'
        });
    }

    // --- 1. Donation Form ---
    const donateDisplay = document.getElementById('donate-amount-display');
    const donateHidden = document.getElementById('donate-amount-hidden');
    
    if (donateDisplay && donateHidden) {
        StarlightUtils.setupInputMask(donateDisplay, donateHidden);
    }
    
    const maxDonateBtn = document.getElementById('btn-max-donate');
    if (maxDonateBtn) {
        maxDonateBtn.addEventListener('click', function() {
            // Attempt to get user credits from a global element (if it exists)
            // Advisor V2 puts this in #advisor-data or we can assume it's passed globally?
            // Fallback: Check if there's a global var or element. 
            // Often "global-user-credits" is injected in layout or header.
            const creditsEl = document.getElementById('global-user-credits');
            let USER_CREDITS = 0;

            if (creditsEl) {
                USER_CREDITS = parseInt(creditsEl.getAttribute('data-credits'), 10);
            } else if (window.UserResources && window.UserResources.credits) {
                USER_CREDITS = window.UserResources.credits;
            } else {
                // If we can't find it, we can't auto-fill safely.
                console.warn('User credits source not found for MAX button.');
                return;
            }
            
            if (donateDisplay && donateHidden) {
                donateDisplay.value = (USER_CREDITS > 0) ? StarlightUtils.formatNumber(USER_CREDITS) : '';
                donateHidden.value = USER_CREDITS;
            }
        });
    }

    // --- 2. Loan Request Form ---
    const loanRequestDisplay = document.getElementById('loan-request-display');
    const loanRequestHidden = document.getElementById('loan-request-hidden');
    
    if (loanRequestDisplay && loanRequestHidden) {
        StarlightUtils.setupInputMask(loanRequestDisplay, loanRequestHidden);
    }
    
    // --- 3. Repayment Forms (Multiple) ---
    
    // Setup masks for all repayment inputs
    document.querySelectorAll('.repay-amount-display').forEach(displayInput => {
        const wrapper = displayInput.closest('.input-group') || displayInput.closest('.amount-input-group');
        const hiddenInput = wrapper ? wrapper.querySelector('.repay-amount-hidden') : null;
        
        if (displayInput && hiddenInput) {
            StarlightUtils.setupInputMask(displayInput, hiddenInput);
        }
    });

    // Setup "Max" buttons for all repayment forms
    document.querySelectorAll('.btn-max-repay').forEach(button => {
        button.addEventListener('click', function() {
            const maxRepay = parseInt(this.getAttribute('data-max-repay'), 10);
            
            // Get user credits
            const creditsEl = document.getElementById('global-user-credits');
            let USER_CREDITS = 0;
            
            if (creditsEl) {
                USER_CREDITS = parseInt(creditsEl.getAttribute('data-credits'), 10);
            } else if (window.UserResources && window.UserResources.credits) {
                USER_CREDITS = window.UserResources.credits;
            }

            // User can only repay what they have or what they owe, whichever is less
            const actualMax = Math.min(USER_CREDITS, maxRepay);

            const wrapper = this.closest('.input-group') || this.closest('.amount-input-group');
            const displayInput = wrapper.querySelector('.repay-amount-display');
            const hiddenInput = wrapper.querySelector('.repay-amount-hidden');
            
            if (displayInput && hiddenInput) {
                displayInput.value = (actualMax > 0) ? StarlightUtils.formatNumber(actualMax) : '';
                hiddenInput.value = actualMax;
            }
        });
    });
});