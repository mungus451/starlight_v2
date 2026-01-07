/**
 * Armory.js - V2 Tactical Configurator
 * Handles the "Configurator" UI for managing unit loadouts and requisitions.
 */

window.Armory = {
    data: null,

    init: function() {
        console.log('[Armory] Initializing Tactical Configurator...');
        this.data = window.ArmoryData;
        
        if (!this.data) {
            console.error('[Armory] Missing initialization data.');
            return;
        }

        this.initTabs();
        this.initSlots();
        this.initForms();
    },

    /**
     * Standard tab switching logic
     */
    initTabs: function() {
        const tabs = document.querySelectorAll('.tab-link');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const targetId = tab.dataset.tab;
                
                // Toggle active link
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                // Toggle active content
                document.querySelectorAll('.tab-content').forEach(c => {
                    c.classList.remove('active');
                });
                document.getElementById(targetId).classList.add('active');
                
                // Save state
                localStorage.setItem('armoryActiveTab', targetId);
            });
        });

        // Restore tab
        const savedTab = localStorage.getItem('armoryActiveTab');
        if (savedTab && document.getElementById(savedTab)) {
            const tabBtn = document.querySelector(`.tab-link[data-tab="${savedTab}"]`);
            if (tabBtn) tabBtn.click();
        }
    },

    /**
     * Initialize each slot card and its selection logic
     */
    initSlots: function() {
        const slotCards = document.querySelectorAll('.slot-card');
        
        slotCards.forEach(card => {
            const select = card.querySelector('.config-select');
            
            // Handle selection change
            select.addEventListener('change', () => {
                this.updateSlotInfo(card, select.value);
            });

            // Handle Unequip Click
            const unequipBtn = card.querySelector('.btn-config-unequip');
            if (unequipBtn) {
                unequipBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.submitUnequip(card);
                });
            }

            // Initial update for currently selected/equipped item
            this.updateSlotInfo(card, select.value);
        });
    },

    /**
     * Update the info area of a card based on the selected item
     */
    updateSlotInfo: function(card, itemKey) {
        const unitKey = card.dataset.unit;
        const categoryKey = card.dataset.category;
        
        // Find item data from manufacturing list (enriched with stats/costs)
        // Note: tieredItems is an object with tiers as keys. We need to find the item.
        let itemData = null;
        const tiers = this.data.manufacturing[unitKey] || {};
        
        for (const tier in tiers) {
            const items = tiers[tier];
            itemData = items.find(i => i.item_key === itemKey);
            if (itemData) break;
        }

        if (!itemData) return;

        // 1. Update Hidden Inputs in Forms
        card.querySelectorAll('.dynamic-item-key').forEach(input => {
            input.value = itemKey;
        });

        // 2. Update Stats
        const statsRow = card.querySelector('.item-stats-row');
        statsRow.innerHTML = '';
        if (itemData.stat_badges) {
            itemData.stat_badges.forEach(badge => {
                const span = document.createElement('span');
                span.className = `stat-pill ${badge.type}`;
                span.innerText = badge.label;
                statsRow.appendChild(span);
            });
        }

        // 3. Update Description
        const descText = card.querySelector('.item-description-text');
        descText.innerText = itemData.notes || 'No description available.';

        // 4. Update Costs
        const costDisplay = card.querySelector('.item-cost-display');
        costDisplay.innerText = parseInt(itemData.effective_cost).toLocaleString();

        const additionalCosts = card.querySelector('.additional-costs');
        additionalCosts.innerHTML = '';
        
        if (itemData.cost_crystals > 0) {
            additionalCosts.innerHTML += `<div class="flex-between" style="display:flex; justify-content:space-between;"><span>Crystals:</span><strong class="text-neon-blue">${parseInt(itemData.cost_crystals).toLocaleString()}</strong></div>`;
        }
        if (itemData.cost_dark_matter > 0) {
            additionalCosts.innerHTML += `<div class="flex-between" style="display:flex; justify-content:space-between;"><span>Dark Matter:</span><strong class="text-purple">${parseInt(itemData.cost_dark_matter).toLocaleString()}</strong></div>`;
        }

        // 5. Update Owned
        const ownedDisplay = card.querySelector('.item-owned-display');
        ownedDisplay.innerText = (this.data.inventory[itemKey] || 0).toLocaleString();

        // 6. Button States
        const buyBtn = card.querySelector('.btn-config-buy');
        const equipBtn = card.querySelector('.btn-config-equip');
        const unequipBtn = card.querySelector('.btn-config-unequip');
        
        // Disable Buy if Armory level too low
        if (!itemData.has_level) {
            buyBtn.disabled = true;
            buyBtn.title = `Requires Armory Level ${itemData.armory_level_req}`;
        } else {
            buyBtn.disabled = false;
            buyBtn.title = '';
        }

        // Disable Equip if not owned
        const ownedCount = this.data.inventory[itemKey] || 0;
        equipBtn.disabled = (ownedCount <= 0);
        
        // Check currently equipped status
        const currentlyEquipped = (this.data.loadouts[unitKey] || {})[categoryKey];
        
        // Unequip Button Logic
        if (currentlyEquipped) {
            unequipBtn.style.display = 'inline-block';
        } else {
            unequipBtn.style.display = 'none';
        }

        // Equip Button Logic
        if (currentlyEquipped === itemKey) {
            equipBtn.innerText = 'Equipped';
            equipBtn.classList.remove('btn-outline-info');
            equipBtn.classList.add('btn-success');
            equipBtn.disabled = true;
        } else {
            equipBtn.innerHTML = '<i class="fas fa-check-circle me-1"></i> Equip';
            equipBtn.classList.add('btn-outline-info');
            equipBtn.classList.remove('btn-success');
        }
    },

    /**
     * Handle Unequip Action
     */
    submitUnequip: async function(card) {
        const form = card.querySelector('.equip-config-form');
        const btn = card.querySelector('.btn-config-unequip');
        
        const originalContent = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        try {
            // Create FormData but override item_key to be empty
            const formData = new FormData(form);
            formData.set('item_key', ''); // Empty string = unequip

            const response = await fetch('/armory/equip', {
                method: 'POST',
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json' 
                },
                body: formData
            });
            
            const result = await response.json();
            if (result.success) {
                this.showToast('Item unequipped.', 'success');
                
                // Update Local Data
                const unitKey = form.querySelector('input[name="unit_key"]').value;
                const catKey = form.querySelector('input[name="category_key"]').value;
                
                // Clear loadout locally
                if (this.data.loadouts[unitKey]) {
                    this.data.loadouts[unitKey][catKey] = null;
                }

                // Update UI Text
                card.querySelector('.current-equipped-name').innerText = 'None';
                
                // Refresh Slot State (re-evaluates buttons)
                const currentSelection = card.querySelector('.config-select').value;
                this.updateSlotInfo(card, currentSelection);

            } else {
                this.showToast(result.error || 'Unequip failed', 'error');
            }
        } catch (err) {
            console.error(err);
            this.showToast('Network error occurred.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalContent;
        }
    },

    /**
     * Handle Form Submissions via AJAX
     */
    initForms: function() {
        // --- Purchase Form ---
        document.querySelectorAll('.manufacture-config-form').forEach(form => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = form.querySelector('button[type="submit"]');
                const originalContent = btn.innerHTML;
                
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

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
                        // Update inventory data and refresh view
                        this.data.inventory[result.item_key] = result.new_owned;
                        const card = form.closest('.slot-card');
                        
                        // Update Dropdown Text for the purchased item
                        const select = card.querySelector('.config-select');
                        const option = select.querySelector(`option[value="${result.item_key}"]`);
                        if (option) {
                            const itemName = option.text.split(' (Owned:')[0];
                            option.text = `${itemName} (Owned: ${result.new_owned.toLocaleString()})`;
                        }

                        this.updateSlotInfo(card, select.value);
                    } else {
                        this.showToast(result.error || 'Purchase failed', 'error');
                    }
                } catch (err) {
                    this.showToast('Network error occurred.', 'error');
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                }
            });
        });

        // --- Equip Form ---
        document.querySelectorAll('.equip-config-form').forEach(form => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = form.querySelector('button[type="submit"]');
                const originalContent = btn.innerHTML;
                
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                try {
                    const formData = new FormData(form);
                    const response = await fetch('/armory/equip', {
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
                        
                        // Update UI to show new equipped status
                        const unitKey = form.querySelector('input[name="unit_key"]').value;
                        const catKey = form.querySelector('input[name="category_key"]').value;
                        const itemKey = form.querySelector('input[name="item_key"]').value;
                        
                        if (!this.data.loadouts[unitKey]) this.data.loadouts[unitKey] = {};
                        this.data.loadouts[unitKey][catKey] = itemKey;
                        
                        const card = form.closest('.slot-card');
                        const itemName = card.querySelector('.config-select option:checked').text;
                        card.querySelector('.current-equipped-name').innerText = itemName;
                        
                        this.updateSlotInfo(card, itemKey);
                    } else {
                        this.showToast(result.error || 'Equip failed', 'error');
                    }
                } catch (err) {
                    this.showToast('Network error occurred.', 'error');
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                }
            });
        });
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
        const bg = type === 'success' ? '#2d3748' : '#e53e3e';
        const border = type === 'success' ? '#00f3ff' : '#feb2b2';
        toast.style.cssText = `background:${bg}; border-left: 4px solid ${border}; color:#fff; padding:12px 24px; border-radius:4px; box-shadow:0 4px 12px rgba(0,0,0,0.5); font-family:sans-serif; font-size:0.9rem; opacity:0; transform:translateY(20px); transition:all 0.3s ease;`;

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

// Start
document.addEventListener('DOMContentLoaded', () => {
    window.Armory.init();
});
