document.addEventListener('DOMContentLoaded', function() {
    const availableCreditsElem = document.getElementById('available-credits');
    const availableCitizensElem = document.getElementById('available-citizens');
    const totalBuildCostElem = document.getElementById('total-build-cost');
    // total-refund-value is no longer relevant as disband tab is removed.

    let currentCredits = parseInt(availableCreditsElem.dataset.amount);
    let currentCitizens = parseInt(availableCitizensElem.dataset.amount);

    // Function to format numbers with commas
    function numberWithCommas(x) {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // Function to update total costs across all training inputs
    const updateOverallCosts = () => {
        let totalCost = 0;
        document.querySelectorAll('.training-input-field').forEach(input => {
            const amount = parseInt(input.value) || 0;
            const costPerUnit = parseInt(input.dataset.cost);
            totalCost += amount * costPerUnit;
        });
        totalBuildCostElem.textContent = numberWithCommas(totalCost);

        // Update color based on affordability
        if (totalCost > currentCredits) {
            totalBuildCostElem.classList.remove('text-yellow-400');
            totalBuildCostElem.classList.add('text-red-500');
        } else {
            totalBuildCostElem.classList.remove('text-red-500');
            totalBuildCostElem.classList.add('text-yellow-400');
        }
    };

    document.querySelectorAll('.training-input-field').forEach(input => {
        // Event listener for amount input changes
        input.addEventListener('input', updateOverallCosts);
    });

    document.querySelectorAll('.training-max-btn.train').forEach(button => {
        button.addEventListener('click', () => {
            const input = button.parentNode.querySelector('.training-input-field');
            const costPerUnit = parseInt(input.dataset.cost);

            const maxByCredits = costPerUnit > 0 ? Math.floor(currentCredits / costPerUnit) : Number.MAX_SAFE_INTEGER;
            const maxByCitizens = 0; // Citizens cost is per unit, but overall citizens are untrainined. Max should be based on available citizens for ALL units or the specific unit.

            // Given the HTML, citizens are a global resource (untrained_citizens).
            // A unit's 'costCitizens' is not specified per unit, it's implied by available citizens.
            // For simplicity, max is currently based only on credits for individual units.
            // If `untrained_citizens` is also a per-unit cost, that needs to be clarified.
            // For now, let's assume `untrained_citizens` is the overall pool for all.
            // And each unit 'consumes' 1 citizen, unless `unit['citizens']` exists in PHP.
            // Based on HTML: `available-citizens` is total untrained.
            // Each unit card just shows cost in credits. No citizen cost shown per unit.
            // Let's assume each unit consumes 1 citizen for now.

            const maxByTotalCitizens = currentCitizens; // If each unit consumes 1 citizen.

            let maxAffordable = Math.min(maxByCredits, maxByTotalCitizens);
            
            if (costPerUnit === 0) {
                maxAffordable = maxByTotalCitizens; // Max is limited only by citizens if credits are free
            }

            input.value = Math.max(0, maxAffordable);
            updateOverallCosts();
        });
    });

    // Initial cost update on load
    updateOverallCosts();
});
