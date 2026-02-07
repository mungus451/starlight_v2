document.addEventListener('DOMContentLoaded', () => {
    const ADVISOR_STATE_KEY = 'advisorPodState';
    const ADVISOR_COLLAPSED_KEY = 'starlight_advisor_collapsed';

    const advisorPanel = document.getElementById('advisor-panel');
    const advisorToggleBtn = document.getElementById('advisor-toggle');
    const body = document.body;

    // --- Sidebar Collapse Logic ---
    if (advisorPanel && advisorToggleBtn) {
        // 1. Restore State
        const isCollapsed = localStorage.getItem(ADVISOR_COLLAPSED_KEY) === 'true';
        if (isCollapsed) {
            advisorPanel.classList.add('collapsed');
            body.classList.add('advisor-collapsed');
        }

        // 2. Toggle Handler
        advisorToggleBtn.addEventListener('click', () => {
            advisorPanel.classList.toggle('collapsed');
            body.classList.toggle('advisor-collapsed');
            
            // Save State
            const collapsedState = advisorPanel.classList.contains('collapsed');
            localStorage.setItem(ADVISOR_COLLAPSED_KEY, collapsedState);
        });
    }

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

// Minimalist countdown for sidebar
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
