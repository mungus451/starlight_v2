/**
 * Armory.js
 * Handles tabs, batch manufacturing logic, and AJAX interactions.
 * Refactored for dynamic re-initialization on Mobile AJAX loads.
 */

window.Armory = {
    cart: new Map(), // key -> {qty, name, cost}
    ARMORY_TAB_KEY: 'armoryActiveTab',
    ACCORDION_STATE_KEY: 'armoryAccordionState',

    init: function() {
        console.log('[Armory] Initializing features...');
        this.initTabs();
        this.initMaxButtons();
        this.initUpgradeButtons();
        this.initEquipForms();
        this.initBatchSystem();
        this.initAccordions(); // Move to end to prevent blocking other features
    },

    // --- Accordion State Persistence ---
    initAccordions: function() {
        const accordions = document.querySelectorAll('.tier-accordion');
        
        accordions.forEach(accordion => {
            accordion.addEventListener('toggle', (e) => {
                const tabContent = e.target.closest('.tab-content');
                if (!tabContent) return;

                const tabId = tabContent.id;
                const tier = e.target.dataset.tier;
                const isOpen = e.target.open;

                let allStates = {};
                try {
                    allStates = JSON.parse(localStorage.getItem(this.ACCORDION_STATE_KEY) || '{}');
                } catch (e) {
                    console.warn('Invalid accordion state, resetting.');
                }
                
                if (!allStates[tabId]) {
                    allStates[tabId] = {};
                }
                allStates[tabId][`tier-${tier}`] = isOpen;
                
                try {
                    localStorage.setItem(this.ACCORDION_STATE_KEY, JSON.stringify(allStates));
                } catch (e) {
                    console.error('Failed to save accordion state:', e);
                }
            });
        });
    },

    restoreAccordionState: function(tabId) {
        let allStates = {};
        try {
            allStates = JSON.parse(localStorage.getItem(this.ACCORDION_STATE_KEY) || '{}');
        } catch (e) {
            console.warn('Invalid accordion state, resetting.');
            return;
        }

        const tabStates = allStates[tabId];
        if (!tabStates) return;

        const tabContent = document.getElementById(tabId);
        if (!tabContent) return;
        
        const accordions = tabContent.querySelectorAll('.tier-accordion');
        accordions.forEach(accordion => {
            const tier = accordion.dataset.tier;
            if (tabStates[`tier-${tier}`] !== undefined) {
                accordion.open = tabStates[`tier-${tier}`];
            }
        });
    },

    // --- Max Manufacture Buttons (Tier 1 - Resource Only) ---
    initMaxButtons: function() {
        document.querySelectorAll('.btn-max-manufacture').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const container = btn.closest('.form-group');
                const input = container.querySelector('input.manufacture-amount');
                if (!input) return;

                // Get costs
                const creditCost = parseInt(input.dataset.itemCost || 0);
                const crystalCost = parseInt(input.dataset.costCrystals || 0);
                const darkMatterCost = parseInt(input.dataset.costDarkMatter || 0);

                // Get user's current resources
                const userCredits = parseInt(document.getElementById('global-user-credits')?.dataset.credits || 0);
                const userCrystals = parseInt(document.getElementById('global-user-crystals')?.dataset.crystals || 0);
                const userDarkMatter = parseInt(document.getElementById('global-user-dark-matter')?.dataset.darkMatter || 0);

                let maxByCredits = (creditCost > 0) ? Math.floor(userCredits / creditCost) : Number.MAX_SAFE_INTEGER;
                let maxByCrystals = (crystalCost > 0) ? Math.floor(userCrystals / crystalCost) : Number.MAX_SAFE_INTEGER;
                let maxByDarkMatter = (darkMatterCost > 0) ? Math.floor(userDarkMatter / darkMatterCost) : Number.MAX_SAFE_INTEGER;

                const maxQty = Math.min(maxByCredits, maxByCrystals, maxByDarkMatter);

                input.value = Math.max(0, maxQty);
            });
        });
    },

    // --- Max Upgrade Buttons (Tier 2+ - Prereq + Resource) ---
    initUpgradeButtons: function() {
        const upgradeBtns = document.querySelectorAll('.btn-max-upgrade');
        console.log(`[Armory] Found ${upgradeBtns.length} upgrade buttons.`);

        upgradeBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                console.log('[Armory] Max Upgrade clicked.');

                const container = btn.closest('.form-group');
                const input = container.querySelector('input.manufacture-amount');
                if (!input) {
                    console.error('[Armory] Input not found relative to button.');
                    return;
                }

                // 1. Get Prerequisite Limit (Primary Constraint)
                const reqOwnedRaw = input.dataset.prereqOwned;
                const reqOwned = parseInt(reqOwnedRaw || 0);
                console.log(`[Armory] Prereq Owned: Raw="${reqOwnedRaw}", Parsed=${reqOwned}`);

                // 2. Get Resource Limits
                const creditCost = parseInt(input.dataset.itemCost || 0);
                const crystalCost = parseInt(input.dataset.costCrystals || 0);
                const darkMatterCost = parseInt(input.dataset.costDarkMatter || 0);
                
                console.log(`[Armory] Costs: Credits=${creditCost}, Crystals=${crystalCost}, DM=${darkMatterCost}`);

                const userCreditsEl = document.getElementById('global-user-credits');
                const userCrystalsEl = document.getElementById('global-user-crystals');
                const userDarkMatterEl = document.getElementById('global-user-dark-matter');

                const userCredits = parseInt(userCreditsEl?.dataset.credits || 0);
                const userCrystals = parseInt(userCrystalsEl?.dataset.crystals || 0);
                const userDarkMatter = parseInt(userDarkMatterEl?.dataset.darkMatter || 0);

                console.log(`[Armory] User Resources: Credits=${userCredits}, Crystals=${userCrystals}, DM=${userDarkMatter}`);

                let maxByCredits = (creditCost > 0) ? Math.floor(userCredits / creditCost) : Number.MAX_SAFE_INTEGER;
                let maxByCrystals = (crystalCost > 0) ? Math.floor(userCrystals / crystalCost) : Number.MAX_SAFE_INTEGER;
                let maxByDarkMatter = (darkMatterCost > 0) ? Math.floor(userDarkMatter / darkMatterCost) : Number.MAX_SAFE_INTEGER;

                console.log(`[Armory] Max Limits: Credits=${maxByCredits}, Crystals=${maxByCrystals}, DM=${maxByDarkMatter}`);

                // 3. Calculate Final Max (Lowest of Prereq or Resources)
                const maxQty = Math.min(reqOwned, maxByCredits, maxByCrystals, maxByDarkMatter);
                console.log(`[Armory] Final Max Qty: ${maxQty}`);

                input.value = Math.max(0, maxQty);
            });
        });
    },

    // --- Batch and Buy Now System ---
    initBatchSystem: function() {
        const forms = document.querySelectorAll('.manufacture-form');
        
        forms.forEach(form => {
            const input = form.querySelector('input[name="quantity"]');
            const itemKey = form.querySelector('input[name="item_key"]').value;
            const card = form.closest('.item-card');
            const itemName = card ? (card.querySelector('h4')?.innerText || 'Item') : 'Item';

            // --- "Add to Batch" Button ---
            const batchBtn = form.querySelector('.btn-add-to-batch');
            if (batchBtn) {
                // Check if already in cart on init
                if (this.cart.has(itemKey)) {
                    const existing = this.cart.get(itemKey);
                    batchBtn.innerText = `In Batch (${existing.qty})`;
                    batchBtn.classList.add('btn-secondary');
                } else {
                    batchBtn.innerText = 'Add to Batch';
                    batchBtn.classList.remove('btn-secondary');
                }

                batchBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const qty = parseInt(input.value) || 0;
                    if (qty <= 0) {
                        this.showToast('Please enter a quantity.', 'error');
                        return;
                    }
                    
                    const cost = parseInt(input.dataset.itemCost || 0);
                    const costCrystals = parseInt(input.dataset.costCrystals || 0);
                    const costDarkMatter = parseInt(input.dataset.costDarkMatter || 0);

                    this.addToCart(itemKey, qty, itemName, cost, costCrystals, costDarkMatter, batchBtn);
                });
            }

            // --- "Buy Now" Form Submission ---
            form.addEventListener('submit', async (e) => {
                e.preventDefault(); // Always prevent default to handle via AJAX
                const btn = form.querySelector('.btn-buy-now');
                const originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = 'Buying...';

                try {
                    const formData = new FormData(form);
                    const response = await fetch('/armory/manufacture', {
                        method: 'POST',
                        headers: { 
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json' 
                        },
                        body: formData
                    });
                    const result = await response.json();
                    if (result.success) {
                        this.showToast(result.message, 'success');
                        setTimeout(() => window.location.reload(), 1000); // Reload to see changes
                    } else {
                        this.showToast(result.error || 'Purchase failed', 'error');
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    }
                } catch (err) {
                    console.error(err);
                    this.showToast('A network error occurred.', 'error');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            });
        });

        // --- Checkout Logic (only needs to be initialized once) ---
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

                const items = Array.from(this.cart.entries()).map(([key, data]) => ({
                    item_key: key,
                    quantity: data.qty
                }));

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
                document.querySelectorAll('.btn-add-to-batch').forEach(b => {
                    b.innerText = 'Add to Batch';
                    b.classList.remove('btn-secondary');
                });
            };
        }
    },

    addToCart: function(key, qty, name, cost, costCrystals, costDarkMatter, btn) {
        this.cart.set(key, { qty, name, cost, costCrystals, costDarkMatter });
        
        btn.innerText = `In Batch (${qty})`;
        btn.classList.add('btn-secondary');
        
        this.updateCheckoutUI();
        this.showToast(`Added ${qty}x ${name} to batch.`, 'success');
    },

    updateCheckoutUI: function() {
        const box = document.getElementById('armory-checkout-box');
        const list = document.getElementById('checkout-list');
        const totalCreditsEl = document.getElementById('checkout-total-credits');
        const totalCrystalsEl = document.getElementById('checkout-total-crystals');
        const totalDarkMatterEl = document.getElementById('checkout-total-dark-matter');
        
        if (this.cart.size === 0) {
            if (box) box.style.display = 'none';
            return;
        }
        
        if (box) box.style.display = 'block';
        if (list) list.innerHTML = '';
        
        let totalCredits = 0;
        let totalCrystals = 0;
        let totalDarkMatter = 0;
        
        this.cart.forEach((data, key) => {
            const itemCreditCost = data.cost * data.qty;
            const itemCrystalCost = data.costCrystals * data.qty;
            const itemDarkMatterCost = data.costDarkMatter * data.qty;

            totalCredits += itemCreditCost;
            totalCrystals += itemCrystalCost;
            totalDarkMatter += itemDarkMatterCost;
            
            if (list) {
                const div = document.createElement('div');
                div.className = 'checkout-item';
                div.style.cssText = 'font-size:0.85rem; padding:6px 0; border-bottom:1px solid rgba(255,255,255,0.1); display:flex; justify-content:space-between;';
                div.innerHTML = `<span>${data.name} x${data.qty}</span> <span class="text-accent">${itemCreditCost.toLocaleString()}</span>`;
                list.appendChild(div);
            }
        });
        
        if (totalCreditsEl) totalCreditsEl.innerText = totalCredits.toLocaleString();
        if (totalCrystalsEl) totalCrystalsEl.innerText = totalCrystals.toLocaleString();
        if (totalDarkMatterEl) totalDarkMatterEl.innerText = totalDarkMatter.toLocaleString();
    },

    // --- AJAX: Equip ---
    initEquipForms: function() {
        const forms = document.querySelectorAll('.equip-form');

        forms.forEach(form => {
            // Support both select-based (desktop) and button-based (mobile) equip
            const selects = form.querySelectorAll('.equip-select');
            
            selects.forEach(select => {
                select.onchange = async () => {
                    const categoryKey = select.dataset.categoryKey;
                    const itemKey = select.value;
                    await this.submitEquip(form, categoryKey, itemKey);
                };
            });

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
