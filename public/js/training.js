document.addEventListener('DOMContentLoaded', function() {
    
    // --- "Max Train" Button Logic ---
    const USER_CREDITS = parseInt(document.getElementById('global-user-credits').getAttribute('data-credits'), 10);
    const USER_CITIZENS = parseInt(document.getElementById('global-user-citizens').getAttribute('data-citizens'), 10);

    document.querySelectorAll('.btn-max-train').forEach(button => {
        button.addEventListener('click', function() {
            const form = this.closest('.train-form');
            const amountInput = form.querySelector('.train-amount');
            
            const creditCost = parseInt(amountInput.getAttribute('data-credit-cost'), 10);
            const citizenCost = parseInt(amountInput.getAttribute('data-citizen-cost'), 10);

            let maxFromCredits = Infinity;
            let maxFromCitizens = Infinity;

            // Check costs to prevent division by zero
            if (creditCost > 0) {
                maxFromCredits = Math.floor(USER_CREDITS / creditCost);
            }
            
            if (citizenCost > 0) {
                maxFromCitizens = Math.floor(USER_CITIZENS / citizenCost);
            }

            // The final max is the *smallest* constraint
            const finalMax = Math.min(maxFromCredits, maxFromCitizens);

            amountInput.value = finalMax > 0 ? finalMax : 0;
        });
    });
});