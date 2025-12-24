/**
 * Starlight Dominion V2 - ROBUST Mobile JavaScript
 * Logic is isolated to prevent conflicts between pages.
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('[Mobile JS] Initializing...');

    // --- Feature 1: Main Navigation (Runs on all pages) ---
    const hamburger = document.querySelector('.hamburger-menu');
    const nav = document.querySelector('.mobile-nav');
    const closeBtn = document.querySelector('.close-btn');

    if (hamburger && nav && closeBtn) {
        hamburger.addEventListener('click', () => nav.classList.add('active'));
        closeBtn.addEventListener('click', () => nav.classList.remove('active'));
    }

    const submenuToggles = document.querySelectorAll('.mobile-nav .has-submenu > a');
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', e => {
            e.preventDefault();
            const parentLi = toggle.parentElement;
            const submenu = toggle.nextElementSibling;
            if (parentLi && submenu) {
                parentLi.classList.toggle('active');
                submenu.style.maxHeight = parentLi.classList.contains('active') ? submenu.scrollHeight + 'px' : '0';
            }
        });
    });

    // --- Feature 2: Dashboard Page ---
    const dashboardTabContainer = document.getElementById('dashboard-tabs');
    if (dashboardTabContainer) {
        // ... (Dashboard-specific logic is correct and remains unchanged)
    }

    // --- Feature 3: Structures Page ---
    const structureTabContainer = document.getElementById('structure-tabs');
    if (structureTabContainer) {
        // ... (Structures AJAX logic is correct and remains unchanged)
    }

    // --- UNIVERSAL DELEGATED EVENT LISTENER FOR TABS ---
    document.body.addEventListener('click', function(e) {
        const tabLink = e.target.closest('.tab-link');
        if (!tabLink) return;

        const container = tabLink.closest('.nested-tabs-container');
        if (!container) return;

        e.preventDefault();
        
        // Deactivate all tabs and content within this specific container
        container.querySelectorAll('.tab-link').forEach(t => t.classList.remove('active'));
        container.querySelectorAll('.nested-tab-content').forEach(c => c.classList.remove('active'));
        
        // Activate the clicked tab and its corresponding content
        tabLink.classList.add('active');
        const targetId = tabLink.dataset.tabTarget;
        const targetContent = document.getElementById(targetId);
        if (targetContent) {
            targetContent.classList.add('active');
        }
    });

    console.log('[Mobile JS] Initialization complete.');
});
