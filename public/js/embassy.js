/**
 * Embassy UI Logic
 * Handles tab switching for planetary directives.
 */

document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.structure-nav-btn[data-tab-target]');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove active class from all tabs
            tabs.forEach(t => t.classList.remove('active'));
            // Add to clicked
            tab.classList.add('active');
            
            // Hide all content containers
            document.querySelectorAll('.structure-category-container').forEach(c => {
                c.classList.remove('active');
            });
            
            // Show target
            const targetId = tab.dataset.tabTarget;
            const targetEl = document.getElementById(targetId);
            if (targetEl) {
                targetEl.classList.add('active');
            }
        });
    });
});
