document.addEventListener('DOMContentLoaded', function () {
    // --- Globals ---
    let selectedStat = null;
    const MAX_POINTS = parseInt(document.getElementById('header-points-display').textContent.replace(/,/g, ''), 10) || 0;

    // --- DOM Elements ---
    const inspector = {
        panel: document.getElementById('inspector-panel'),
        title: document.getElementById('insp-title'),
        icon: document.getElementById('insp-icon'),
        desc: document.getElementById('insp-desc'),
        controls: document.getElementById('insp-controls'),
        input: document.getElementById('stat-input'),
        hiddenStat: document.getElementById('selected-stat'),
        confirmBtn: document.getElementById('btn-confirm'),
        decBtn: document.querySelector('.btn-dec'),
        incBtn: document.querySelector('.btn-inc')
    };

    // --- Main Selection Function ---
    window.selectStat = function(element) {
        if (selectedStat) {
            selectedStat.classList.remove('active');
        }
        selectedStat = element;
        selectedStat.classList.add('active');

        // --- Extract data from the clicked element ---
        const statData = {
            key: element.dataset.stat,
            name: element.dataset.name,
            desc: element.dataset.desc,
            icon: element.dataset.icon,
            color: element.dataset.color,
            current: element.dataset.current
        };

        // --- Update Inspector Panel ---
        inspector.title.textContent = `UPGRADE: ${statData.name.toUpperCase()}`;
        inspector.icon.className = statData.icon;
        inspector.icon.style.color = statData.color;
        inspector.desc.textContent = statData.desc;
        
        inspector.hiddenStat.value = statData.key;
        inspector.input.value = 0;
        inspector.input.max = MAX_POINTS;
        
        inspector.decBtn.dataset.target = statData.key;
        inspector.incBtn.dataset.target = statData.key;

        inspector.controls.style.display = 'block';
        updateUI();
    };

    // --- Event Listeners for +/- buttons ---
    inspector.decBtn.addEventListener('click', () => adjustAmount(-1));
    inspector.incBtn.addEventListener('click', () => adjustAmount(1));
    inspector.input.addEventListener('input', updateUI);

    // --- Helper Functions ---
    function adjustAmount(value) {
        let currentVal = parseInt(inspector.input.value, 10);
        let newVal = currentVal + value;
        
        if (newVal >= 0 && newVal <= MAX_POINTS) {
            inspector.input.value = newVal;
            updateUI();
        }
    }

    function updateUI() {
        let currentVal = parseInt(inspector.input.value, 10);
        
        // Disable/Enable buttons
        inspector.decBtn.disabled = currentVal <= 0;
        inspector.incBtn.disabled = currentVal >= MAX_POINTS;
        inspector.confirmBtn.disabled = currentVal <= 0;
    }
});