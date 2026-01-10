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
        if (typeof StarlightUtils !== 'undefined') {
            StarlightUtils.initTabs({
                storageKey: 'armoryActiveTab'
            });
        }
    },

    /**
     * Initialize each slot card and its selection logic
     */
    initSlots: function() {
        const slotCards = document.querySelectorAll('.slot-card');
        
        slotCards.forEach(card => {
            const select = card.querySelector('.config-select');
            const qtyInput = card.querySelector('.config-qty');
            
            // Handle selection change
            select.addEventListener('change', () => {
                this.updateSlotInfo(card, select.value);
            });

            // Handle quantity change
            qtyInput.addEventListener('input', () => {
                this.updateTransactionPreview(card, qtyInput.value, select.value);
            });

            // Handle Unequip Click
            const unequipBtn = card.querySelector('.btn-config-unequip');
            if (unequipBtn) {
                unequipBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.submitUnequip(card);
                });
            }

            // Handle MAX Click
            const maxBtn = card.querySelector('.btn-config-max');
            if (maxBtn) {
                maxBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.calculateMax(card, select.value);
                    // Manually trigger preview update after MAX
                    this.updateTransactionPreview(card, qtyInput.value, select.value);
                });
            }

            // Initial update for currently selected/equipped item
            this.updateSlotInfo(card, select.value);
        });
    },

    /**
     * Update the live transaction preview
     */
    updateTransactionPreview: function(card, qty, itemKey) {
        const preview = card.querySelector('.transaction-preview');
        const costArea = preview.querySelector('.preview-costs');
        const itemData = this.getItemData(card, itemKey);
        
        if (!itemData || isNaN(qty) || qty <= 0) {
            preview.style.display = 'none';
            return;
        }

        const totalQty = parseInt(qty);
        const userRes = this.data.userResources;
        
        const totalCredits = itemData.effective_cost * totalQty;
        const totalCrystals = (itemData.cost_crystals || 0) * totalQty;
        const totalDarkMatter = (itemData.cost_dark_matter || 0) * totalQty;

        const hasCredits = userRes.credits >= totalCredits;
        const hasCrystals = userRes.crystals >= totalCrystals;
        const hasDarkMatter = userRes.darkMatter >= totalDarkMatter;

        // Prerequisite check
        let hasPrereq = true;
        let prereqDisplay = '';
        if (!itemData.is_tier_1 && itemData.prereq_key) {
            const ownedPrereq = this.data.inventory[itemData.prereq_key] || 0;
            hasPrereq = ownedPrereq >= totalQty;
            
            const colorClass = hasPrereq ? 'text-success' : 'text-danger';
            const icon = hasPrereq ? 'fa-check-circle' : 'fa-times-circle';
            
            prereqDisplay = `
                <div class="flex-between font-08 mb-1" style="display:flex; justify-content:space-between;">
                    <span class="text-muted">Requires ${itemData.prereq_name}:</span>
                    <span class="${colorClass}">
                        <i class="fas ${icon} me-1"></i> ${totalQty.toLocaleString()} / ${ownedPrereq.toLocaleString()}
                    </span>
                </div>
            `;
        }

        let html = '';
        
        // Credits Row
        html += `
            <div class="flex-between font-08 mb-1" style="display:flex; justify-content:space-between;">
                <span>Credits:</span>
                <span class="${hasCredits ? 'text-success' : 'text-danger'}">
                    <i class="fas ${hasCredits ? 'fa-check-circle' : 'fa-times-circle'} me-1"></i> ${totalCredits.toLocaleString()}
                </span>
            </div>
        `;

        // Crystals Row
        if (totalCrystals > 0) {
            html += `
                <div class="flex-between font-08 mb-1" style="display:flex; justify-content:space-between;">
                    <span>Crystals:</span>
                    <span class="${hasCrystals ? 'text-success' : 'text-danger'}">
                        <i class="fas ${hasCrystals ? 'fa-check-circle' : 'fa-times-circle'} me-1"></i> ${totalCrystals.toLocaleString()}
                    </span>
                </div>
            `;
        }

        // Dark Matter Row
        if (totalDarkMatter > 0) {
            html += `
                <div class="flex-between font-08 mb-1" style="display:flex; justify-content:space-between;">
                    <span>Dark Matter:</span>
                    <span class="${hasDarkMatter ? 'text-success' : 'text-danger'}">
                        <i class="fas ${hasDarkMatter ? 'fa-check-circle' : 'fa-times-circle'} me-1"></i> ${totalDarkMatter.toLocaleString()}
                    </span>
                </div>
            `;
        }

        html += prereqDisplay;

        // Final Status
        const canAfford = hasCredits && hasCrystals && hasDarkMatter && hasPrereq;
        const statusText = canAfford ? 'VALID REQUISITION' : 'INSUFFICIENT RESOURCES';
        const statusColor = canAfford ? 'text-success' : 'text-danger';
        
        html += `
            <div class="text-center mt-2 font-07 fw-bold ${statusColor}">
                ${statusText}
            </div>
        `;

        costArea.innerHTML = html;
        preview.style.display = 'block';

        // Update Buy Button State
        const buyBtn = card.querySelector('.btn-config-buy');
        if (itemData.has_level) {
            buyBtn.disabled = !canAfford;
        }
    },

    /**
     * Calculate and set MAX quantity
     */
    calculateMax: function(card, itemKey) {
        const itemData = this.getItemData(card, itemKey);
        if (!itemData) return;

        const userRes = this.data.userResources;
        
        // 1. Resource Limits
        const maxCredits = itemData.effective_cost > 0 
            ? Math.floor(userRes.credits / itemData.effective_cost) 
            : Number.MAX_SAFE_INTEGER;
            
        const maxCrystals = itemData.cost_crystals > 0 
            ? Math.floor(userRes.crystals / itemData.cost_crystals) 
            : Number.MAX_SAFE_INTEGER;
            
        const maxDarkMatter = itemData.cost_dark_matter > 0 
            ? Math.floor(userRes.darkMatter / itemData.cost_dark_matter) 
            : Number.MAX_SAFE_INTEGER;

        // 2. Prerequisite Limit
        let maxPrereq = Number.MAX_SAFE_INTEGER;
        
        if (!itemData.is_tier_1 && itemData.prereq_key) {
            // Check inventory for the prerequisite item
            maxPrereq = this.data.inventory[itemData.prereq_key] || 0;
        }

        // 3. Final Calculation
        const maxQty = Math.min(maxCredits, maxCrystals, maxDarkMatter, maxPrereq);
        
        // Update Input
        const input = card.querySelector('.config-qty');
        input.value = Math.max(0, maxQty);
    },

    /**
     * Helper to find item data
     */
    getItemData: function(card, itemKey) {
        const unitKey = card.dataset.unit;
        const tiers = this.data.manufacturing[unitKey] || {};
        
        for (const tier in tiers) {
            const items = tiers[tier];
            const found = items.find(i => i.item_key === itemKey);
            if (found) return found;
        }
        return null;
    },

    /**
     * Update the info area of a card based on the selected item
     */
    updateSlotInfo: function(card, itemKey) {
        const unitKey = card.dataset.unit;
        const categoryKey = card.dataset.category;
        
        const itemData = this.getItemData(card, itemKey);
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

        // 4. Update Costs & Prereq
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

        // Prerequisite Info
        const prereqInfo = card.querySelector('.prereq-info');
        if (!itemData.is_tier_1 && itemData.prereq_name) {
            const prereqOwned = this.data.inventory[itemData.prereq_key] || 0;
            prereqInfo.innerHTML = `<span>Requires: ${itemData.prereq_name}</span><span>(Owned: ${prereqOwned.toLocaleString()})</span>`;
            prereqInfo.style.display = 'flex';
        } else {
            prereqInfo.style.display = 'none';
        }

        // 5. Update Owned
        const ownedDisplay = card.querySelector('.item-owned-display');
        ownedDisplay.innerText = (this.data.inventory[itemKey] || 0).toLocaleString();

        // 6. Button States
        const buyBtn = card.querySelector('.btn-config-buy');
        const equipBtn = card.querySelector('.btn-config-equip');
        const unequipBtn = card.querySelector('.btn-config-unequip');
        const maxBtn = card.querySelector('.btn-config-max');
        
        // Disable Buy if Armory level too low
        if (!itemData.has_level) {
            buyBtn.disabled = true;
            buyBtn.title = `Requires Armory Level ${itemData.armory_level_req}`;
            maxBtn.disabled = true;
        } else {
            buyBtn.disabled = false;
            buyBtn.title = '';
            maxBtn.disabled = false;
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

        // 7. Update Live Preview
        const qtyInput = card.querySelector('.config-qty');
        this.updateTransactionPreview(card, qtyInput.value, itemKey);
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
                        
                        // Update inventory data
                        this.data.inventory[result.item_key] = result.new_owned;
                        
                        // 2. Update Credits/Resources locally (Important for subsequent MAX calcs!)
                        if (result.new_credits !== undefined) this.data.userResources.credits = result.new_credits;
                        if (result.new_crystals !== undefined) this.data.userResources.crystals = result.new_crystals;
                        if (result.new_dark_matter !== undefined) this.data.userResources.darkMatter = result.new_dark_matter;
                        
                        // Note: If the backend doesn't return new crystals/DM, we might need to manually deduct
                        // or request the backend to send them. For now we assume typical credit purchase.
                        
                        const card = form.closest('.slot-card');
                        
                        // Update Dropdown Text for the purchased item
                        const select = card.querySelector('.config-select');
                        const option = select.querySelector(`option[value="${result.item_key}"]`);
                        if (option) {
                            const itemName = option.text.split(' (Owned:')[0];
                            option.text = `${itemName} (Owned: ${result.new_owned.toLocaleString()})`;
                        }

                        // Update Prerequisite Source if this item is a prereq for something else?
                        // Complex to track cross-dependencies. For now, rely on refresh for cross-slot updates
                        // OR we could iterate all slots and update texts.
                        
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