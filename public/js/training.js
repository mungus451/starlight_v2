document.addEventListener('DOMContentLoaded', function() {
    const globalCreditsElem = document.getElementById('global-credits');
    const globalCitizensElem = document.getElementById('global-citizens');
    let currentCredits = parseInt(globalCreditsElem.dataset.val);
    let currentCitizens = parseInt(globalCitizensElem.dataset.val);

    const ghostCreditsElem = document.getElementById('ghost-credits');
    const ghostCitizensElem = document.getElementById('ghost-citizens');

    // Function to format numbers with commas
    function numberWithCommas(x) {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    document.querySelectorAll('.training-form').forEach(form => {
        const unitType = form.querySelector('input[name="unit_type"]').value;
        const trainAmountInput = form.querySelector('.train-amount');
        const trainMaxBtn = form.querySelector('.train-max-btn');
        const unitCard = form.closest('.structure-card');

        const baseCreditCost = parseInt(unitCard.dataset.costCredits);
        const baseCitizenCost = parseInt(unitCard.dataset.costCitizens);

        // Function to update costs and global resources display
        const updateCosts = () => {
            const amount = parseInt(trainAmountInput.value);
            const totalCreditCost = baseCreditCost * amount;
            const totalCitizenCost = baseCitizenCost * amount;

            // Update ghost values
            ghostCreditsElem.textContent = amount > 0 ? `-${numberWithCommas(totalCreditCost)}` : '';
            ghostCreditsElem.style.color = (currentCredits - totalCreditCost < 0) ? '#ff4d4d' : '#00f3ff';

            ghostCitizensElem.textContent = amount > 0 ? `-${numberWithCommas(totalCitizenCost)}` : '';
            ghostCitizensElem.style.color = (currentCitizens - totalCitizenCost < 0) ? '#ff4d4d' : '#00f3ff';
        };

        // Event listener for amount input changes
        trainAmountInput.addEventListener('input', updateCosts);

        // Event listener for MAX button
        trainMaxBtn.addEventListener('click', () => {
            const maxByCredits = Math.floor(currentCredits / baseCreditCost);
            const maxByCitizens = Math.floor(currentCitizens / baseCitizenCost);
            let maxAffordable = Math.min(maxByCredits, maxByCitizens);
            
            // Ensure costs are not zero to prevent division by zero and infinite training
            if (baseCreditCost === 0 && baseCitizenCost === 0) {
                maxAffordable = 9999999; // Essentially infinite if no cost
            } else if (baseCreditCost === 0) { // Only citizen cost
                 maxAffordable = Math.floor(currentCitizens / baseCitizenCost);
            } else if (baseCitizenCost === 0) { // Only credit cost
                maxAffordable = Math.floor(currentCredits / baseCreditCost);
            }

            trainAmountInput.value = Math.max(0, maxAffordable); // Ensure it's not negative
            updateCosts();
        });

        // Initial cost update on load if input has a value
        if (trainAmountInput.value > 0) {
            updateCosts();
        }
    });
});
