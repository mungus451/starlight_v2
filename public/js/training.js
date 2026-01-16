/**
 * training.js
 * Starlight Dominion - Tactical Command Interface
 * Handles Unit Selection, Inspector Updates, and Ghost Resource Simulation
 */

// Global State
let currentUnit = null;
let currentMax = 0;
let userCredits = 0;
let userCitizens = 0;

// DOM Elements
const els = {
    rows: [],
    inspTitle: null,
    inspDesc: null,
    inspStats: null,
    inspControls: null,
    inspIcon: null,
    slider: null,
    displayAmt: null,
    formInputType: null,
    formInputAmt: null,
    ghostCredits: null,
    ghostCitizens: null,
    barAtk: null,
    barDef: null,
    valAtk: null,
    valDef: null
};

document.addEventListener('DOMContentLoaded', function() {
    // 1. Initialize DOM Elements
    els.rows = document.querySelectorAll('.unit-row');
    els.inspTitle = document.getElementById('insp-title');
    els.inspDesc = document.getElementById('insp-desc');
    els.inspStats = document.getElementById('insp-stats');
    els.inspControls = document.getElementById('insp-controls');
    els.inspIcon = document.getElementById('insp-icon');
    els.slider = document.getElementById('insp-slider');
    els.displayAmt = document.getElementById('display-amount');
    els.formInputType = document.getElementById('selected-unit-type');
    els.formInputAmt = document.getElementById('selected-amount');
    els.ghostCredits = document.getElementById('ghost-credits');
    els.ghostCitizens = document.getElementById('ghost-citizens');
    
    // Stats
    els.barAtk = document.getElementById('bar-atk');
    els.barDef = document.getElementById('bar-def');
    els.valAtk = document.getElementById('val-atk');
    els.valDef = document.getElementById('val-def');

    // Global Resources
    userCredits = parseInt(document.getElementById('global-credits').dataset.val);
    userCitizens = parseInt(document.getElementById('global-citizens').dataset.val);

    // 2. Setup Slider Listener
    if (els.slider) {
        els.slider.addEventListener('input', (e) => {
            const val = parseInt(e.target.value);
            updateAmount(val);
        });
    }

    // 3. Auto-select first unit if available
    if (els.rows.length > 0) {
        selectUnit(els.rows[0]);
    }
});

/**
 * Handles Unit Selection
 * @param {HTMLElement} rowElement 
 */
function selectUnit(rowElement) {
    // UI: Update Active State
    els.rows.forEach(r => r.classList.remove('active'));
    rowElement.classList.add('active');

    // Data: Update State
    const ds = rowElement.dataset;
    currentUnit = {
        id: ds.unit,
        name: ds.name,
        desc: ds.desc,
        atk: parseInt(ds.atk),
        def: parseInt(ds.def),
        costCr: parseInt(ds.costCredits),
        costCz: parseInt(ds.costCitizens),
        icon: ds.icon,
        max: parseInt(ds.max)
    };

    // Update Global Max reference
    currentMax = currentUnit.max;

    // UI: Update Inspector
    els.inspTitle.textContent = currentUnit.name;
    els.inspDesc.textContent = currentUnit.desc;
    els.inspIcon.className = currentUnit.icon;
    els.inspStats.style.opacity = '1';
    els.inspControls.style.display = 'block';

    // Stats Animation
    animateBar(els.barAtk, els.valAtk, currentUnit.atk);
    animateBar(els.barDef, els.valDef, currentUnit.def);

    // Reset Inputs
    els.slider.max = currentMax;
    els.slider.value = 0;
    updateAmount(0);
    
    // Update Hidden Form Input
    els.formInputType.value = currentUnit.id;
}

/**
 * Updates the selected amount and triggers "Ghost" resource calculation
 */
function updateAmount(amount) {
    if (amount > currentMax) amount = currentMax;
    if (amount < 0) amount = 0;

    // Update UI
    els.slider.value = amount;
    els.displayAmt.textContent = amount;
    els.formInputAmt.value = amount;

    // Calculate Costs
    const totalCr = amount * currentUnit.costCr;
    const totalCz = amount * currentUnit.costCz;

    // Update Ghost Text
    if (amount > 0) {
        els.ghostCredits.textContent = `[-${formatNumber(totalCr)}]`;
        els.ghostCitizens.textContent = `[-${formatNumber(totalCz)}]`;
    } else {
        els.ghostCredits.textContent = '';
        els.ghostCitizens.textContent = '';
    }
}

/**
 * Helper for Quick Buttons (+1, +10, etc)
 */
function adjustAmount(delta) {
    let current = parseInt(els.slider.value) || 0;
    updateAmount(current + delta);
}

function setMax() {
    updateAmount(currentMax);
}

/**
 * Simple Bar Animation
 */
function animateBar(barEl, textEl, value) {
    // Normalize logic: Assume 10 is "High" for basic units
    let pct = (value / 10) * 100; 
    if (pct > 100) pct = 100;
    
    barEl.style.width = `${pct}%`;
    textEl.textContent = value;
}

function formatNumber(num) {
    return new Intl.NumberFormat().format(num);
}

// Expose functions to window for onclick handlers
window.selectUnit = selectUnit;
window.adjustAmount = adjustAmount;
window.setMax = setMax;