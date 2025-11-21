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
    }
};