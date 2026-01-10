/**
 * Dashboard V3 - Bridge Logic
 * Handles Oscilloscope animations and holographic interactions.
 */

document.addEventListener('DOMContentLoaded', () => {
    initOscilloscope();
});

function initOscilloscope() {
    const canvas = document.getElementById('oscilloscope-canvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    let width = canvas.offsetWidth;
    let height = canvas.offsetHeight;
    canvas.width = width;
    canvas.height = height;

    const battles = window.dashboardData?.battles || [];
    
    // Parse battle timestamps
    const events = battles.map(b => {
        return {
            time: new Date(b.created_at).getTime(),
            result: b.attack_result, // 'win' or 'loss'
            type: b.attack_type,
            details: `${b.attacker_name || 'Unknown'} vs ${b.defender_name || 'Unknown'}`
        };
    }).filter(e => !isNaN(e.time));

    const timeWindow = 24 * 60 * 60 * 1000; // 24 hours in ms
    let now = Date.now();

    function draw() {
        // Update dimensions if resized
        if (canvas.width !== canvas.offsetWidth) {
            width = canvas.offsetWidth;
            height = canvas.offsetHeight;
            canvas.width = width;
            canvas.height = height;
        }

        const container = document.querySelector('.command-bridge-container');
        const isWarMode = container && container.classList.contains('war-mode');

        now = Date.now();
        const centerY = height / 2;

        ctx.clearRect(0, 0, width, height);

        // Draw Baseline
        ctx.beginPath();
        ctx.strokeStyle = isWarMode ? 'rgba(255, 42, 42, 0.3)' : 'rgba(0, 243, 255, 0.2)';
        ctx.lineWidth = 1;
        ctx.moveTo(0, centerY);
        ctx.lineTo(width, centerY);
        ctx.stroke();

        // Draw Events
        events.forEach(e => {
            const age = now - e.time;
            if (age > timeWindow || age < 0) return;

            // Calculate X position (Right is Now, Left is 24h ago)
            const x = width - (age / timeWindow) * width;
            
            // Spike Height & Color
            const isWin = e.result === 'win'; // Simplified logic, ideally check if user was attacker/defender and won/lost
            const color = isWin ? '#4caf50' : '#ff2a2a';
            const spikeHeight = isWin ? -40 : 40;

            // Draw Spike
            ctx.beginPath();
            ctx.strokeStyle = color;
            ctx.lineWidth = 2;
            ctx.shadowBlur = 10;
            ctx.shadowColor = color;
            
            ctx.moveTo(x - 5, centerY);
            ctx.lineTo(x, centerY + spikeHeight);
            ctx.lineTo(x + 5, centerY);
            ctx.stroke();

            // Draw Point
            ctx.beginPath();
            ctx.fillStyle = '#fff';
            ctx.arc(x, centerY + spikeHeight, 2, 0, Math.PI * 2);
            ctx.fill();
        });

        // Draw Scanner Line
        const scanSpeed = isWarMode ? 2500 : 5000; // Faster scan in war
        const scanX = (now % scanSpeed) / scanSpeed * width;
        ctx.beginPath();
        ctx.strokeStyle = isWarMode ? 'rgba(255, 50, 50, 0.6)' : 'rgba(255, 255, 255, 0.5)';
        ctx.lineWidth = 2;
        ctx.moveTo(scanX, 0);
        ctx.lineTo(scanX, height);
        ctx.stroke();

        requestAnimationFrame(draw);
    }

    draw();
}