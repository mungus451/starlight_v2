/**
 * Starlight Dominion V2 - ROBUST Mobile JavaScript
 * This script is defensive and will not crash if elements are not found on a given page.
 * Logic is now isolated to prevent conflicts between pages.
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
        console.log('[Mobile JS] Dashboard page detected. Initializing dashboard modules.');
        
        // --- Avatar Lightbox ---
        const avatarTrigger = document.getElementById('avatar-trigger');
        const lightbox = document.getElementById('avatar-lightbox');
        const lightboxImg = document.getElementById('lightbox-img');
        const lightboxClose = lightbox ? lightbox.querySelector('.lightbox-close') : null;

        if (avatarTrigger && lightbox && lightboxImg && lightboxClose) {
            avatarTrigger.addEventListener('click', function(e) {
                e.preventDefault();
                const avatarElement = this.querySelector('img');
                if (avatarElement && avatarElement.src) {
                    lightboxImg.src = avatarElement.src;
                    lightbox.classList.add('active');
                }
            });
            const closeModal = () => lightbox.classList.remove('active');
            lightboxClose.addEventListener('click', closeModal);
            lightbox.addEventListener('click', (e) => (e.target === lightbox) && closeModal());
        }

        // --- AJAX Tabs for Dashboard ---
        const tabContent = document.getElementById('tab-content');
        if (tabContent) {
            dashboardTabContainer.addEventListener('click', async function(e) {
                e.preventDefault();
                const targetLink = e.target.closest('.tab-link');
                if (targetLink && !targetLink.classList.contains('active')) {
                    const tabName = targetLink.dataset.tab;
                    if (!tabName) return; // Safety check

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
        console.log('[Mobile JS] Structures page detected. Initializing structures tab module.');
        const tabContent = document.getElementById('tab-content');
        
        if (tabContent) {
            structureTabContainer.addEventListener('click', async function(e) {
                e.preventDefault();
                const targetLink = e.target.closest('.tab-link');

                if (targetLink && !targetLink.classList.contains('active')) {
                    const categorySlug = targetLink.dataset.category;
                    if (!categorySlug) return; // Safety check

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
                        }, 150);
                    } catch (error) {
                        tabContent.innerHTML = `<div class="error-message">${error.message}</div>`;
                    }
                }
            });
        }
    }

    console.log('[Mobile JS] Initialization complete.');
});