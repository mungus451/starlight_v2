document.addEventListener('DOMContentLoaded', function() {
    
    // --- Modal Elements ---
    const modalOverlay = document.getElementById('spy-modal-overlay');
    const modalCloseBtn = document.getElementById('modal-close-btn');
    const modalTargetNameInput = document.getElementById('modal-target-name');
    const modalTargetNameDisplay = document.getElementById('modal-target-name-display');
    const allSpyButtons = document.querySelectorAll('.btn-spy-modal');

    // --- Function to open the modal ---
    function openModal(targetName) {
        modalTargetNameInput.value = targetName;
        modalTargetNameDisplay.textContent = targetName;
        modalOverlay.style.display = 'flex';
        setTimeout(() => {
            modalOverlay.classList.add('active');
        }, 10); // Start transition after display
    }

    // --- Function to close the modal ---
    function closeModal() {
        modalOverlay.classList.remove('active');
        setTimeout(() => {
            modalOverlay.style.display = 'none';
        }, 300); // Wait for transition to finish
    }

    // --- Event Listeners ---
    
    // Add listener to all "Spy" buttons and player avatars
    allSpyButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get target data from the clicked button's data attributes
            const targetName = this.getAttribute('data-target-name');
            // const targetId = this.getAttribute('data-target-id'); // Not used yet
            
            // Open the modal
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