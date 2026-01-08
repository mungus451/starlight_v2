/**
 * alliance_uplink.js
 * Handles the interactive functionality of the Alliance Sidebar.
 * - Collapsing/Expanding
 * - State persistence via localStorage
 */

document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('uplink-toggle');
    const panel = document.getElementById('alliance-uplink-panel');
    const body = document.body;

    // Only run if elements exist (e.g., user is in an alliance)
    if (!toggleBtn || !panel) return;

    // 1. Load State
    const isCollapsed = localStorage.getItem('starlight_uplink_collapsed') === 'true';
    if (isCollapsed) {
        panel.classList.add('collapsed');
        body.classList.add('uplink-collapsed');
    }

    // 2. Toggle Handler
    toggleBtn.addEventListener('click', function() {
        panel.classList.toggle('collapsed');
        body.classList.toggle('uplink-collapsed');

        // Save State
        const collapsedState = panel.classList.contains('collapsed');
        localStorage.setItem('starlight_uplink_collapsed', collapsedState);
    });
});
