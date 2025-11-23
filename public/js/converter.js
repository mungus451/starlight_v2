/**
 * Black Market Converter - Client-Side Logic
 *
 * This script handles the input masking and live calculation display for the
 * currency converter. It uses a modified version of the StarlightUtils logic
 * to support floating-point numbers for Naquadah Crystals.
 */
document.addEventListener('DOMContentLoaded', () => {

    const conversionRate = 100.0;
    const feePercentage = 0.10;

    /**
     * A float-compatible version of StarlightUtils.unformatNumber.
     * Removes all non-digit characters except for one decimal point.
     * @param {string} str A formatted string, e.g., "1,000.50"
     * @returns {string} A raw number string, e.g., "1000.50"
     */
    function unformatFloat(str) {
        if (typeof str !== 'string' || !str) return '0';
        // Keep digits and one decimal point
        return str.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');
    }

    /**
     * A float-compatible version of StarlightUtils.formatNumber.
     * Formats a raw number string into a comma-separated string, preserving decimals.
     * @param {string} numStr The number string to format.
     * @returns {string} A formatted number string (e.g., "1,000,000.5000").
     */
    function formatFloat(numStr) {
        if (typeof numStr !== 'string' || numStr === '') return '';
        const parts = numStr.split('.');
        // Format the integer part
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return parts.join('.');
    }

    /**
     * Sets up a two-way binding input mask for float values.
     * @param {HTMLElement} displayInput The visible input field.
     * @param {HTMLElement} hiddenInput The hidden input to store the raw value.
     * @param {Function} onUpdate A callback function to run after the value is updated.
     */
    function setupFloatInputMask(displayInput, hiddenInput, onUpdate) {
        if (!displayInput || !hiddenInput) return;

        // Set initial value from hidden input if it exists
        if (hiddenInput.value) {
            displayInput.value = formatFloat(hiddenInput.value);
        }

        displayInput.addEventListener('input', (e) => {
            const cursorStart = e.target.selectionStart;
            const originalLength = e.target.value.length;

            const rawValue = unformatFloat(e.target.value);
            
            // Update hidden source of truth
            hiddenInput.value = rawValue || '0';

            const formattedValue = formatFloat(rawValue);
            e.target.value = formattedValue;
            
            const newLength = formattedValue.length;
            const lengthDifference = newLength - originalLength;
            
            if (cursorStart !== null) {
                const newCursorPosition = Math.max(0, cursorStart + lengthDifference);
                e.target.setSelectionRange(newCursorPosition, newCursorPosition);
            }
            
            // Run the calculation callback
            if (onUpdate) {
                onUpdate(parseFloat(rawValue) || 0);
            }
        });
    }

    // --- Credits to Crystals Conversion ---
    const creditsDisplay = document.getElementById('credits-amount-display');
    const creditsHidden = document.getElementById('credits-amount-hidden');
    const c2cryFeeEl = document.getElementById('c2cry-fee');
    const c2cryAfterFeeEl = document.getElementById('c2cry-after-fee');
    const c2cryReceiveEl = document.getElementById('c2cry-receive');

    setupFloatInputMask(creditsDisplay, creditsHidden, (amount) => {
        const fee = amount * feePercentage;
        const afterFee = amount - fee;
        const receive = afterFee / conversionRate;

        c2cryFeeEl.textContent = `${formatFloat(fee.toFixed(2))} Credits`;
        c2cryAfterFeeEl.textContent = `${formatFloat(afterFee.toFixed(2))} Credits`;
        c2cryReceiveEl.textContent = `${receive.toFixed(4)} 💎`;
    });

    // --- Crystals to Credits Conversion ---
    const crystalsDisplay = document.getElementById('crystals-amount-display');
    const crystalsHidden = document.getElementById('crystals-amount-hidden');
    const cry2cFeeEl = document.getElementById('cry2c-fee');
    const cry2cAfterFeeEl = document.getElementById('cry2c-after-fee');
    const cry2cReceiveEl = document.getElementById('cry2c-receive');

    setupFloatInputMask(crystalsDisplay, crystalsHidden, (amount) => {
        const fee = amount * feePercentage;
        const afterFee = amount - fee;
        const receive = afterFee * conversionRate;

        cry2cFeeEl.textContent = `${fee.toFixed(4)} 💎`;
        cry2cAfterFeeEl.textContent = `${afterFee.toFixed(4)} 💎`;
        cry2cReceiveEl.textContent = `${formatFloat(receive.toFixed(2))} Credits`;
    });
});
