/**
 * Alliance Profile Page Logic
 * Handles donations, loan requests, and repayments.
 * Dependencies: StarlightUtils (utils.js)
 */
document.addEventListener('DOMContentLoaded', function() {
    
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
            const creditsEl = document.getElementById('global-user-credits');
            const USER_CREDITS = creditsEl ? parseInt(creditsEl.getAttribute('data-credits'), 10) : 0;
            
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
        const wrapper = displayInput.closest('.amount-input-group');
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
            const USER_CREDITS = creditsEl ? parseInt(creditsEl.getAttribute('data-credits'), 10) : 0;
            
            // User can only repay what they have or what they owe, whichever is less
            const actualMax = Math.min(USER_CREDITS, maxRepay);

            const wrapper = this.closest('.amount-input-group');
            const displayInput = wrapper.querySelector('.repay-amount-display');
            const hiddenInput = wrapper.querySelector('.repay-amount-hidden');
            
            if (displayInput && hiddenInput) {
                displayInput.value = (actualMax > 0) ? StarlightUtils.formatNumber(actualMax) : '';
                hiddenInput.value = actualMax;
            }
        });
    });
});