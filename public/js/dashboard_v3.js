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

    const points = [];
    const maxPoints = 100;
    const centerY = height / 2;

    // Fill initial points
    for (let i = 0; i < maxPoints; i++) {
        points.push(centerY);
    }

    function draw() {
        ctx.clearRect(0, 0, width, height);

        // Shift points
        points.shift();
        
        // Random "heartbeat" or "activity" spike
        let newY = centerY + (Math.random() - 0.5) * 5;
        
        // Occasional large spike (simulating an event)
        if (Math.random() > 0.98) {
            newY = centerY + (Math.random() - 0.5) * 60;
        }
        
        points.push(newY);

        // Draw line
        ctx.beginPath();
        ctx.lineWidth = 2;
        ctx.strokeStyle = '#00f3ff';
        ctx.shadowBlur = 8;
        ctx.shadowColor = '#00f3ff';

        for (let i = 0; i < points.length; i++) {
            const x = (i / (maxPoints - 1)) * width;
            if (i === 0) ctx.moveTo(x, points[i]);
            else ctx.lineTo(x, points[i]);
        }

        ctx.stroke();

        // Draw scanning dot
        ctx.beginPath();
        ctx.arc(width, points[points.length - 1], 4, 0, Math.PI * 2);
        ctx.fillStyle = '#fff';
        ctx.fill();

        requestAnimationFrame(draw);
    }

    draw();

    // Handle Resize
    window.addEventListener('resize', () => {
        width = canvas.offsetWidth;
        height = canvas.offsetHeight;
        canvas.width = width;
        canvas.height = height;
    });
}
