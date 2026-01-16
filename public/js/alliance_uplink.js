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

/**
 * Handle Turn Donation to Alliance Energy
 */
function donateTurns() {
    const amount = prompt("Enter amount of Turns to donate (1 Turn = 1 AE):");
    if (!amount || isNaN(amount) || amount <= 0) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    fetch('/alliance/ops/donate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `csrf_token=${encodeURIComponent(csrfToken)}&amount=${amount}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload(); // Refresh to show new AE and Logs
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => console.error(err));
}

/**
 * Handle Contribution to Active Op
 */
function contributeToOp(opId, resourceName = 'units') {
    const amount = prompt(`Enter amount of ${resourceName} to contribute:`);
    if (!amount || isNaN(amount) || amount <= 0) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    fetch('/alliance/ops/contribute', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `csrf_token=${encodeURIComponent(csrfToken)}&op_id=${opId}&amount=${amount}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload(); // Refresh to update progress bar
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => console.error(err));
}

