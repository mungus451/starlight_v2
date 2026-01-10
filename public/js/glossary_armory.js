/**
 * glossary_armory.js
 * 
 * Handles the dynamic rendering of the Armory tab in the Glossary.
 * Transforms static data into an interactive, filterable database.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize
    const container = document.getElementById('armory-dynamic-container');
    if (!container || !window.armoryData) return;

    const state = {
        unit: 'soldier', // Default
        category: 'all'
    };

    // 2. Render Functions
    function render() {
        container.innerHTML = ''; // Clear current

        // A. Unit Navigation (Top Level)
        const unitNav = document.createElement('div');
        unitNav.className = 'armory-unit-nav';
        
        Object.keys(window.armoryData).forEach(unitKey => {
            const unit = window.armoryData[unitKey];
            const btn = document.createElement('button');
            btn.className = `armory-unit-btn ${state.unit === unitKey ? 'active' : ''}`;
            btn.innerHTML = getUnitIcon(unitKey) + ` <span>${unitKey.toUpperCase()}</span>`;
            btn.onclick = () => {
                state.unit = unitKey;
                state.category = 'all'; // Reset category on unit switch
                render();
            };
            unitNav.appendChild(btn);
        });
        container.appendChild(unitNav);

        // B. Category Filters (Second Level)
        const currentUnitData = window.armoryData[state.unit];
        const categories = currentUnitData.categories;
        
        const catNav = document.createElement('div');
        catNav.className = 'armory-cat-nav';

        // "All" Button
        const allBtn = document.createElement('button');
        allBtn.className = `armory-cat-pill ${state.category === 'all' ? 'active' : ''}`;
        allBtn.innerText = 'ALL';
        allBtn.onclick = () => { state.category = 'all'; render(); };
        catNav.appendChild(allBtn);

        Object.keys(categories).forEach(catKey => {
            const cat = categories[catKey];
            const btn = document.createElement('button');
            btn.className = `armory-cat-pill ${state.category === catKey ? 'active' : ''}`;
            btn.innerText = cat.title.replace(/\(.*\)/, '').trim(); // Clean up title (remove parens)
            btn.onclick = () => { state.category = catKey; render(); };
            catNav.appendChild(btn);
        });
        container.appendChild(catNav);

        // C. Item Grid (Content)
        const grid = document.createElement('div');
        grid.className = 'armory-item-grid';

        let itemsToShow = [];
        if (state.category === 'all') {
            // Flatten all categories
            Object.values(categories).forEach(cat => {
                itemsToShow = itemsToShow.concat(Object.values(cat.items));
            });
        } else {
            itemsToShow = Object.values(categories[state.category].items);
        }

        if (itemsToShow.length === 0) {
            grid.innerHTML = '<div class="text-muted text-center p-5">No items found in this category.</div>';
        } else {
            itemsToShow.forEach(item => {
                const card = createItemCard(item);
                grid.appendChild(card);
            });
        }

        container.appendChild(grid);
    }

    // 3. Helper: Create Card HTML
    function createItemCard(item) {
        const el = document.createElement('div');
        el.className = 'armory-db-card';
        
        // Stats Formatting
        let statsHtml = '';
        if (item.attack) statsHtml += `<span class="stat-badge attack"><i class="fas fa-crosshairs"></i> ${item.attack}</span>`;
        if (item.defense) statsHtml += `<span class="stat-badge defense"><i class="fas fa-shield-alt"></i> ${item.defense}</span>`;
        if (item.credit_bonus) statsHtml += `<span class="stat-badge eco"><i class="fas fa-coins"></i> +${item.credit_bonus}%</span>`;

        // Cost Formatting
        let costHtml = `<span class="cost-val credits">${parseInt(item.cost).toLocaleString()} Cr</span>`;
        if (item.cost_crystals) costHtml += `<span class="cost-val crystals">${parseInt(item.cost_crystals).toLocaleString()} 💎</span>`;
        if (item.cost_dark_matter) costHtml += `<span class="cost-val dm">${parseInt(item.cost_dark_matter).toLocaleString()} DM</span>`;

        // Requirements
        let reqHtml = '';
        if (item.armory_level_req) reqHtml += `<span class="req-badge">Lvl ${item.armory_level_req}</span>`;
        if (item.requires) {
            // Try to find the name of the required item? Or just ID.
            // For glossaries, seeing the ID is "okay" but a formatted name is better.
            // We'll just show "Requires Pre-req" or similar to keep it simple, or formatted key.
            const reqName = item.requires.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            reqHtml += `<span class="req-badge text-warning">Req: ${reqName}</span>`;
        }

        el.innerHTML = `
            <div class="adb-header">
                <h4 class="adb-title">${item.name}</h4>
                <div class="adb-stats">${statsHtml}</div>
            </div>
            <div class="adb-body">
                <p class="adb-desc">${item.notes || 'No description available.'}</p>
                <div class="adb-meta">
                    <div class="adb-costs">${costHtml}</div>
                    <div class="adb-reqs">${reqHtml}</div>
                </div>
            </div>
        `;
        return el;
    }

    // 4. Helper: Icons
    function getUnitIcon(unit) {
        const map = {
            'soldier': '<i class="fas fa-crosshairs"></i>',
            'guard': '<i class="fas fa-shield-alt"></i>',
            'spy': '<i class="fas fa-user-secret"></i>',
            'sentry': '<i class="fas fa-eye"></i>',
            'worker': '<i class="fas fa-hammer"></i>'
        };
        return map[unit] || '<i class="fas fa-cog"></i>';
    }

    // Start
    render();
});
