document.addEventListener('DOMContentLoaded', function() {
    // JS for the "Show Breakdown" toggles
    document.querySelectorAll('.card-toggle').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            if (!targetId) {
                // If no target, it's a link (like "Train" or "Spend Points"), so follow it
                window.location.href = this.href;
                return;
            }

            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                if (targetElement.classList.contains('active')) {
                    // It's active, hide it
                    targetElement.classList.remove('active');
                    this.textContent = 'Show Breakdown';
                } else {
                    // It's hidden, show it
                    targetElement.classList.add('active');
                    this.textContent = 'Hide Breakdown';
                }
            }
        });
    });
});