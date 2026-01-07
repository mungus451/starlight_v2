document.addEventListener('DOMContentLoaded', () => {
    const ADVISOR_STATE_KEY = 'advisorPodState';

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
