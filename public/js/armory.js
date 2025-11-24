/**
 * Armory.js
 * Handles tabs, manufacturing logic, and AJAX interactions.
 */

document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    initMaxButtons();
    initManufactureForms();
    initEquipForms();
});

// --- Tab Switching ---
function initTabs() {
    const links = document.querySelectorAll('.tab-link');
    const contents = document.querySelectorAll('.tab-content');

    links.forEach(link => {
        link.addEventListener('click', (e) => {
            // Remove active class from all
            links.forEach(l => l.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));

            // Add active to clicked
            link.classList.add('active');
            
            // Show content
            const targetId = link.dataset.tab;
            document.getElementById(targetId).classList.add('active');
        });
    });
}

// --- Max Manufacture Buttons ---
function initMaxButtons() {
    const maxBtns = document.querySelectorAll('.btn-max-manufacture');
    
    maxBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.parentElement.querySelector('input.manufacture-amount');
            if (!input) return;

            const cost = parseInt(input.dataset.itemCost || 0);
            const reqKey = input.dataset.prereqKey || null;
            const reqOwned = parseInt(input.dataset.reqOwned || 999999999); // If no req, assume infinite
            
            // Get current global credits (dynamically updated)
            const creditElem = document.getElementById('global-user-credits');
            // Remove commas for parsing
            const currentCredits = parseInt(creditElem.innerText.replace(/,/g, '')) || 0;

            if (cost <= 0) return;

            // Calculate max based on credits
            let maxQty = Math.floor(currentCredits / cost);

            // Calculate max based on prerequisites (if any)
            if (reqKey && reqOwned < maxQty) {
                maxQty = reqOwned;
            }

            // Update input
            input.value = Math.max(0, maxQty);
        });
    });
}

// --- AJAX: Manufacture ---
function initManufactureForms() {
    const forms = document.querySelectorAll('.manufacture-form');

    forms.forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerText;
            submitBtn.disabled = true;
            submitBtn.innerText = '...';

            try {
                const formData = new FormData(form);
                
                const response = await fetch('/armory/manufacture', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // 1. Show Success Toast
                    showToast(result.message, 'success');

                    // 2. Update Credits Globally
                    updateCreditsDisplay(result.new_credits);

                    // 3. Update Item Owned Count
                    // Find all indicators for this item (could be in multiple places?)
                    const itemCounters = document.querySelectorAll(`strong[data-inventory-key="${result.item_key}"]`);
                    itemCounters.forEach(el => {
                        el.innerText = result.new_owned.toLocaleString();
                        // Visual flash effect
                        el.style.color = '#4CAF50'; // Green flash
                        setTimeout(() => el.style.color = '', 1000);
                    });

                    // 4. Update Input Data Attributes (for Max calc)
                    // Any input that uses this item as a prerequisite needs its data-req-owned updated
                    const dependentInputs = document.querySelectorAll(`input[data-prereq-key="${result.item_key}"]`);
                    dependentInputs.forEach(inp => {
                        inp.dataset.reqOwned = result.new_owned;
                    });
                    
                    // Update current owned on the input itself (if we were upgrading this item further)
                    const selfInput = form.querySelector('input.manufacture-amount');
                    if(selfInput) selfInput.dataset.currentOwned = result.new_owned;

                    // 5. Reset Form
                    form.reset();

                } else {
                    showToast(result.error || 'An error occurred.', 'error');
                }

            } catch (error) {
                console.error('Error:', error);
                showToast('Network error. Please try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerText = originalText;
            }
        });
    });
}

// --- AJAX: Equip ---
function initEquipForms() {
    const selects = document.querySelectorAll('.equip-select');

    selects.forEach(select => {
        select.addEventListener('change', async (e) => {
            const form = select.closest('form');
            const categoryKey = select.dataset.categoryKey;
            const itemKey = select.value;

            // Populate hidden fields needed for the controller logic
            form.querySelector('.dynamic-category-key').value = categoryKey;
            form.querySelector('.dynamic-item-key').value = itemKey;

            // Optional: Disable select while processing
            select.disabled = true;

            try {
                const formData = new FormData(form);
                // The controller expects unit_key, which is already in the form as hidden input

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
                    showToast(result.message, 'success');
                } else {
                    showToast(result.error || 'Failed to equip.', 'error');
                    // Revert selection on error? Complex to track previous val, 
                    // simpler to just let user retry.
                }

            } catch (error) {
                console.error('Equip Error:', error);
                showToast('Connection error.', 'error');
            } finally {
                select.disabled = false;
            }
        });
    });
}

// --- Helpers ---

function updateCreditsDisplay(newAmount) {
    const el = document.getElementById('global-user-credits');
    if (el) {
        el.innerText = newAmount.toLocaleString();
        el.dataset.credits = newAmount;
    }
}

/**
 * Creates a temporary toast notification
 * @param {string} message 
 * @param {string} type 'success' | 'error'
 */
function showToast(message, type) {
    // Create container if not exists
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.position = 'fixed';
        container.style.bottom = '20px';
        container.style.right = '20px';
        container.style.zIndex = '9999';
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '10px';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.innerText = message;
    
    // Styles
    const bg = type === 'success' ? '#4CAF50' : '#e53e3e';
    toast.style.background = bg;
    toast.style.color = '#fff';
    toast.style.padding = '12px 24px';
    toast.style.borderRadius = '8px';
    toast.style.boxShadow = '0 4px 12px rgba(0,0,0,0.3)';
    toast.style.fontFamily = 'sans-serif';
    toast.style.fontSize = '0.9rem';
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(20px)';
    toast.style.transition = 'all 0.3s ease';

    container.appendChild(toast);

    // Animate in
    requestAnimationFrame(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    });

    // Remove after 3s
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}