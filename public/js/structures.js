document.addEventListener('DOMContentLoaded', function() {
    
    // ==========================================
    // 1. Category Tab Logic (Central Deck)
    // ==========================================
    if (typeof StarlightUtils !== 'undefined') {
        StarlightUtils.initTabs({
            navSelector: '.structure-nav-btn',
            contentSelector: '.structure-category-container',
            dataAttr: 'tab-target'
        });
    }

    // ==========================================
    // 2. Shopping Cart / Batch Logic
    // ==========================================
    const cart = new Set();
    const costs = {}; // key -> {credits}
    const names = {};

    // Elements
    const checkoutBox = document.getElementById('structure-checkout-box');
    const checkoutList = document.getElementById('checkout-list');
    const totalCreditsEl = document.getElementById('checkout-total-credits');
    
    const checkoutForm = document.getElementById('checkout-form');
    const checkoutInput = document.getElementById('checkout-input-keys');
    const cancelBatchBtn = document.getElementById('btn-cancel-batch');

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
        const name = btn.dataset.name;

        cart.add(key);
        costs[key] = { cred };
        names[key] = name;

        // UI Update
        btn.classList.add('in-cart');
        btn.innerHTML = '<i class="fas fa-minus"></i> Remove';
        btn.classList.replace('btn-outline-info', 'btn-info'); 
        
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
        btn.innerHTML = '<i class="fas fa-plus"></i> Batch';
        btn.classList.replace('btn-info', 'btn-outline-info');

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

        if (checkoutBox) checkoutBox.style.display = 'flex'; // Changed to flex for new HUD layout
        
        // Calculate Totals
        let tCred = 0;
        
        // Clear list
        if (checkoutList) checkoutList.innerHTML = '';

        cart.forEach(key => {
            const c = costs[key];
            tCred += c.cred;

            if (checkoutList) {
                const li = document.createElement('div');
                li.className = 'hud-item';
                li.textContent = names[key];
                checkoutList.appendChild(li);
            }
        });

        if (totalCreditsEl) totalCreditsEl.textContent = tCred.toLocaleString();
    }

    // ==========================================
    // 3. "Upgrade Now" AJAX Logic
    // ==========================================
    document.querySelectorAll('.btn-upgrade-now').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();

            const structureKey = btn.dataset.key;
            const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';

            if (!structureKey) {
                console.error('Structure key not found on button.');
                return;
            }

            btn.disabled = true;
            const originalText = btn.innerHTML;
            btn.innerHTML = 'Upgrading...';

            try {
                const formData = new FormData();
                formData.append('structure_key', structureKey);
                formData.append('csrf_token', csrfToken);

                const response = await fetch('/structures/upgrade', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest' // To let backend know it's AJAX
                    },
                    body: formData
                });

                // Since the backend redirects, we check the 'redirected' flag
                if (response.redirected || response.ok) {
                    // The backend will set a flash message, so we just need to reload
                    window.location.reload();
                } else {
                    // Try to get an error from a JSON response if the backend is set up for it
                    // For now, we assume a generic error
                    showToast('Upgrade failed. Please check your resources.', 'error');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }

            } catch (error) {
                console.error('Upgrade Error:', error);
                showToast('A network error occurred.', 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    });

    // Simple toast notification function
    function showToast(message, type) {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.style.cssText = 'position:fixed; bottom:20px; right:20px; z-index:10000; display:flex; flex-direction:column; gap:10px;';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.innerText = message;
        const bg = type === 'success' ? '#4CAF50' : '#e53e3e';
        toast.style.cssText = `background:${bg}; color:#fff; padding:12px 24px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.3); font-size:0.9rem; opacity:0; transform:translateY(20px); transition:all 0.3s ease;`;

        container.appendChild(toast);

        requestAnimationFrame(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        });

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
});