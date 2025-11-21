/**
 * Level Up Page Logic
 * Handles real-time calculation of stat point costs and validation.
 */
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('level-up-form');
    
    // If the form doesn't exist (e.g. user not on this page), exit
    if (!form) return;

    const inputs = form.querySelectorAll('.stat-input');
    const totalSpan = document.getElementById('total-to-spend');
    const availablePointsEl = document.getElementById('available-points');
    const submitBtn = document.getElementById('spend-points-btn');

    if (!availablePointsEl || !submitBtn) return;

    const availablePoints = parseInt(availablePointsEl.getAttribute('data-points'), 10);
    
    // Determine cost per point from the first input's data attribute
    // Default to 1 if not found to prevent NaN errors
    const firstInput = form.querySelector('.stat-input');
    const costPerPoint = firstInput ? parseInt(firstInput.getAttribute('data-cost'), 10) : 1;

    function updateTotal() {
        let totalPointsAllocated = 0;
        
        inputs.forEach(input => {
            let val = parseInt(input.value, 10);
            if (isNaN(val) || val < 0) {
                val = 0;
                // Optional: reset invalid input visually
                // input.value = 0; 
            }
            totalPointsAllocated += val;
        });
        
        const totalCost = totalPointsAllocated * costPerPoint;
        if (totalSpan) {
            totalSpan.textContent = StarlightUtils.formatNumber(totalCost);
        }

        // Validation Logic
        // 1. Spending 0 is invalid
        // 2. Spending more than available is invalid
        if (totalCost > 0 && totalCost <= availablePoints) {
            submitBtn.disabled = false;
            submitBtn.textContent = `Spend ${StarlightUtils.formatNumber(totalCost)} Points`;
            submitBtn.classList.remove('btn-disabled'); // Assuming you might want styling hooks
        } else if (totalCost > availablePoints) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Not Enough Points';
        } else {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Spend Points';
        }
    }
    
    // Attach listeners
    inputs.forEach(input => {
        // 'input' fires on every keystroke/spinner change
        input.addEventListener('input', updateTotal);
    });

    // Initial check (handles browser auto-fill or page reload retention)
    updateTotal();
});