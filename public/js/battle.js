/**
 * Battle Page Logic
 * Handles the attack confirmation modal and target selection.
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // --- Modal Elements ---
    const modalOverlay = document.getElementById('attack-modal-overlay');
    const modalCloseBtn = document.getElementById('modal-close-btn');
    const modalTargetNameInput = document.getElementById('modal-target-name');
    const modalTargetNameDisplay = document.getElementById('modal-target-name-display');
    const allAttackButtons = document.querySelectorAll('.btn-attack-modal');

    // If we aren't on the battle page (elements missing), exit
    if (!modalOverlay || !modalTargetNameInput) return;

    // --- Function to open the modal ---
    function openModal(targetName) {
        modalTargetNameInput.value = targetName;
        modalTargetNameDisplay.textContent = targetName;
        
        modalOverlay.style.display = 'flex';
        // Small delay to allow display:flex to apply before opacity transition
        setTimeout(() => {
            modalOverlay.classList.add('active');
        }, 10); 
    }

    // --- Function to close the modal ---
    function closeModal() {
        modalOverlay.classList.remove('active');
        // Wait for transition to finish before hiding
        setTimeout(() => {
            modalOverlay.style.display = 'none';
        }, 300); 
    }

    // --- Event Listeners ---
    
    // Add listener to all "Attack" buttons and player avatars
    allAttackButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const playerRow = this.closest('.player-row');
            const targetName = playerRow.getAttribute('data-target-name');
            openModal(targetName);
        });
    });

    // Close modal via the 'X' button
    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', function(e) {
            e.preventDefault();
            closeModal();
        });
    }

    // Close modal by clicking the background overlay
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) {
                closeModal();
            }
        });
    }
});