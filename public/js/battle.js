/**
 * Battle Page Logic
 * Handles the attack confirmation modal and target selection.
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // --- Modal Elements ---
    const modalOverlay = document.getElementById('attack-modal-overlay');
    const modalCloseBtn = document.getElementById('modal-close-btn');
    const modalTargetIdInput = document.getElementById('modal-target-id');
    const modalTargetNameDisplay = document.getElementById('modal-target-name-display');
    const allAttackButtons = document.querySelectorAll('.btn-attack-modal');

    // If we aren't on the battle page (elements missing), exit
    if (!modalOverlay || !modalTargetIdInput) return;

    // --- Function to open the modal ---
    function openModal(targetId, targetName) {
        if (!targetId || !targetName) {
            console.error('Battle script: Target ID or Name is missing.');
            return;
        }
        modalTargetIdInput.value = targetId;
        modalTargetNameDisplay.textContent = targetName;
        
        modalOverlay.style.display = 'flex';
        setTimeout(() => {
            modalOverlay.classList.add('active');
        }, 10); 
    }

    // --- Function to close the modal ---
    function closeModal() {
        modalOverlay.classList.remove('active');
        setTimeout(() => {
            modalOverlay.style.display = 'none';
        }, 300); 
    }

    // --- Event Listeners ---
    
    // Add listener to all "Attack" buttons and player avatars
    allAttackButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target-id');
            const targetName = this.getAttribute('data-target-name');
            openModal(targetId, targetName);
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