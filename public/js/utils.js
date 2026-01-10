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
        // Alias for backward compatibility, uses new initTabs logic
        this.initTabs({
            navSelector: '.structure-nav-btn',
            contentSelector: '.structure-category-container',
            storageKey: storageKey,
            defaultTab: defaultTabId,
            dataAttr: 'tab-target'
        });
    },

    /**
     * General tab initialization.
     * Handles .tab-link (data-tab) and .structure-nav-btn (data-tab-target) by default.
     * @param {Object} options Configuration options.
     */
    initTabs: function(options = {}) {
        const {
            navSelector = '.tab-link, .structure-nav-btn',
            contentSelector = '.tab-content, .structure-category-container',
            activeClass = 'active',
            storageKey = null,
            dataAttr = 'tab', // Primary data attribute (e.g., data-tab)
            defaultTab = null,
            onTabChange = null
        } = options;

        const navBtns = document.querySelectorAll(navSelector);
        const contents = document.querySelectorAll(contentSelector);
        
        if (navBtns.length === 0) return;

        const activateTab = (targetId) => {
            if (!targetId) return;

            navBtns.forEach(btn => {
                // Check both dataAttr and fallback to data-tab-target for structures
                const btnId = btn.getAttribute(`data-${dataAttr}`) || btn.getAttribute('data-tab-target');
                if (btnId === targetId) {
                    btn.classList.add(activeClass);
                } else {
                    btn.classList.remove(activeClass);
                }
            });

            contents.forEach(content => {
                if (content.id === targetId) {
                    content.classList.add(activeClass);
                } else {
                    content.classList.remove(activeClass);
                }
            });

            if (storageKey) {
                localStorage.setItem(storageKey, targetId);
            }

            if (typeof onTabChange === 'function') {
                onTabChange(targetId);
            }
        };

        // Initialize from storage or default
        let initialTab = defaultTab;
        if (storageKey) {
            const saved = localStorage.getItem(storageKey);
            if (saved && document.getElementById(saved)) {
                initialTab = saved;
            }
        }

        if (initialTab) {
            activateTab(initialTab);
        } else {
            // Find currently active or first
            const activeBtn = document.querySelector(`${navSelector}.${activeClass}`);
            if (activeBtn) {
                const id = activeBtn.getAttribute(`data-${dataAttr}`) || activeBtn.getAttribute('data-tab-target');
                activateTab(id);
            }
        }

        // Add Listeners
        navBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const targetId = btn.getAttribute(`data-${dataAttr}`) || btn.getAttribute('data-tab-target');
                if (targetId) {
                    // Only prevent default if it's an anchor with a hash or no href
                    const href = btn.getAttribute('href');
                    if (btn.tagName === 'A' && (!href || href.startsWith('#'))) {
                        e.preventDefault();
                    }
                    activateTab(targetId);
                }
            });
        });
    }
};