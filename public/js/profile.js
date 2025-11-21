/**
 * Profile Page Logic
 * Handles the interaction for Attack and Spy modals.
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // --- Modal Control Logic ---
    const allModals = document.querySelectorAll('.modal-overlay');
    
    /**
     * Opens a modal by its ID with a fade-in effect.
     * @param {string} modalId 
     */
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            // Small delay to allow display:flex to apply before opacity transition
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
        }
    }

    /**
     * Closes a specific modal element with a fade-out effect.
     * @param {HTMLElement} modal 
     */
    function closeModal(modal) {
        modal.classList.remove('active');
        // Wait for transition to finish before hiding
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }

    // --- Event Listeners ---

    // Open buttons (e.g., "Attack", "Spy")
    document.querySelectorAll('[data-modal-target]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.getAttribute('data-modal-target');
            openModal(modalId);
        });
    });

    // Close buttons (The 'X' in the corner)
    document.querySelectorAll('.modal-close-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const modal = this.closest('.modal-overlay');
            if (modal) {
                closeModal(modal);
            }
        });
    });
    
    // Close by clicking the background overlay
    allModals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal(modal);
            }
        });
    });
});