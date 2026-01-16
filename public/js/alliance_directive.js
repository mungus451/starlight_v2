/**
 * alliance_directive.js
 * Handles the "Set Directive" modal interaction.
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Alliance Directive JS loaded');
    const btnSet = document.getElementById('btn-set-directive');
    const modal = document.getElementById('directive-modal');
    
    if (!btnSet) console.log('Set Directive Button NOT found');
    if (!modal) console.log('Directive Modal NOT found');

    const closeBtns = document.querySelectorAll('.close-modal');
    const grid = document.getElementById('directive-options-grid');
    const btnConfirm = document.getElementById('btn-confirm-directive');
    
    let selectedType = null;

    if (!btnSet || !modal) return;

    // Open Modal
    btnSet.addEventListener('click', (e) => {
        e.preventDefault(); // Prevent any default behavior
        console.log('Set Directive Clicked');
        modal.style.display = 'flex';
        fetchOptions();
    });

    // Close Modal
    closeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            modal.style.display = 'none';
        });
    });

    // Fetch Options
    async function fetchOptions() {
        grid.innerHTML = '<div class="text-center p-3">Loading strategic data...</div>';
        
        try {
            const res = await fetch('/alliance/directive/options');
            const data = await res.json();
            
            if (data.options) {
                renderGrid(data.options);
            } else {
                grid.innerHTML = '<div class="text-danger">Failed to load directive options.</div>';
            }
        } catch (e) {
            console.error(e);
            grid.innerHTML = '<div class="text-danger">Connection error.</div>';
        }
    }

    // Render Grid
    function renderGrid(options) {
        grid.innerHTML = '';
        
        for (const [key, opt] of Object.entries(options)) {
            const card = document.createElement('div');
            card.className = 'directive-card';
            card.dataset.type = key;
            card.innerHTML = `
                <div class="card-icon"><i class="fas ${opt.icon}"></i></div>
                <div class="card-content">
                    <h4>${opt.name}</h4>
                    <p>${opt.desc}</p>
                    <div class="card-stats">
                        <span>Current: ${formatNum(opt.current)}</span>
                        <span class="text-neon-blue">Target: ${formatNum(opt.target)}</span>
                    </div>
                </div>
            `;
            
            card.addEventListener('click', () => {
                // Deselect all
                document.querySelectorAll('.directive-card').forEach(c => c.classList.remove('selected'));
                // Select this
                card.classList.add('selected');
                selectedType = key;
                btnConfirm.disabled = false;
            });
            
            grid.appendChild(card);
        }
    }

    // Confirm Logic
    btnConfirm.addEventListener('click', async () => {
        if (!selectedType) return;
        
        btnConfirm.textContent = 'Transmitting Orders...';
        btnConfirm.disabled = true;
        
        try {
            const res = await fetch('/alliance/directive/set', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type: selectedType })
            });
            const data = await res.json();
            
            if (data.message) {
                location.reload(); // Refresh to update sidebar
            } else {
                alert('Error: ' + (data.error || 'Unknown'));
                btnConfirm.disabled = false;
                btnConfirm.textContent = 'Confirm Orders';
            }
        } catch (e) {
            alert('System Error.');
            btnConfirm.disabled = false;
            btnConfirm.textContent = 'Confirm Orders';
        }
    });

    function formatNum(n) {
        return new Intl.NumberFormat().format(n);
    }
});
