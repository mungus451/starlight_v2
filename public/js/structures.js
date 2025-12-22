document.addEventListener('DOMContentLoaded', function() {
    
    // ==========================================
    // 1. Expand / Collapse Logic
    // ==========================================
    const allCards = document.querySelectorAll('.structure-card');
    const expandAllBtn = document.getElementById('btn-expand-all');
    const collapseAllBtn = document.getElementById('btn-collapse-all');

    // Individual Card Toggling
    const allHeaders = document.querySelectorAll('.card-header-main, .card-header');
    allHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const card = this.closest('.structure-card');
            if (card) {
                card.classList.toggle('is-expanded');
            }
        });
    });

    // Expand All Button
    if (expandAllBtn) {
        expandAllBtn.addEventListener('click', function() {
            allCards.forEach(card => {
                card.classList.add('is-expanded');
            });
        });
    }
    
    // Collapse All Button
    if (collapseAllBtn) {
        collapseAllBtn.addEventListener('click', function() {
            allCards.forEach(card => {
                card.classList.remove('is-expanded');
            });
        });
    }

    // ==========================================
    // 2. Shopping Cart / Batch Logic
    // ==========================================
    const cart = new Set();
    const costs = {}; // key -> {credits, crystal, dm}
    const names = {};

    // Elements
    const checkoutBox = document.getElementById('structure-checkout-box');
    const checkoutList = document.getElementById('checkout-list');
    const totalCreditsEl = document.getElementById('checkout-total-credits');
    const totalCrystalEl = document.getElementById('checkout-total-crystal');
    const totalDmEl = document.getElementById('checkout-total-dm');
    
    const checkoutForm = document.getElementById('checkout-form');
    const checkoutInput = document.getElementById('checkout-input-keys');
    const cancelBatchBtn = document.getElementById('btn-cancel-batch');
    const rareResourcesContainer = document.getElementById('checkout-rare-resources');

    // Attach Event Listeners to "Add to Cart" Buttons
    document.querySelectorAll('.btn-add-cart').forEach(btn => {
        btn.addEventListener('click', (e) => {
            // Prevent default form submission if it was a submit button
            e.preventDefault(); 
            e.stopPropagation(); // Stop card expansion click

            const key = btn.dataset.key;
            if (cart.has(key)) {
                removeFromCart(key, btn);
            } else {
                addToCart(key, btn);
            }
        });
    });

    if (cancelBatchBtn) {
        cancelBatchBtn.addEventListener('click', clearCart);
    }

    if (checkoutForm) {
        checkoutForm.addEventListener('submit', (e) => {
            if (cart.size === 0) {
                e.preventDefault();
                alert('Cart is empty.');
                return;
            }
            
            const keys = Array.from(cart);
            const json = JSON.stringify(keys);
            
            if (checkoutInput) {
                checkoutInput.value = json;
                // Debug log
                console.log("Submitting Batch Upgrade:", json);
            } else {
                e.preventDefault();
                console.error("Critical Error: #checkout-input-keys not found in DOM.");
                alert("System Error: Could not prepare batch data. Please refresh and try again.");
            }
        });
    }

    function addToCart(key, btn) {
        // Parse costs
        const cred = parseInt(btn.dataset.costCredits || 0);
        const cry = parseInt(btn.dataset.costCrystal || 0);
        const dm = parseInt(btn.dataset.costDm || 0);
        const name = btn.dataset.name;

        cart.add(key);
        costs[key] = { cred, cry, dm };
        names[key] = name;

        // UI Update
        btn.classList.add('in-cart');
        btn.textContent = 'Remove from Batch';
        btn.classList.replace('btn-submit', 'btn-secondary'); // Visual toggle
        
        // Also ensure the parent card is expanded so user sees they selected it? 
        // Optional, but might be nice.
        const card = btn.closest('.structure-card');
        if (card) card.classList.add('is-expanded');

        updateCheckoutUI();
    }

    function removeFromCart(key, btn) {
        cart.delete(key);
        delete costs[key];
        
        // UI Update
        btn.classList.remove('in-cart');
        btn.textContent = `Add to Batch (Lvl ${btn.dataset.nextLevel})`;
        btn.classList.replace('btn-secondary', 'btn-submit');

        updateCheckoutUI();
    }

    function clearCart() {
        // Need to convert to array to avoid modification issues during iteration?
        // Actually forEach on Set is safe.
        // We need to find the buttons for each key.
        const keys = Array.from(cart);
        keys.forEach(key => {
            const btn = document.querySelector(`.btn-add-cart[data-key="${key}"]`);
            if (btn) {
                removeFromCart(key, btn);
            } else {
                // If button not found (shouldn't happen), just delete from data
                cart.delete(key);
                delete costs[key];
            }
        });
        updateCheckoutUI();
    }

    function updateCheckoutUI() {
        if (cart.size === 0) {
            if (checkoutBox) checkoutBox.style.display = 'none';
            return;
        }

        if (checkoutBox) checkoutBox.style.display = 'block';
        
        // Calculate Totals
        let tCred = 0, tCry = 0, tDm = 0;
        
        // Clear list
        if (checkoutList) checkoutList.innerHTML = '';

        cart.forEach(key => {
            const c = costs[key];
            tCred += c.cred;
            tCry += c.cry;
            tDm += c.dm;

            if (checkoutList) {
                const li = document.createElement('div');
                li.className = 'checkout-item';
                li.style.fontSize = '0.9em';
                li.style.padding = '2px 0';
                li.style.borderBottom = '1px solid rgba(255,255,255,0.1)';
                li.textContent = names[key];
                checkoutList.appendChild(li);
            }
        });

        if (totalCreditsEl) totalCreditsEl.textContent = tCred.toLocaleString();
        
        if (rareResourcesContainer) {
            if (tCry > 0 || tDm > 0) {
                rareResourcesContainer.style.display = 'block';
                if (totalCrystalEl) totalCrystalEl.textContent = tCry.toLocaleString();
                if (totalDmEl) totalDmEl.textContent = tDm.toLocaleString();
            } else {
                rareResourcesContainer.style.display = 'none';
            }
        }
    }

});