/**
 * Starlight Dominion V2 - ROBUST Mobile JavaScript
 * Logic is isolated to prevent conflicts between pages.
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('[Mobile JS] Initializing...');

    // --- CSRF Token Helper ---
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const injectCsrfTokens = (container) => {
        container.querySelectorAll('form').forEach(form => {
            const tokenInput = form.querySelector('input[name="csrf_token"]');
            if (tokenInput) {
                tokenInput.value = csrfToken;
            }
        });
    };

    // --- Feature 1: Main Navigation & Submenus (Runs on all pages) ---
    const hamburger = document.querySelector('.hamburger-menu');
    const nav = document.querySelector('.mobile-nav');
    const closeBtn = document.querySelector('.close-btn');
    if (hamburger && nav && closeBtn) {
        hamburger.addEventListener('click', () => nav.classList.add('active'));
        closeBtn.addEventListener('click', () => nav.classList.remove('active'));
    }
    document.querySelectorAll('.mobile-nav .has-submenu > a').forEach(toggle => {
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
        const tabContent = document.getElementById('tab-content');
        if (tabContent) {
            dashboardTabContainer.addEventListener('click', async function(e) {
                e.preventDefault();
                const targetLink = e.target.closest('.tab-link');
                if (targetLink && !targetLink.classList.contains('active')) {
                    const tabName = targetLink.dataset.tab;
                    if (!tabName) return;
                    document.querySelectorAll('#dashboard-tabs .tab-link').forEach(link => link.classList.remove('active'));
                    targetLink.classList.add('active');
                    tabContent.innerHTML = '<div class="loading-spinner"></div>';
                    try {
                        const response = await fetch(`/dashboard/mobile-tab/${tabName}`);
                        if (!response.ok) throw new Error(`Server error: ${response.status}`);
                        const data = await response.json();
                        if (!data.html) throw new Error('Invalid response from server.');
                        tabContent.style.opacity = 0;
                        setTimeout(() => {
                            tabContent.innerHTML = data.html;
                            tabContent.style.opacity = 1;
                        }, 150);
                    } catch (error) {
                        tabContent.innerHTML = `<div class="error-message">${error.message}</div>`;
                    }
                }
            });
        }
    }

    // --- Feature 3: Structures Page ---
    const structureTabContainer = document.getElementById('structure-tabs');
    if (structureTabContainer) {
        const tabContent = document.getElementById('tab-content');
        if (tabContent) {
            injectCsrfTokens(tabContent); // Initial injection
            structureTabContainer.addEventListener('click', async function(e) {
                e.preventDefault();
                const targetLink = e.target.closest('.tab-link');
                if (targetLink && !targetLink.classList.contains('active')) {
                    const categorySlug = targetLink.dataset.category;
                    if (!categorySlug) return;
                    document.querySelectorAll('#structure-tabs .tab-link').forEach(link => link.classList.remove('active'));
                    targetLink.classList.add('active');
                    tabContent.innerHTML = '<div class="loading-spinner"></div>';
                    try {
                        const response = await fetch(`/structures/mobile-tab/${categorySlug}`);
                        if (!response.ok) throw new Error(`Server error: ${response.status}`);
                        const data = await response.json();
                        if (!data.html) throw new Error('Invalid response from server.');
                        tabContent.style.opacity = 0;
                        setTimeout(() => {
                            tabContent.innerHTML = data.html;
                            tabContent.style.opacity = 1;
                            injectCsrfTokens(tabContent); // Inject after AJAX load
                        }, 150);
                    } catch (error) {
                        tabContent.innerHTML = `<div class="error-message">${error.message}</div>`;
                    }
                }
            });
        }
    }

    // --- Feature 4: Embassy Page (Show/Hide logic) ---
    const embassyTabContainer = document.getElementById('embassy-tabs');
    if(embassyTabContainer) {
        embassyTabContainer.addEventListener('click', e => {
            const targetLink = e.target.closest('.tab-link');
            if(!targetLink) return;
            e.preventDefault();
            if(targetLink.classList.contains('active')) return;

            const targetId = targetLink.dataset.tabTarget;
            if(targetId.startsWith('nested-')) return; // Ignore nested tabs

            document.querySelectorAll('#embassy-tabs > .tab-link').forEach(t => t.classList.remove('active'));
            targetLink.classList.add('active');

            document.querySelectorAll('#tab-content > .nested-tab-content').forEach(c => c.classList.remove('active'));
            document.getElementById(targetId)?.classList.add('active');
        });
    }

    // --- UNIVERSAL DELEGATED EVENT LISTENER FOR NESTED TABS ---
    document.body.addEventListener('click', function(e) {
        const nestedTabLink = e.target.closest('.nested-tabs .tab-link');
        if (nestedTabLink) {
            e.preventDefault();
            const container = nestedTabLink.closest('.nested-tabs-container');
            if (!container) return;
            container.querySelectorAll('.nested-tabs .tab-link').forEach(t => t.classList.remove('active'));
            container.querySelectorAll('.nested-tab-content').forEach(c => c.classList.remove('active'));
            nestedTabLink.classList.add('active');
            const targetId = nestedTabLink.dataset.tabTarget;
            document.getElementById(targetId)?.classList.add('active');
        }
    });

    console.log('[Mobile JS] Initialization complete.');
});
