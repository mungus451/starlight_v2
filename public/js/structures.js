document.addEventListener('DOMContentLoaded', function () {
    // --- Globals ---
    let selectedStructure = null;

    // --- DOM Elements ---
    const inspector = {
        panel: document.getElementById('inspector-panel'),
        title: document.getElementById('insp-title'),
        icon: document.getElementById('insp-icon'),
        desc: document.getElementById('insp-desc'),
        details: document.getElementById('insp-details'),
        level: document.getElementById('insp-level'),
        benefit: document.getElementById('insp-benefit'),
        costCredits: document.getElementById('insp-cost-credits'),
        hiddenKey: document.getElementById('insp-structure-key'),
        confirmBtn: document.getElementById('btn-confirm'),
        maxLevelNotice: document.getElementById('insp-max-level-notice'),
    };

    // --- Main Selection Function ---
    window.selectStructure = function(element) {
        if (selectedStructure) {
            selectedStructure.classList.remove('active');
        }
        selectedStructure = element;
        selectedStructure.classList.add('active');

        const data = element.dataset;

        // --- Update Inspector Panel ---
        inspector.title.textContent = data.name.toUpperCase();
        inspector.icon.innerHTML = data.icon;
        inspector.desc.textContent = data.description;
        inspector.hiddenKey.value = data.key;

        // --- Handle Display Logic ---
        if (data.isMaxLevel === 'true') {
            inspector.details.style.display = 'none';
            inspector.maxLevelNotice.style.display = 'block';
        } else {
            inspector.details.style.display = 'block';
            inspector.maxLevelNotice.style.display = 'none';

            // Populate details
            inspector.level.textContent = `${data.level} / ${data.maxLevel}`;
            inspector.benefit.textContent = data.benefitText;
            
            if (data.costCredits) {
                inspector.costCredits.textContent = `${Number(data.costCredits).toLocaleString()} Credits`;
            } else {
                inspector.costCredits.textContent = 'N/A';
            }
            
            // Handle button state
            if (data.canAfford === 'true') {
                inspector.confirmBtn.disabled = false;
                inspector.confirmBtn.textContent = 'Begin Construction';
            } else {
                inspector.confirmBtn.disabled = true;
                inspector.confirmBtn.textContent = 'Insufficient Funds';
            }
        }
    };
});