    document.addEventListener('DOMContentLoaded', function() {
        
        // --- NEW: Get all cards for expand/collapse ---
        const allCards = document.querySelectorAll('.structure-card');
        const expandAllBtn = document.getElementById('btn-expand-all');
        const collapseAllBtn = document.getElementById('btn-collapse-all');

        // --- Individual Card Toggling (Unchanged) ---
        const allHeaders = document.querySelectorAll('.card-header');
        allHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const card = this.closest('.structure-card');
                if (card) {
                    card.classList.toggle('is-expanded');
                }
            });
        });

        // --- Expand All Button ---
        if (expandAllBtn) {
            expandAllBtn.addEventListener('click', function() {
                allCards.forEach(card => {
                    card.classList.add('is-expanded');
                });
            });
        }
        
        // --- Collapse All Button ---
        if (collapseAllBtn) {
            collapseAllBtn.addEventListener('click', function() {
                allCards.forEach(card => {
                    card.classList.remove('is-expanded');
                });
            });
        }

    });