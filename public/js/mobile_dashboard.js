/**
 * Starlight Dominion V2 - ROBUST Mobile JavaScript
 * This script is defensive and will not crash if elements are not found on a given page.
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('[Mobile JS] Initializing...');

    // --- Feature 1: Main Navigation ---
    // This should run on every page.
    const hamburger = document.querySelector('.hamburger-menu');
    const nav = document.querySelector('.mobile-nav');
    const closeBtn = document.querySelector('.close-btn');

    if (hamburger && nav && closeBtn) {
        console.log('[Mobile JS] Main navigation module loaded.');
        hamburger.addEventListener('click', () => {
            nav.classList.add('active');
        });
        closeBtn.addEventListener('click', () => {
            nav.classList.remove('active');
        });
    } else {
        console.warn('[Mobile JS] Main navigation elements not found on this page.');
    }

    // --- Feature 1a: Submenu Toggling ---
    const submenuToggles = document.querySelectorAll('.mobile-nav .has-submenu > a');
    if (submenuToggles.length > 0) {
        console.log('[Mobile JS] Submenu module loaded.');
        submenuToggles.forEach(toggle => {
            toggle.addEventListener('click', e => {
                e.preventDefault();
                const parentLi = toggle.parentElement;
                const submenu = toggle.nextElementSibling;

                if (parentLi && submenu) {
                    parentLi.classList.toggle('active');
                    if (parentLi.classList.contains('active')) {
                        submenu.style.maxHeight = submenu.scrollHeight + 'px';
                    } else {
                        submenu.style.maxHeight = '0';
                    }
                }
            });
        });
    }

    // --- Feature 2: Dashboard - Avatar Lightbox ---
    // This should only run on the dashboard.
    const avatarTrigger = document.getElementById('avatar-trigger');
    if (avatarTrigger) {
        const lightbox = document.getElementById('avatar-lightbox');
        const lightboxImg = document.getElementById('lightbox-img');
        const lightboxClose = document.querySelector('.lightbox-close');

        if (lightbox && lightboxImg && lightboxClose) {
            console.log('[Mobile JS] Avatar lightbox module loaded.');
            avatarTrigger.addEventListener('click', function(e) {
                e.preventDefault();
                const avatarElement = this.querySelector('img');
                if (avatarElement && avatarElement.src) {
                    lightboxImg.src = avatarElement.src;
                    lightbox.classList.add('active');
                } else {
                    console.error('[Mobile JS] Lightbox error: Avatar source image not found.');
                }
            });

            const closeModal = () => lightbox.classList.remove('active');
            lightboxClose.addEventListener('click', closeModal);
            lightbox.addEventListener('click', (e) => (e.target === lightbox) && closeModal());
        } else {
            console.warn('[Mobile JS] Lightbox HTML structure incomplete.');
        }
    }

    // --- Feature 3: Dashboard - AJAX Tabs ---
    // This should only run on the dashboard.
    const tabContainer = document.querySelector('.mobile-tabs');
    if (tabContainer) {
        const tabContent = document.getElementById('tab-content');

        if (tabContent) {
            console.log('[Mobile JS] AJAX tab system module loaded.');
            tabContainer.addEventListener('click', async function(e) {
                e.preventDefault();
                const targetLink = e.target.closest('.tab-link');

                if (targetLink && !targetLink.classList.contains('active')) {
                    const tabName = targetLink.dataset.tab;
                    console.log(`[Mobile JS] Tab clicked: ${tabName}`);
                    
                    document.querySelectorAll('.tab-link').forEach(link => link.classList.remove('active'));
                    targetLink.classList.add('active');

                    tabContent.innerHTML = '<div class="loading-spinner"></div>';
                    console.log('[Mobile JS] Fetching content...');

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
                        console.error('[Mobile JS] Fetch Error:', error);
                        tabContent.innerHTML = `<div class="error-message">${error.message}</div>`;
                    }
                }
            });
        } else {
            console.warn('[Mobile JS] Tab content container (#tab-content) not found.');
        }
    }

    console.log('[Mobile JS] Initialization complete.');
});
