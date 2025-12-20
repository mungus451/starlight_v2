/**
 * Armory.js
 * Handles tabs, batch manufacturing logic, and AJAX interactions.
 */

document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    initMaxButtons();
    initEquipForms();
    initBatchSystem();
});

// --- Tab Switching ---
function initTabs() {
    const links = document.querySelectorAll('.tab-link');
    const contents = document.querySelectorAll('.tab-content');

    links.forEach(link => {
        link.addEventListener('click', (e) => {
            links.forEach(l => l.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));
            link.classList.add('active');
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
            const reqOwned = parseInt(input.dataset.reqOwned || 999999999);
            
            const creditElem = document.getElementById('global-user-credits');
            const currentCredits = parseInt(creditElem.innerText.replace(/,/g, '')) || 0;

            if (cost <= 0) return;

            let maxQty = Math.floor(currentCredits / cost);
            if (reqKey && reqOwned < maxQty) {
                maxQty = reqOwned;
            }

            input.value = Math.max(0, maxQty);
        });
    });
}

// --- Batch System ---
const cart = new Map(); // key -> {qty, name, cost}

function initBatchSystem() {
    const forms = document.querySelectorAll('.manufacture-form');
    
    forms.forEach(form => {
        const input = form.querySelector('input[name="quantity"]');
        const btn = form.querySelector('button[type="submit"]');
        const itemKey = form.querySelector('input[name="item_key"]').value;
        
        // Get Item Name safely
        const card = form.closest('.item-card');
        const itemName = card ? card.querySelector('h4').innerText : 'Item';
        
        // Transform button
        btn.type = 'button';
        btn.innerText = 'Add to Batch';
        btn.classList.add('btn-add-batch');
        
        // Remove old listeners by replacing node? No, standard listener add is fine if we preventDefault or change type.
        // Changing type to 'button' prevents submit.
        
        btn.addEventListener('click', () => {
            const qty = parseInt(input.value) || 0;
            if (qty <= 0) {
                showToast('Please enter a quantity.', 'error');
                return;
            }
            
            const cost = parseInt(input.dataset.itemCost || 0);
            addToCart(itemKey, qty, itemName, cost, btn);
        });
    });

    // Checkout Logic
    const checkoutForm = document.getElementById('armory-checkout-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (cart.size === 0) return;
            
            const submitBtn = checkoutForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerText;
            submitBtn.disabled = true;
            submitBtn.innerText = 'Processing...';

            const items = [];
            cart.forEach((data, key) => {
                items.push({ item_key: key, quantity: data.qty });
            });

            try {
                const formData = new FormData();
                formData.append('csrf_token', checkoutForm.querySelector('input[name="csrf_token"]').value);
                formData.append('items', JSON.stringify(items));
                
                const response = await fetch('/armory/batch-manufacture', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.location.reload(); 
                } else {
                    showToast(result.error || 'Batch failed.', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerText = originalText;
                }
            } catch (err) {
                console.error(err);
                showToast('Network error.', 'error');
                submitBtn.disabled = false;
                submitBtn.innerText = originalText;
            }
        });
    }
    
    const cancelBtn = document.getElementById('btn-cancel-batch');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            cart.clear();
            updateCheckoutUI();
            document.querySelectorAll('.btn-add-batch').forEach(b => {
                b.innerText = 'Add to Batch';
                b.classList.remove('btn-secondary');
            });
        });
    }
}

function addToCart(key, qty, name, cost, btn) {
    cart.set(key, { qty, name, cost });
    
    btn.innerText = `Update Batch (${qty})`;
    btn.classList.add('btn-secondary');
    
    updateCheckoutUI();
}

function updateCheckoutUI() {
    const box = document.getElementById('armory-checkout-box');
    const list = document.getElementById('checkout-list');
    const totalEl = document.getElementById('checkout-total-credits');
    
    if (cart.size === 0) {
        if (box) box.style.display = 'none';
        return;
    }
    
    if (box) box.style.display = 'block';
    if (list) list.innerHTML = '';
    
    let totalCost = 0;
    
    cart.forEach((data, key) => {
        const itemCost = data.cost * data.qty;
        totalCost += itemCost;
        
        if (list) {
            const div = document.createElement('div');
            div.className = 'checkout-item';
            div.style.cssText = 'font-size:0.9em; padding:4px 0; border-bottom:1px solid rgba(255,255,255,0.1); display:flex; justify-content:space-between;';
            div.innerHTML = `<span>${data.name} x${data.qty}</span> <span>${itemCost.toLocaleString()}</span>`;
            list.appendChild(div);
        }
    });
    
    if (totalEl) totalEl.innerText = totalCost.toLocaleString();
}

// --- AJAX: Equip ---
function initEquipForms() {
    const selects = document.querySelectorAll('.equip-select');

    selects.forEach(select => {
        select.addEventListener('change', async (e) => {
            const form = select.closest('form');
            const categoryKey = select.dataset.categoryKey;
            const itemKey = select.value;

            form.querySelector('.dynamic-category-key').value = categoryKey;
            form.querySelector('.dynamic-item-key').value = itemKey;
            select.disabled = true;

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
                    showToast(result.message, 'success');
                } else {
                    showToast(result.error || 'Failed to equip.', 'error');
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

function showToast(message, type) {
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