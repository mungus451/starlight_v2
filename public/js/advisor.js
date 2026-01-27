document.addEventListener('DOMContentLoaded', () => {
    const ADVISOR_STATE_KEY = 'advisorPodState';
    const ADVISOR_COLLAPSED_KEY = 'starlight_advisor_collapsed'; // For desktop sidebar collapse state

    const advisorPanel = document.getElementById('advisor-panel');
    const desktopAdvisorToggleBtn = document.getElementById('advisor-toggle'); // Desktop toggle button
    const body = document.body;

    // --- Desktop Sidebar Collapse Logic ---
    // This logic now applies only on wider screens, as its CSS is media-queried
    if (advisorPanel && desktopAdvisorToggleBtn) {
        // 1. Restore State for desktop (only if not on mobile viewport)
        // We'll let CSS handle initial mobile state
        if (window.innerWidth > 980) { // Apply desktop collapse only on larger screens
            const isCollapsed = localStorage.getItem(ADVISOR_COLLAPSED_KEY) === 'true';
            if (isCollapsed) {
                advisorPanel.classList.add('collapsed');
                body.classList.add('advisor-collapsed');
            }
        }

        // 2. Toggle Handler for desktop button
        desktopAdvisorToggleBtn.addEventListener('click', () => {
            advisorPanel.classList.toggle('collapsed');
            body.classList.toggle('advisor-collapsed');
            
            // Save State
            const collapsedState = advisorPanel.classList.contains('collapsed');
            localStorage.setItem(ADVISOR_COLLAPSED_KEY, collapsedState);
        });
    }

    // --- Mobile Navigation Toggle ---
    const mobileMenuToggleBtn = document.getElementById('mobile-menu-toggle');
    const mainNav = document.querySelector('.main-nav');
    if (mobileMenuToggleBtn && mainNav) {
        mobileMenuToggleBtn.addEventListener('click', () => {
            mainNav.classList.toggle('active');
            // Close advisor if open
            if (advisorPanel && advisorPanel.classList.contains('active')) {
                advisorPanel.classList.remove('active');
            }
            // Toggle body overflow to prevent scrolling when nav is open
            body.classList.toggle('no-scroll');
        });
    }

    // --- Mobile Advisor Toggle ---
    const mobileAdvisorToggleBtn = document.getElementById('mobile-advisor-toggle');
    if (mobileAdvisorToggleBtn && advisorPanel) {
        mobileAdvisorToggleBtn.addEventListener('click', () => {
            advisorPanel.classList.toggle('active');
            // Close main nav if open
            if (mainNav && mainNav.classList.contains('active')) {
                mainNav.classList.remove('active');
            }
            // Toggle body overflow to prevent scrolling when advisor is open
            body.classList.toggle('no-scroll');
        });
    }

    // --- Close mobile menus when clicking outside ---
    document.addEventListener('click', (event) => {
        // Check if click is outside main nav or its toggle
        if (mainNav && mainNav.classList.contains('active') && !mainNav.contains(event.target) && (!mobileMenuToggleBtn || !mobileMenuToggleBtn.contains(event.target))) {
            mainNav.classList.remove('active');
            body.classList.remove('no-scroll');
        }
        // Check if click is outside advisor panel or its toggle
        if (advisorPanel && advisorPanel.classList.contains('active') && !advisorPanel.contains(event.target) && (!mobileAdvisorToggleBtn || !mobileAdvisorToggleBtn.contains(event.target))) {
            advisorPanel.classList.remove('active');
            body.classList.remove('no-scroll');
        }
    });

    // --- Close mobile menus on resize (if transitioning to desktop view) ---
    window.addEventListener('resize', () => {
        if (window.innerWidth > 980) {
            if (mainNav) mainNav.classList.remove('active');
            if (advisorPanel) advisorPanel.classList.remove('active');
            body.classList.remove('no-scroll');
        }
        // Re-apply desktop collapse state if it was stored
        if (window.innerWidth > 980 && advisorPanel && desktopAdvisorToggleBtn) {
            const isCollapsed = localStorage.getItem(ADVISOR_COLLAPSED_KEY) === 'true';
            if (isCollapsed) {
                advisorPanel.classList.add('collapsed');
                body.classList.add('advisor-collapsed');
            } else {
                advisorPanel.classList.remove('collapsed');
                body.classList.remove('advisor-collapsed');
            }
        }
    });


    // Function to get the current state from localStorage
    const getAdvisorState = () => {
        try {
            const state = localStorage.getItem(ADVISOR_STATE_KEY);
            return state ? JSON.parse(state) : {};
        } catch (e) {
            console.error('Failed to parse advisor state:', e);
            return {};
        }
    };

    // Function to save the state to localStorage
    const saveAdvisorState = (state) => {
        try {
            localStorage.setItem(ADVISOR_STATE_KEY, JSON.stringify(state));
        } catch (e) {
            console.error('Failed to save advisor state:', e);
        }
    };

    // Advisor Clock
    const clockElement = document.getElementById('advisor-clock');
    if (clockElement) {
        const updateClock = () => {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { hour12: false });
            clockElement.textContent = timeString;
        };
        setInterval(updateClock, 1000);
        updateClock();
    }

    // Advisor Pod Toggles
    const podHeaders = document.querySelectorAll('.advisor-pod-header');
    const currentState = getAdvisorState();

    podHeaders.forEach(header => {
        const pod = header.parentElement;
        const podId = header.getAttribute('data-pod-id');

        if (!podId) return;

        // Restore state on page load
        if (currentState[podId]) {
            pod.classList.add('open');
        }

        header.addEventListener('click', () => {
            pod.classList.toggle('open');
            // Save the new state
            const state = getAdvisorState();
            state[podId] = pod.classList.contains('open');
            saveAdvisorState(state);
        });
    });
});

// Minimalist countdown for sidebar - This will run in any context due to DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    const warStatus = document.querySelector('[data-war-end-time]');
    if (warStatus) {
        const updateWarTimer = () => {
            const endTime = new Date(warStatus.dataset.warEndTime + ' UTC').getTime();
            const now = new Date().getTime();
            const distance = endTime - now;

            if (distance < 0) {
                warStatus.innerHTML = "WAR CONCLUDED";
                return;
            }
            
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));

            warStatus.innerHTML = `Time Left: ${hours}h ${minutes}m`;
        };
        updateWarTimer();
        setInterval(updateWarTimer, 60000); // Update every minute
    }
});