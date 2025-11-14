<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\UserResource $userResources */
/* @var \App\Models\Entities\UserStructure $userStructures */
/* @var array $armoryConfig */
/* @var array $inventory (e.g., ['pulse_rifle' => 100]) */
/* @var array $loadouts (e.g., ['soldier' => ['main_weapon' => 'pulse_rifle']]) */
/* @var array $itemLookup (e.g., ['pulse_rifle' => 'Pulse Rifle']) */
?>

<style>
    :root {
        --bg: radial-gradient(circle at 10% 0%, #0c101e 0%, #050712 42%, #02030a 75%);
        --bg-panel: rgba(12, 14, 25, 0.65);
        --card: radial-gradient(circle at 30% -10%, rgba(45, 209, 209, 0.07), rgba(13, 15, 27, 0.6));
        --border: rgba(255, 255, 255, 0.03);
        --accent: #2dd1d1; /* Main Accent (Teal) */
        --accent-soft: rgba(45, 209, 209, 0.12);
        --accent-2: #f9c74f; /* Secondary Accent (Gold) */
        --accent-red: #e53e3e;
        --text: #eff1ff;
        --muted: #a8afd4;
        --radius: 18px;
        --shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
    }

    /* --- CSS FIX: Box sizing reset --- */
    *, *::before, *::after {
        box-sizing: border-box;
    }

    /* --- CSS FIX: Removed redundant body background --- */

        .armory-container {
        width: 100%;
        max-width: 1400px;
        margin-inline: auto;
        /* --- CSS FIX: Removed horizontal padding --- */
        padding: 1.5rem 0 3.5rem; /* Was 1.5rem 1.5rem 3.5rem */
        position: relative;
    }
    
    /* --- CSS FIX: Removed horizontal negative inset --- */
    .armory-container::before {
        content: "";
        position: absolute;
        inset: -80px 0 0 0; /* Was -80px -120px 0 */
        background-image:
            linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 0),
            linear-gradient(0deg, rgba(255,255,255,0.015) 1px, transparent 0);
        background-size: 120px 120px;
        pointer-events: none;
        z-index: -1;
    }

    .armory-container h1 {
        text-align: center;
        margin-bottom: 0.25rem;
        font-size: clamp(2.1rem, 3vw, 2.6rem);
        letter-spacing: -0.03em;
        color: #fff;
    }

    /* --- Header Card --- */
    .armory-header-card {
        background: radial-gradient(circle at top, rgba(45, 209, 209, 0.12), rgba(11, 13, 24, 0.9));
        border: 1px solid rgba(45, 209, 209, 0.4);
        border-radius: var(--radius);
        padding: 1rem 1.5rem;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-around;
        gap: 1.5rem;
        box-shadow: var(--shadow);
        backdrop-filter: blur(6px);
        margin-bottom: 2rem;
    }
    .header-stat {
        text-align: center;
        flex-grow: 1;
    }
    .header-stat span {
        display: block;
        font-size: 0.9rem;
        color: var(--muted);
        margin-bottom: 0.25rem;
    }
    .header-stat strong {
        font-size: 1.5rem;
        color: #fff;
    }
    .header-stat strong.accent {
        color: var(--accent-2); /* Gold for credits */
    }
    .header-stat strong.accent-teal {
        color: var(--accent);
    }

    /* --- Tab Navigation --- */
    .armory-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }
    .tab-link {
        padding: 0.75rem 1.25rem;
        border: 1px solid var(--border);
        border-bottom: none;
        background: var(--card);
        color: var(--muted);
        font-weight: 600;
        font-size: 0.9rem;
        border-radius: 12px 12px 0 0;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
        top: 1px; /* Sits on top of the border */
    }
    .tab-link:hover {
        background: rgba(255,255,255, 0.03);
        color: #fff;
    }
    .tab-link.active {
        background: var(--bg); /* Match main bg */
        border-color: rgba(255, 255, 255, 0.05);
        border-bottom: 1px solid var(--bg);
        color: var(--accent);
    }

    /* --- Tab Content --- */
    .tab-content {
        display: none;
    }
    .tab-content.active {
        display: block;
    }
    
    /* This is the OUTER grid (Equip Card | Item Grid) */
    .unit-grid {
        display: grid;
        grid-template-columns: 1fr 2fr; /* 1:2 ratio on desktop */
        gap: 1.5rem;
    }

    /* This is the INNER grid (for the Item Cards) */
    .item-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr); /* 3 item cards per row */
        gap: 1.5rem;
    }
    
    /* --- Responsive Fixes --- */
    @media (max-width: 1200px) {
        .item-grid {
            grid-template-columns: repeat(2, 1fr); /* 2 item cards on tablet */
        }
    }
    
    @media (max-width: 980px) { /* Stacks the Equip card on top of items */
        .unit-grid {
            grid-template-columns: 1fr; /* Stack outer grid */
        }
    }

    @media (max-width: 768px) {
        .item-grid {
            grid-template-columns: 1fr; /* 1 item card on mobile */
        }
    }


    /* --- Section Headers (from structures.php) --- */
    .section-header {
        grid-column: 1 / -1; 
        color: #ffffff;
        font-size: 0.95rem;
        font-weight: 600;
        border-bottom: 1px solid rgba(249, 199, 79, 0.06);
        padding-bottom: 0.3rem;
        margin: 0.6rem 0 0.2rem 0;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .section-header::before {
        content: "";
        width: 5px;
        height: 18px;
        border-radius: 999px;
        background: linear-gradient(180deg, var(--accent), rgba(2, 3, 10, 0));
        box-shadow: 0 0 20px rgba(45, 209, 209, 0.35);
    }
    
    /* --- Equip Card --- */
    .equip-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.25rem 1.5rem;
        box-shadow: var(--shadow);
        backdrop-filter: blur(6px);
    }
    .equip-card h3 {
        color: #fff;
        margin: 0 0 1rem 0;
        font-size: 1rem;
        letter-spacing: 0.02em;
        border-bottom: 1px solid var(--border);
        padding-bottom: 0.75rem;
    }
    .equip-card .form-group { margin-bottom: 1rem; }
    .equip-card .form-group:last-child { margin-bottom: 0; }
    
    /* --- Item (Manufacture) Card (from structures.php) --- */
    .item-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.25rem 1.5rem;
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
        transition: transform 0.1s ease-out, border 0.1s ease-out;
    }
    .item-card:hover {
        transform: translateY(-2px);
        border: 1px solid rgba(45, 209, 209, 0.4);
    }
    .item-card h4 {
        color: #fff;
        margin: 0 0 0.25rem 0;
        font-size: 1.1rem;
    }
    .item-card .item-notes {
        font-size: 0.85rem;
        color: var(--muted);
        margin: 0 0 1rem 0;
        font-style: italic;
        flex-grow: 1;
        line-height: 1.4;
    }
    .item-stats {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    .stat-pill {
        font-size: 0.8rem;
        font-weight: 600;
        color: #fff;
        padding: 0.25rem 0.6rem;
        border-radius: 999px;
    }
    .stat-pill.attack {
        background: rgba(229, 62, 62, 0.2);
        border: 1px solid rgba(229, 62, 62, 0.5);
    }
    .stat-pill.defense {
        background: rgba(45, 209, 209, 0.2);
        border: 1px solid rgba(45, 209, 209, 0.5);
    }
    
    .item-info { list-style: none; padding: 0; margin: 0 0 1rem 0; }
    .item-info li {
        font-size: 0.9rem;
        color: var(--muted);
        padding: 0.35rem 0;
        display: flex;
        justify-content: space-between;
        border-bottom: 1px solid var(--border);
    }
    .item-info li:last-child { border: none; }
    .item-info li strong {
        color: var(--text);
        font-weight: 600;
    }
    .item-info li .status-ok { color: var(--accent); }
    .item-info li .status-bad { color: var(--accent-red); }
    
    .amount-input-group {
        display: flex;
        gap: 0.5rem;
    }
    .amount-input-group input {
        flex-grow: 1;
        min-width: 50px; 
    }
    .amount-input-group button {
        margin-top: 0;
        flex-shrink: 0; /* Button will not shrink */
    }
    
    .item-card .btn-submit {
        margin-top: 0;
        border: none;
        background: linear-gradient(120deg, var(--accent) 0%, #1f8ac5 100%);
        color: #fff;
        padding: 0.6rem 0.75rem;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: filter 0.1s ease-out, transform 0.1s ease-out;
    }
    
    .item-card .btn-submit:not([disabled]):hover {
        filter: brightness(1.02);
        transform: translateY(-1px);
    }
    .item-card .btn-submit[disabled] {
        background: rgba(187, 76, 76, 0.15);
        border: 1px solid rgba(187, 76, 76, 0.3);
        color: rgba(255, 255, 255, 0.35);
        cursor: not-allowed;
    }

    .item-card .btn-manufacture-submit {
        width: 100%;
        margin-top: 0.75rem;
    }
</style>

<div class="armory-container">
    <h1>Armory</h1>

    <div class="armory-header-card">
        <div class="header-stat">
            <span>Credits on Hand</span>
            <strong class="accent" id="global-user-credits" data-credits="<?= $userResources->credits ?>">
                <?= number_format($userResources->credits) ?>
            </strong>
        </div>
        <div class="header-stat">
            <span>Armory Level</span>
            <strong class="accent-teal" id="global-armory-level" data-level="<?= $userStructures->armory_level ?>">
                Level <?= $userStructures->armory_level ?>
            </strong>
        </div>
    </div>

    <div class="armory-tabs">
        <?php $i = 0; ?>
        <?php foreach ($armoryConfig as $unitKey => $unitData): ?>
            <a class="tab-link <?= $i === 0 ? 'active' : '' ?>" data-tab="tab-<?= $unitKey ?>">
                <?= htmlspecialchars($unitData['title']) ?>
            </a>
            <?php $i++; ?>
        <?php endforeach; ?>
    </div>

    <?php $i = 0; ?>
    <?php foreach ($armoryConfig as $unitKey => $unitData): ?>
        <?php 
            $unitResourceKey = $unitData['unit']; // e.g., 'soldiers'
            $unitCount = $userResources->{$unitResourceKey} ?? 0;
        ?>
        <div id="tab-<?= $unitKey ?>" class="tab-content <?= $i === 0 ? 'active' : '' ?>" data-unit-key="<?= $unitKey ?>" data-unit-count="<?= $unitCount ?>">
            
            <div class="unit-grid">
                <div class="equip-card">
                    <h3>Current Loadout (<?= number_format($unitCount) ?> <?= htmlspecialchars(ucfirst($unitResourceKey)) ?>)</h3>
                    
                    <form action="/armory/equip" method="POST" class="equip-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                        <input type="hidden" name="unit_key" value="<?= $unitKey ?>">
                        
                        <?php foreach ($unitData['categories'] as $categoryKey => $categoryData): ?>
                            <div class="form-group">
                                <label for="equip-<?= $unitKey ?>-<?= $categoryKey ?>">
                                    <?= htmlspecialchars($categoryData['title']) ?>
                                </label>
                                <select class="equip-select" 
                                        data-category-key="<?= $categoryKey ?>">
                                    <option value="">-- None Equipped --</option>
                                    <?php 
                                        $currentlyEquipped = $loadouts[$unitKey][$categoryKey] ?? null;
                                        foreach ($categoryData['items'] as $itemKey => $item): 
                                            $owned = (int)($inventory[$itemKey] ?? 0);
                                    ?>
                                        <option value="<?= $itemKey ?>" <?= $currentlyEquipped === $itemKey ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($item['name']) ?> (Owned: <?= number_format($owned) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                        
                        <input type="hidden" name="category_key" class="dynamic-category-key" value="">
                        <input type="hidden" name="item_key" class="dynamic-item-key" value="">
                    </form>
                </div>

                <div>
                    <div class="item-grid">
                        <?php foreach ($unitData['categories'] as $categoryKey => $categoryData): ?>
                            <h2 class="section-header"><?= htmlspecialchars($categoryData['title']) ?></h2>

                            <?php foreach ($categoryData['items'] as $itemKey => $item): ?>
                                <?php
                                $isTier1 = !isset($item['requires']);
                                $prereqKey = $item['requires'] ?? null;
                                $prereqName = $prereqKey ? ($itemLookup[$prereqKey] ?? 'N/A') : 'N/A';
                                $prereqOwned = (int)($inventory[$prereqKey] ?? 0);
                                $currentOwned = (int)($inventory[$itemKey] ?? 0);
                                $armoryLvlReq = $item['armory_level_req'] ?? 0;
                                $cost = $item['cost'];
                                $hasLvl = $userStructures->armory_level >= $armoryLvlReq;
                                $canManufacture = $hasLvl && ($isTier1 || $prereqOwned > 0);
                                ?>
                                <div class="item-card">
                                    <h4><?= htmlspecialchars($item['name']) ?></h4>
                                    <p class="item-notes"><?= htmlspecialchars($item['notes']) ?></p>
                                    
                                    <div class="item-stats">
                                        <?php if (isset($item['attack'])): ?>
                                            <span class="stat-pill attack">+<?= $item['attack'] ?> Attack</span>
                                        <?php endif; ?>
                                        <?php if (isset($item['defense'])): ?>
                                            <span class="stat-pill defense">+<?= $item['defense'] ?> Defense</span>
                                        <?php endif; ?>
                                    </div>

                                    <ul class="item-info">
                                        <li><span>Cost (Credits):</span> <strong><?= number_format($cost) ?></strong></li>
                                        <li>
                                            <span>Armory Level Req:</span>
                                            <strong class="<?= $hasLvl ? 'status-ok' : 'status-bad' ?>">
                                                Lvl <?= $armoryLvlReq ?>
                                            </strong>
                                        </li>
                                        <li>
                                            <span>Requires:</span>
                                            <strong><?= htmlspecialchars($prereqName) ?></strong>
                                        </li>
                                        <li>
                                            <span>Prereq. Owned:</span>
                                            <strong data-inventory-key="<?= $prereqKey ?>"><?= number_format($prereqOwned) ?></strong>
                                        </li>
                                        <li><span>Currently Owned:</span> <strong data-inventory-key="<?= $itemKey ?>"><?= number_format($currentOwned) ?></strong></li>
                                    </ul>

                                    <form action="/armory/manufacture" method="POST" class="manufacture-form">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                        <input type="hidden" name="item_key" value="<?= $itemKey ?>">
                                        
                                        <div class="form-group">
                                            <div class="amount-input-group">
                                                <input type="number" name="quantity" class="manufacture-amount" min="1" placeholder="Amount" required 
                                                    data-item-key="<?= $itemKey ?>"
                                                    data-item-cost="<?= $cost ?>"
                                                    data-prereq-key="<?= $prereqKey ?>"
                                                    data-current-owned="<?= $currentOwned ?>"
                                                >
                                                <button type="button" class="btn-submit btn-max-manufacture">Max</button>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn-submit btn-manufacture-submit" <?= !$canManufacture ? 'disabled' : '' ?>>
                                            <?= $isTier1 ? 'Manufacture' : 'Upgrade' ?>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php $i++; ?>
    <?php endforeach; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. Tabbed Interface Logic ---
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');

    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const tabId = this.getAttribute('data-tab');

            tabLinks.forEach(l => l.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });

    // --- 2. "Equip" Form Logic (Auto-submit on change) ---
    document.querySelectorAll('.equip-select').forEach(select => {
        select.addEventListener('change', function() {
            const form = this.closest('form');
            const categoryKey = this.getAttribute('data-category-key');
            const itemKey = this.value; // Get the item key from the selected option
            
            // Find the hidden inputs
            const categoryInput = form.querySelector('.dynamic-category-key');
            const itemInput = form.querySelector('.dynamic-item-key');

            // Set the values of the hidden inputs
            categoryInput.value = categoryKey;
            itemInput.value = itemKey;
            
            // Now submit the form
            form.submit();
        });
    });

    // --- 3. "Max Manufacture/Upgrade" Button Logic ---
    const USER_CREDITS = parseInt(document.getElementById('global-user-credits').getAttribute('data-credits'), 10);
    const inventory = {};
    
    // Create a live map of all inventory counts
    document.querySelectorAll('[data-inventory-key]').forEach(el => {
        const key = el.getAttribute('data-inventory-key');
        if (key && key !== 'null') {
            // Use a regex to remove commas before parsing
            const count = parseInt(el.textContent.replace(/,/g, ''), 10);
            inventory[key] = isNaN(count) ? 0 : count;
        }
    });

    document.querySelectorAll('.btn-max-manufacture').forEach(button => {
        button.addEventListener('click', function() {
            const form = this.closest('.manufacture-form');
            const amountInput = form.querySelector('.manufacture-amount');
            
            const itemCost = parseInt(amountInput.getAttribute('data-item-cost'), 10);
            const currentOwned = parseInt(amountInput.getAttribute('data-current-owned'), 10);
            const prereqKey = amountInput.getAttribute('data-prereq-key');
            
            const tab = this.closest('.tab-content');
            const unitCount = parseInt(tab.getAttribute('data-unit-count'), 10);

            let maxFromCredits = Infinity;
            let maxFromPrereq = Infinity;
            let maxNeededForArmy = Infinity;

            // Constraint 1: User's credits
            if (itemCost > 0) {
                maxFromCredits = Math.floor(USER_CREDITS / itemCost);
            }

            // Constraint 2: Prerequisite item (if it's an upgrade)
            if (prereqKey && prereqKey !== 'null') {
                maxFromPrereq = inventory[prereqKey] || 0;
            }

            // Constraint 3: How many are needed for the full army
            if (unitCount > currentOwned) {
                maxNeededForArmy = unitCount - currentOwned;
            } else {
                maxNeededForArmy = 0; // Already have enough for the army
            }
            
            // Find the *smallest* constraint
            const finalMax = Math.min(maxFromCredits, maxFromPrereq, maxNeededForArmy);
            
            amountInput.value = finalMax > 0 ? finalMax : 0;
        });
    });

});
</script>