/**
 * Black Market Converter - Client-Side Logic
 *
 * This script handles the input masking, live calculation, ghost balances,
 * market graphs, and reactor core interactions.
 */
document.addEventListener('DOMContentLoaded', () => {

    const conversionRate = 100.0;
    const feePercentage = 0.10;
    
    // Store graph instances to update them later
    const graphInstances = {};

    // --- Helper Functions ---

    function unformatFloat(str) {
        if (typeof str !== 'string' || !str) return '0';
        return str.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');
    }

    function formatFloat(numStr) {
        if (typeof numStr !== 'string' || numStr === '') return '';
        const parts = numStr.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return parts.join('.');
    }

    // --- Ghost Balance System ---
    function updateGhostBalance(resourceType, currentAmount, changeAmount) {
        const walletEl = document.getElementById(`wallet-${resourceType}`);
        const ghostEl = document.getElementById(`ghost-${resourceType}`);
        
        if (!walletEl || !ghostEl) return;

        const currentVal = parseFloat(walletEl.dataset.raw);
        const futureVal = currentVal + changeAmount;
        
        if (changeAmount === 0) {
            ghostEl.classList.remove('visible');
            return;
        }

        ghostEl.textContent = formatFloat(futureVal.toFixed(resourceType === 'credits' ? 2 : 4));
        ghostEl.className = 'balance-ghost visible';
        
        if (changeAmount > 0) {
            ghostEl.classList.add('positive');
            ghostEl.innerHTML += ' <i class="fas fa-arrow-up"></i>';
        } else {
            ghostEl.classList.add('negative');
            ghostEl.innerHTML += ' <i class="fas fa-arrow-down"></i>';
        }
    }

    /**
     * Sets up input mask + logic for Converter
     */
    function setupConverterInput(displayId, hiddenId, type) {
        const display = document.getElementById(displayId);
        const hidden = document.getElementById(hiddenId);
        if (!display || !hidden) return;

        const updateCalc = (val) => {
            const amount = parseFloat(val) || 0;
            let fee, afterFee, receive, sourceRes, targetRes;

            if (type === 'c2cry') {
                fee = amount * feePercentage;
                afterFee = amount - fee;
                receive = afterFee / conversionRate;
                sourceRes = 'credits';
                targetRes = 'crystals';
                
                const feeEl = document.getElementById('c2cry-fee');
                const recEl = document.getElementById('c2cry-receive');
                if(feeEl) feeEl.textContent = formatFloat(fee.toFixed(2)) + ' Credits';
                if(recEl) recEl.textContent = receive.toFixed(4) + ' 💎';

            } else { // cry2c
                fee = amount * feePercentage;
                afterFee = amount - fee;
                receive = afterFee * conversionRate;
                sourceRes = 'crystals';
                targetRes = 'credits';

                const feeEl = document.getElementById('cry2c-fee');
                const recEl = document.getElementById('cry2c-receive');
                if(feeEl) feeEl.textContent = fee.toFixed(4) + ' 💎';
                if(recEl) recEl.textContent = formatFloat(receive.toFixed(2)) + ' Credits';
            }

            // Update Ghosts
            updateGhostBalance(sourceRes, 0, -amount);
            updateGhostBalance(targetRes, 0, receive);
        };

        display.addEventListener('input', (e) => {
            const raw = unformatFloat(e.target.value);
            hidden.value = raw;
            e.target.value = formatFloat(raw);
            updateCalc(raw);
        });

        // Range Toggles
        const toggles = document.querySelectorAll(`.btn-range[data-target="${displayId}"]`);
        toggles.forEach(btn => {
            btn.addEventListener('click', () => {
                let max = 0;
                if (type === 'c2cry') {
                    max = parseFloat(document.getElementById('wallet-credits').dataset.raw);
                } else {
                    max = parseFloat(document.getElementById('wallet-crystals').dataset.raw);
                }
                
                const percent = parseFloat(btn.dataset.percent);
                const val = (max * percent).toFixed(2);
                
                hidden.value = val;
                display.value = formatFloat(val);
                updateCalc(val);
            });
        });
    }

    setupConverterInput('credits-amount-display', 'credits-amount-hidden', 'c2cry');
    setupConverterInput('crystals-amount-display', 'crystals-amount-hidden', 'cry2c');


    // --- Reactor Core Logic ---
    function setupReactor(sliderId, displayId, hiddenId, coreId, type) {
        const slider = document.getElementById(sliderId);
        const display = document.getElementById(displayId);
        const hidden = document.getElementById(hiddenId);
        const core = document.getElementById(coreId);
        const graphId = `graph-syn-${type}`;
        
        if (!slider || !display) return;

        const updateReactor = (val, fromInput = false) => {
            hidden.value = val;
            
            // Sync slider if update came from text input
            if (fromInput) {
                slider.value = val;
            } else {
                display.value = formatFloat(val.toString());
            }
            
            // Core Animation
            if (val > 0) {
                core.classList.add('active');
            } else {
                core.classList.remove('active');
            }

            // Update Graph Intensity based on slider percentage
            if (graphInstances[graphId]) {
                const max = parseFloat(slider.max) || 100;
                const percent = Math.min(val / max, 1);
                // Base volatility 20, max 100
                graphInstances[graphId].updateIntensity(20 + (percent * 80));
            }

            // Calc Output
            let base, fee, receive;
            if (type === 'credits') {
                base = val / 10000;
                // Update Ghost: Credits down, DM up
                updateGhostBalance('credits', 0, -val);
            } else {
                base = val / 10;
                updateGhostBalance('crystals', 0, -val);
            }
            
            fee = base * 0.3;
            receive = base * 0.7;
            
            updateGhostBalance('dm', 0, receive);

            const recEl = document.getElementById(`syn-${type}-receive`);
            const feeEl = document.getElementById(`syn-${type}-fee`);
            
            if(recEl) recEl.textContent = receive.toFixed(4) + ' DM';
            if(feeEl) feeEl.textContent = fee.toFixed(4) + ' DM';
        };

        // Slider -> Input
        slider.addEventListener('input', (e) => {
            updateReactor(parseFloat(e.target.value));
        });

        // Input -> Slider
        display.addEventListener('input', (e) => {
            const raw = unformatFloat(e.target.value);
            // Cap at max
            let val = parseFloat(raw) || 0;
            const max = parseFloat(slider.max);
            if (val > max) val = max;

            // Update without re-formatting the display immediately to avoid cursor jump issues
            hidden.value = val;
            slider.value = val;
            
            // Manually trigger reactor update logic but flag it came from input
            updateReactor(val, true);
        });
        
        // Format on blur
        display.addEventListener('blur', (e) => {
             const raw = unformatFloat(e.target.value);
             e.target.value = formatFloat(raw);
        });
    }

    setupReactor('slider-syn-credits', 'syn-credits-display', 'syn-credits-hidden', 'core-credits', 'credits');
    setupReactor('slider-syn-crystals', 'syn-crystals-display', 'syn-crystals-hidden', 'core-crystals', 'crystals');


    // --- Market Pulse Graph (Canvas) ---
    function initMarketGraph(canvasId, color) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        let width = canvas.offsetWidth;
        let height = canvas.offsetHeight;
        canvas.width = width;
        canvas.height = height;

        const dataPoints = [];
        const maxPoints = 50;
        let y = height / 2;
        let volatility = 20; // Default volatility

        for (let i = 0; i < maxPoints; i++) dataPoints.push(y);

        function draw() {
            // Shift
            dataPoints.shift();
            // Jitter
            y += (Math.random() - 0.5) * volatility;
            y = Math.max(10, Math.min(height - 10, y));
            dataPoints.push(y);

            ctx.clearRect(0, 0, width, height);
            ctx.beginPath();
            ctx.moveTo(0, dataPoints[0]);

            for (let i = 1; i < maxPoints; i++) {
                const x = (i / (maxPoints - 1)) * width;
                ctx.lineTo(x, dataPoints[i]);
            }

            ctx.strokeStyle = color;
            ctx.lineWidth = 3;
            ctx.shadowBlur = 15;
            ctx.shadowColor = color;
            ctx.stroke();

            // Fill area
            ctx.lineTo(width, height);
            ctx.lineTo(0, height);
            ctx.closePath();
            ctx.fillStyle = color + '33'; // Higher opacity (hex 33 is ~20%)
            ctx.fill();

            requestAnimationFrame(draw);
        }
        draw();

        // Register instance for external control
        graphInstances[canvasId] = {
            updateIntensity: (newVol) => { volatility = newVol; }
        };
    }

    initMarketGraph('graph-credits', '#f9c74f');
    initMarketGraph('graph-crystals', '#00f3ff');
    initMarketGraph('graph-syn-credits', '#bc13fe');
    initMarketGraph('graph-syn-crystals', '#bc13fe');

});