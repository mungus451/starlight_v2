document.addEventListener('DOMContentLoaded', function() {
    // JS for the "Show Breakdown" toggles
    document.querySelectorAll('.card-toggle').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            const targetId = this.getAttribute('data-target');
            
            // Exclude economic overview breakdown from toggle logic
            if (targetId === 'breakdown-income') {
                e.preventDefault(); // Prevent default if it's the excluded target
                return;
            }
            
            if (!targetId) {
                return; // Let links like "Train" or "Spend Points" follow their href
            }

            e.preventDefault(); // Prevent default for actual toggle targets
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                if (targetElement.classList.contains('active')) {
                    targetElement.classList.remove('active');
                    this.textContent = 'Show Breakdown';
                } else {
                    targetElement.classList.add('active');
                    this.textContent = 'Hide Breakdown';
                }
            }
        });
    });
});