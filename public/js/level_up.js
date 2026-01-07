/**
 * Level Up UI Logic
 * Handles the "Bio-Augmentation" stat allocation interface.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize State
    const form = document.getElementById('level-up-form');
    if (!form) return;

    // Get max points from the data attribute I will add to the view
    // or parse from the header display if that's the only source.
    // Ideally, the view should pass this via a data attribute.
    const headerDisplay = document.getElementById('header-points-display');
    const rawPoints = headerDisplay ? headerDisplay.innerText.replace(/,/g, '') : '0';
    const maxPoints = parseInt(rawPoints) || 0;

    const inputs = document.querySelectorAll('.stat-input');
    const totalAllocatedDisplay = document.getElementById('total-allocated');
    const allocationBar = document.getElementById('allocation-bar');
    const confirmBtn = document.getElementById('btn-confirm');

    // 2. Logic Function
    function updateTotals() {
        let totalUsed = 0;
        inputs.forEach(input => {
            let val = parseInt(input.value) || 0;
            if (val < 0) { 
                val = 0; 
                input.value = 0; 
            }
            totalUsed += val;
        });

        // Update UI Text
        if (totalAllocatedDisplay) totalAllocatedDisplay.innerText = totalUsed.toLocaleString();
        
        const remaining = maxPoints - totalUsed;
        if (headerDisplay) headerDisplay.innerText = remaining.toLocaleString();
        
        // Update Progress Bar
        if (allocationBar) {
            const pct = maxPoints > 0 ? Math.min(100, (totalUsed / maxPoints) * 100) : 0;
            allocationBar.style.width = pct + '%';
        }

        // Validation & Button State
        if (confirmBtn) {
            if (totalUsed > maxPoints) {
                totalAllocatedDisplay.classList.add('text-danger');
                totalAllocatedDisplay.classList.remove('text-warning');
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<i class="fas fa-times-circle"></i> Insufficient Points';
                confirmBtn.classList.add('btn-secondary');
                confirmBtn.classList.remove('btn-primary');
            } else if (totalUsed === 0) {
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Upgrades';
                confirmBtn.classList.add('btn-primary'); // Keep primary style but disabled
            } else {
                totalAllocatedDisplay.classList.remove('text-danger');
                totalAllocatedDisplay.classList.add('text-warning');
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Upgrades';
                confirmBtn.classList.add('btn-primary');
                confirmBtn.classList.remove('btn-secondary');
            }
        }
    }

    // 3. Event Listeners
    
    // Direct Input
    inputs.forEach(input => {
        input.addEventListener('input', updateTotals);
    });

    // Increment Buttons
    document.querySelectorAll('.btn-inc').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.target;
            const input = document.getElementById(targetId);
            if (!input) return;

            // Calculate current total dynamically
            let currentTotal = 0;
            inputs.forEach(i => currentTotal += (parseInt(i.value) || 0));
            
            if (currentTotal < maxPoints) {
                input.value = (parseInt(input.value) || 0) + 1;
                updateTotals();
            }
        });
    });

    // Decrement Buttons
    document.querySelectorAll('.btn-dec').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.target;
            const input = document.getElementById(targetId);
            if (!input) return;

            const val = parseInt(input.value) || 0;
            if (val > 0) {
                input.value = val - 1;
                updateTotals();
            }
        });
    });

    // 4. Initial Run
    updateTotals();
});
