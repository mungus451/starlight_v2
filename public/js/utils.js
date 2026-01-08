/**
 * Starlight Dominion - Shared Utilities
 * Handles common frontend tasks like number formatting and input masking.
 */
window.StarlightUtils = {
    
    /**
     * Formats a raw number string into a comma-separated string.
     * @param {string|number} num The number to format.
     * @returns {string} A formatted number string (e.g., "1,000,000").
     */
    formatNumber: function(num) {
        if (num === null || num === undefined || num === '') return '';
        // Ensure we are working with a number
        const parsed = parseInt(num.toString().replace(/[^0-9-]/g, ''), 10);
        if (isNaN(parsed)) return '';
        return parsed.toLocaleString('en-US');
    },

    /**
     * Removes all non-digit characters from a string (except minus sign).
     * @param {string} str A formatted string, e.g., "1,000,000"
     * @returns {string} A raw number string, e.g., "1000000"
     */
    unformatNumber: function(str) {
        if (!str) return '';
        return str.toString().replace(/[^0-9-]/g, '');
    },

    /**
     * Sets up a two-way binding input mask.
     * Updates a hidden input with the raw integer value while keeping
     * the visible input formatted with commas.
     * * @param {HTMLElement} displayInput The visible <input type="text">
     * @param {HTMLElement} hiddenInput The hidden <input type="hidden">
     */
    setupInputMask: function(displayInput, hiddenInput) {
        if (!displayInput || !hiddenInput) return;

        displayInput.addEventListener('input', (e) => {
            // 1. Capture cursor position before modification
            const cursorStart = e.target.selectionStart;
            const originalLength = e.target.value.length;

            // 2. Get raw value
            const rawValue = this.unformatNumber(e.target.value);
            const numValue = rawValue ? parseInt(rawValue, 10) : 0;
            
            // 3. Update hidden source of truth
            hiddenInput.value = numValue;

            // 4. Re-format visible input
            const formattedValue = (numValue === 0 && rawValue === '') ? '' : this.formatNumber(rawValue);
            e.target.value = formattedValue;
            
            // 5. Restore cursor position adjusted for added/removed commas
            const newLength = formattedValue.length;
            const lengthDifference = newLength - originalLength;
            const newCursorPosition = cursorStart + lengthDifference;
            
            if (cursorStart > 0) {
                 e.target.setSelectionRange(newCursorPosition, newCursorPosition);
            }
        });
    },

    /**
     * Initializes tab navigation with localStorage persistence.
     * @param {string} storageKey Unique key for localStorage.
     * @param {string} defaultTabId Default tab ID to show if no history.
     */
    setupTabs: function(storageKey, defaultTabId) {
        const navBtns = document.querySelectorAll('.structure-nav-btn');
        const categories = document.querySelectorAll('.structure-category-container');
        
        if (navBtns.length === 0) return;

        const activateTab = (targetId) => {
            // Update Buttons
            navBtns.forEach(btn => {
                if (btn.getAttribute('data-tab-target') === targetId) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });

            // Update Content
            categories.forEach(cat => {
                if (cat.id === targetId) {
                    cat.classList.add('active');
                } else {
                    cat.classList.remove('active');
                }
            });

            // Save State
            localStorage.setItem(storageKey, targetId);
        };

        // Initialize from storage or default
        const savedTab = localStorage.getItem(storageKey);
        if (savedTab && document.getElementById(savedTab)) {
            activateTab(savedTab);
        } else {
            activateTab(defaultTabId);
        }

        // Add Listeners
        navBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                activateTab(btn.getAttribute('data-tab-target'));
            });
        });
    }
};