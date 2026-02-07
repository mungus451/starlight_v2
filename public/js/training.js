document.addEventListener('DOMContentLoaded', function() {
    const availableCreditsElem = document.getElementById('available-credits');
    const availableCitizensElem = document.getElementById('available-citizens');
    const totalBuildCostElem = document.getElementById('total-build-cost');

    // Function to format numbers with commas
    function numberWithCommas(x) {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // Function to update total costs across all training inputs
    const updateOverallCosts = () => {
        // Get the fresh credit count from the DOM every time the function is called.
        const currentCredits = parseInt(availableCreditsElem.dataset.amount) || 0;
        let totalCost = 0;
        
        document.querySelectorAll('.training-input-field').forEach(input => {
            const amount = parseInt(input.value) || 0;
            const costPerUnit = parseInt(input.dataset.cost) || 0;
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

    // Add event listener to all training inputs to update costs on change
    document.querySelectorAll('.training-input-field').forEach(input => {
        input.addEventListener('input', updateOverallCosts);
    });

    // Add event listener to all "Max" buttons
    document.querySelectorAll('.training-max-btn.train').forEach(button => {
        button.addEventListener('click', () => {
            // Get fresh resource counts on every click
            const currentCredits = parseInt(availableCreditsElem.dataset.amount) || 0;
            const currentCitizens = parseInt(availableCitizensElem.dataset.amount) || 0;
            
            const currentInput = button.closest('.actions').querySelector('.training-input-field');
            const costPerUnit = parseInt(currentInput.dataset.cost) || 0;

            let alreadyAllocatedCredits = 0;
            let alreadyAllocatedCitizens = 0;

            // Calculate credits and citizens allocated in other input fields
            document.querySelectorAll('.training-input-field').forEach(input => {
                if (input !== currentInput) {
                    const amount = parseInt(input.value) || 0;
                    alreadyAllocatedCredits += amount * (parseInt(input.dataset.cost) || 0);
                    alreadyAllocatedCitizens += amount;
                }
            });

            const remainingCredits = currentCredits - alreadyAllocatedCredits;
            const remainingCitizens = currentCitizens - alreadyAllocatedCitizens;

            let maxAffordableByCredit = 0;
            if (costPerUnit > 0) {
                maxAffordableByCredit = Math.floor(remainingCredits / costPerUnit);
            } else {
                // If the item is free, there is no credit limit.
                maxAffordableByCredit = Infinity; 
            }

            // The effective max is the minimum of what can be afforded and what can be trained from available citizens
            const maxAffordable = Math.min(maxAffordableByCredit, remainingCitizens);

            currentInput.value = Math.max(0, maxAffordable);
            
            // Trigger the overall cost update after setting the new value.
            updateOverallCosts();
        });
    });

    // Initial cost update on page load
    updateOverallCosts();
});
