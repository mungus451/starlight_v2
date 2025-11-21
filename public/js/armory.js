/**
 * Armory Page Logic
 * Handles tabs, equipment changes, and manufacturing calculations.
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. Tabbed Interface Logic ---
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');

    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const tabId = this.getAttribute('data-tab');

            // Remove active class from all links and contents
            tabLinks.forEach(l => l.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            // Add active class to clicked link and target content
            this.classList.add('active');
            const targetContent = document.getElementById(tabId);
            if (targetContent) {
                targetContent.classList.add('active');
            }
        });
    });

    // --- 2. "Equip" Form Logic ---
    // Updates hidden inputs and auto-submits the form when a selection changes
    document.querySelectorAll('.equip-select').forEach(select => {
        select.addEventListener('change', function() {
            const form = this.closest('form');
            const categoryKey = this.getAttribute('data-category-key');
            const itemKey = this.value; 
            
            // Update the hidden fields required by the controller
            const categoryInput = form.querySelector('.dynamic-category-key');
            const itemInput = form.querySelector('.dynamic-item-key');

            if (categoryInput) categoryInput.value = categoryKey;
            if (itemInput) itemInput.value = itemKey;
            
            form.submit();
        });
    });

    // --- 3. "Max Manufacture" Logic ---
    const creditsEl = document.getElementById('global-user-credits');
    if (!creditsEl) return; // Safety check

    const USER_CREDITS = parseInt(creditsEl.getAttribute('data-credits'), 10);
    const inventory = {};
    
    // Pre-parse inventory counts from the DOM for prerequisite checks
    document.querySelectorAll('[data-inventory-key]').forEach(el => {
        const key = el.getAttribute('data-inventory-key');
        if (key && key !== 'null') {
            const count = parseInt(el.textContent.replace(/,/g, ''), 10);
            inventory[key] = isNaN(count) ? 0 : count;
        }
    });

    document.querySelectorAll('.btn-max-manufacture').forEach(button => {
        button.addEventListener('click', function() {
            const form = this.closest('.manufacture-form');
            const amountInput = form.querySelector('.manufacture-amount');
            
            // Get data from attributes
            const itemCost = parseInt(amountInput.getAttribute('data-item-cost'), 10);
            const currentOwned = parseInt(amountInput.getAttribute('data-current-owned'), 10);
            const prereqKey = amountInput.getAttribute('data-prereq-key');
            
            // Get unit limit from the parent tab
            const tab = this.closest('.tab-content');
            const unitCount = parseInt(tab.getAttribute('data-unit-count'), 10);

            let maxFromCredits = Infinity;
            let maxFromPrereq = Infinity;
            let maxNeededForArmy = Infinity;

            // 1. Calculate max based on Credits
            if (itemCost > 0) {
                maxFromCredits = Math.floor(USER_CREDITS / itemCost);
            }

            // 2. Calculate max based on Prerequisite Items (for upgrades)
            if (prereqKey && prereqKey !== 'null') {
                maxFromPrereq = inventory[prereqKey] || 0;
            }

            // 3. Calculate max needed to fill the army (soft cap)
            // We cap it at unitCount so user doesn't accidentally make 1M items for 100 soldiers
            if (unitCount > currentOwned) {
                maxNeededForArmy = unitCount - currentOwned;
            } else {
                // If they already have enough for every soldier, allow creating more 
                // but maybe default to 0 or just ignore this cap? 
                // Current logic: if full, max button gives 0 to prevent waste.
                // To allow over-production, we could set this to Infinity.
                // For now, we stick to the logic: "Fill the army"
                maxNeededForArmy = 0; 
            }
            
            // Determine the lowest bottleneck
            const finalMax = Math.min(maxFromCredits, maxFromPrereq, maxNeededForArmy);
            
            amountInput.value = finalMax > 0 ? finalMax : 0;
        });
    });

});