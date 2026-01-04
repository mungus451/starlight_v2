/**
 * Armory.js
 * Handles tabs, batch manufacturing logic, and AJAX interactions.
 * Refactored for dynamic re-initialization on Mobile AJAX loads.
 */

window.Armory = {
    cart: new Map(), // key -> {qty, name, cost}

    init: function() {
        console.log('[Armory] Initializing features...');
        this.initTabs();
        this.initMaxButtons();
        this.initEquipForms();
        this.initBatchSystem();
    },

    // --- Tab Switching (Static tabs, e.g. Desktop) ---
    initTabs: function() {
        const links = document.querySelectorAll('.tabs-nav:not(#armory-tabs) .tab-link');
        const contents = document.querySelectorAll('.tab-content');

        links.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                links.forEach(l => l.classList.remove('active'));
                contents.forEach(c => c.classList.remove('active'));
                link.classList.add('active');
                const targetId = link.dataset.tab;
                const target = document.getElementById(targetId);
                if (target) target.classList.add('active');
            });
        });
    },

    // --- Max Manufacture Buttons ---
    initMaxButtons: function() {
        const maxBtns = document.querySelectorAll('.btn-max-manufacture');
        
        maxBtns.forEach(btn => {
            // Remove existing listeners to prevent duplicates if init is called twice
            btn.onclick = (e) => {
                e.preventDefault();
                const container = btn.closest('.input-group') || btn.parentElement;
                const input = container.querySelector('input.manufacture-amount');
                if (!input) return;

                const cost = parseInt(btn.dataset.itemCost || 0);
                const reqOwned = parseInt(btn.dataset.reqOwned || 999999999);
                
                const creditElem = document.getElementById('global-user-credits');
                const currentCredits = parseInt(creditElem.dataset.credits || creditElem.innerText.replace(/,/g, '')) || 0;

                if (cost <= 0) {
                    input.value = reqOwned !== 999999999 ? reqOwned : 0;
                    return;
                }

                let maxQty = Math.floor(currentCredits / cost);
                if (reqOwned < maxQty) {
                    maxQty = reqOwned;
                }

                input.value = Math.max(0, maxQty);
            };
        });
    },

    // --- Batch System ---
    initBatchSystem: function() {
        const forms = document.querySelectorAll('.manufacture-form');
        
        forms.forEach(form => {
            const input = form.querySelector('input[name="quantity"]');
            const btn = form.querySelector('.btn-manufacture-submit');
            const itemKey = form.querySelector('input[name="item_key"]').value;
            
            // Get Item Name safely
            const card = form.closest('.item-card');
            const itemName = card ? (card.querySelector('h4')?.innerText || card.querySelector('h3')?.innerText) : 'Item';
            
            // Transform button to "Add to Batch" behavior
            if (btn) {
                btn.type = 'button';
                // Check if already in cart
                if (this.cart.has(itemKey)) {
                    const existing = this.cart.get(itemKey);
                    btn.innerText = `In Batch (${existing.qty})`;
                    btn.classList.add('btn-secondary');
                } else {
                    btn.innerText = 'Add to Batch';
                    btn.classList.remove('btn-secondary');
                }

                btn.onclick = (e) => {
                    e.preventDefault();
                    const qty = parseInt(input.value) || 0;
                    if (qty <= 0) {
                        this.showToast('Please enter a quantity.', 'error');
                        return;
                    }
                    
                    const cost = parseInt(input.dataset.itemCost || 0);
                    this.addToCart(itemKey, qty, itemName, cost, btn);
                };
            }
        });

        // Checkout Logic (only once)
        const checkoutForm = document.getElementById('armory-checkout-form');
        if (checkoutForm && !checkoutForm.dataset.initialized) {
            checkoutForm.dataset.initialized = "true";
            checkoutForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (this.cart.size === 0) return;
                
                const submitBtn = checkoutForm.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerText;
                submitBtn.disabled = true;
                submitBtn.innerText = 'Processing...';

                const items = [];
                this.cart.forEach((data, key) => {
                    items.push({ item_key: key, quantity: data.qty });
                });

                try {
                    const formData = new FormData();
                    formData.append('csrf_token', checkoutForm.querySelector('input[name="csrf_token"]').value);
                    formData.append('items', JSON.stringify(items));
                    
                    const response = await fetch('/armory/batch-manufacture', {
                        method: 'POST',
                        headers: { 
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        window.location.reload(); 
                    } else {
                        this.showToast(result.error || 'Batch failed.', 'error');
                        submitBtn.disabled = false;
                        submitBtn.innerText = originalText;
                    }
                } catch (err) {
                    console.error(err);
                    this.showToast('Network error.', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerText = originalText;
                }
            });
        }
        
        const cancelBtn = document.getElementById('btn-cancel-batch');
        if (cancelBtn) {
            cancelBtn.onclick = () => {
                this.cart.clear();
                this.updateCheckoutUI();
                document.querySelectorAll('.btn-manufacture-submit').forEach(b => {
                    b.innerText = 'Add to Batch';
                    b.classList.remove('btn-secondary');
                });
            };
        }
    },

    addToCart: function(key, qty, name, cost, btn) {
        this.cart.set(key, { qty, name, cost });
        
        btn.innerText = `In Batch (${qty})`;
        btn.classList.add('btn-secondary');
        
        this.updateCheckoutUI();
        this.showToast(`Added ${qty}x ${name} to batch.`, 'success');
    },

    updateCheckoutUI: function() {
        const box = document.getElementById('armory-checkout-box');
        const list = document.getElementById('checkout-list');
        const totalEl = document.getElementById('checkout-total-credits');
        
        if (this.cart.size === 0) {
            if (box) box.style.display = 'none';
            return;
        }
        
        if (box) box.style.display = 'block';
        if (list) list.innerHTML = '';
        
        let totalCost = 0;
        
        this.cart.forEach((data, key) => {
            const itemCost = data.cost * data.qty;
            totalCost += itemCost;
            
            if (list) {
                const div = document.createElement('div');
                div.className = 'checkout-item';
                div.style.cssText = 'font-size:0.85rem; padding:6px 0; border-bottom:1px solid rgba(255,255,255,0.1); display:flex; justify-content:space-between;';
                div.innerHTML = `<span>${data.name} x${data.qty}</span> <span class="text-accent">${itemCost.toLocaleString()}</span>`;
                list.appendChild(div);
            }
        });
        
        if (totalEl) totalEl.innerText = totalCost.toLocaleString();
    },

    // --- AJAX: Equip ---
    initEquipForms: function() {
        const forms = document.querySelectorAll('.equip-form');

        forms.forEach(form => {
            // Support both select-based (desktop) and button-based (mobile) equip
            const select = form.querySelector('.equip-select');
            
            if (select) {
                select.onchange = async () => {
                    const categoryKey = select.dataset.categoryKey;
                    const itemKey = select.value;
                    await this.submitEquip(form, categoryKey, itemKey);
                };
            }

            form.onsubmit = async (e) => {
                e.preventDefault();
                const categoryKey = form.querySelector('.dynamic-category-key').value;
                const itemKey = form.querySelector('.dynamic-item-key').value;
                await this.submitEquip(form, categoryKey, itemKey);
            };
        });
    },

    submitEquip: async function(form, categoryKey, itemKey) {
        const dynamicCat = form.querySelector('.dynamic-category-key');
        const dynamicItem = form.querySelector('.dynamic-item-key');
        if (dynamicCat) dynamicCat.value = categoryKey;
        if (dynamicItem) dynamicItem.value = itemKey;

        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;

        try {
            const formData = new FormData(form);
            const response = await fetch('/armory/equip', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showToast(result.message, 'success');
                // On mobile, we might want to reload the tab to show "Equipped" badge
                // but window.location.reload() is safer for now to ensure all state is sync'd
                setTimeout(() => window.location.reload(), 1000);
            } else {
                this.showToast(result.error || 'Failed to equip.', 'error');
            }

        } catch (error) {
            console.error('Equip Error:', error);
            this.showToast('Connection error.', 'error');
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    },

    showToast: function(message, type) {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.style.cssText = 'position:fixed; bottom:20px; right:20px; z-index:9999; display:flex; flex-direction:column; gap:10px;';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.innerText = message;
        const bg = type === 'success' ? '#4CAF50' : '#e53e3e';
        toast.style.cssText = `background:${bg}; color:#fff; padding:12px 24px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.3); font-family:sans-serif; font-size:0.9rem; opacity:0; transform:translateY(20px); transition:all 0.3s ease;`;

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
};

// Auto-init on load
document.addEventListener('DOMContentLoaded', () => {
    window.Armory.init();
});
