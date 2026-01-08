/**
 * Black Market V2 - UI Logic
 * Handles 3D Tilt, Hold-to-Decrypt, and Ticker interactions.
 */

document.addEventListener('DOMContentLoaded', () => {
    init3DTilt();
    initHoldToDecrypt();
    initTicker();
});

/* --- 1. 3D Tilt Effect --- */
function init3DTilt() {
    const cards = document.querySelectorAll('.bm-card');

    cards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            // Calculate rotation (max 10 degrees)
            // Center of card is (0,0) for calculation
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = ((y - centerY) / centerY) * -5; // Invert Y axis for correct tilt
            const rotateY = ((x - centerX) / centerX) * 5;

            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
        });

        card.addEventListener('mouseleave', () => {
            // Reset to flat
            card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)';
        });
    });
}

/* --- 2. Hold-to-Decrypt Logic --- */
function initHoldToDecrypt() {
    const holdButtons = document.querySelectorAll('.btn-decrypt');
    const holdDuration = 1500; // 1.5 seconds to unlock

    holdButtons.forEach(btn => {
        let holdTimer = null;
        let startTime = null;
        let animationFrame = null;
        const progressBar = btn.querySelector('.btn-decrypt-progress');
        const originalText = btn.querySelector('.btn-decrypt-text').innerText;
        const form = btn.closest('form');

        // Prevent default click submission
        btn.addEventListener('click', (e) => {
            e.preventDefault();
        });

        const startHold = () => {
            if (btn.classList.contains('disabled')) return;
            
            btn.classList.add('holding');
            startTime = Date.now();
            
            // Text Scramble Effect
            const scrambleInterval = setInterval(() => {
                const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
                let scrambled = "";
                for(let i=0; i<originalText.length; i++) {
                    scrambled += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                btn.querySelector('.btn-decrypt-text').innerText = scrambled;
            }, 50);

            // Progress Animation Loop
            const updateProgress = () => {
                const elapsed = Date.now() - startTime;
                const progress = Math.min((elapsed / holdDuration) * 100, 100);
                
                progressBar.style.width = `${progress}%`;

                if (elapsed >= holdDuration) {
                    // SUCCESS
                    clearInterval(scrambleInterval);
                    btn.querySelector('.btn-decrypt-text').innerText = "ACCESS GRANTED";
                    btn.classList.add('success'); // Add a flash class if defined
                    
                    // Submit form
                    if (form) form.submit();
                } else {
                    animationFrame = requestAnimationFrame(updateProgress);
                }
            };
            
            animationFrame = requestAnimationFrame(updateProgress);

            // Store interval ID on element to clear later if needed (hacky but works)
            btn.dataset.scrambleId = scrambleInterval;
        };

        const stopHold = () => {
            if (!startTime) return; // Wasn't holding

            // Reset
            cancelAnimationFrame(animationFrame);
            clearInterval(btn.dataset.scrambleId);
            
            btn.classList.remove('holding');
            progressBar.style.width = '0%';
            btn.querySelector('.btn-decrypt-text').innerText = originalText;
            startTime = null;
        };

        // Mouse Events
        btn.addEventListener('mousedown', startHold);
        btn.addEventListener('mouseup', stopHold);
        btn.addEventListener('mouseleave', stopHold);

        // Touch Events (Mobile Support)
        btn.addEventListener('touchstart', (e) => { e.preventDefault(); startHold(); });
        btn.addEventListener('touchend', stopHold);
    });
}

/* --- 3. Syndicate Ticker Logic --- */
function initTicker() {
    const track = document.querySelector('.bm-ticker-track');
    if (!track) return;

    // Clone items to ensure seamless loop if content is short
    // Actually CSS 'infinite' works best if we duplicate content enough times
    // Let's just duplicate the innerHTML once to be safe for scrolling
    track.innerHTML += track.innerHTML;
}
