<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\UserResource $userResources */
/* @var \App\Models\Entities\UserStructure $userStructures */
/* @var \App\Models\Entities\UserStats $userStats */
/* @var array $armoryConfig */
/* @var array $inventory */
/* @var array $loadouts */
/* @var array $itemLookup */
/* @var array $discountConfig */

// --- Calculate Discount Percentage for View ---
$charisma = $userStats->charisma_points ?? 0;
$rate = $discountConfig['discount_per_charisma'] ?? 0.01;
$cap = $discountConfig['max_discount'] ?? 0.75;
$discountPercent = min($charisma * $rate, $cap); // e.g., 0.15 for 15%
$hasDiscount = $discountPercent > 0;
?>

<style>
    :root {
        --bg: radial-gradient(circle at 10% 0%, #0c101e 0%, #050712 42%, #02030a 75%);
        --bg-panel: rgba(12, 14, 25, 0.65);
        --card: radial-gradient(circle at 30% -10%, rgba(45, 209, 209, 0.07), rgba(13, 15, 27, 0.6));
        --border: rgba(255, 255, 255, 0.03);
        --accent: #2dd1d1;
        --accent-soft: rgba(45, 209, 209, 0.12);
        --accent-2: #f9c74f;
        --accent-red: #e53e3e;
        --accent-green: #4CAF50;
        --text: #eff1ff;
        --muted: #a8afd4;
        --radius: 18px;
        --shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
    }

    *, *::before, *::after {
        box-sizing: border-box;
    }

    .armory-container {
        width: 100%;
        max-width: 1400px;
        margin-inline: auto;
        padding: 1.5rem 0 3.5rem;
        position: relative;
    }
    
    .armory-container::before {
        content: "";
        position: absolute;
        inset: -80px 0 0 0;
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
    .header-stat strong.accent { color: var(--accent-2); }
    .header-stat strong.accent-teal { color: var(--accent); }
    .header-stat strong.accent-green { color: var(--accent-green); }

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
        top: 1px;
    }
    .tab-link:hover {
        background: rgba(255,255,255, 0.03);
        color: #fff;
    }
    .tab-link.active {
        background: var(--bg);
        border-color: rgba(255, 255, 255, 0.05);
        border-bottom: 1px solid var(--bg);
        color: var(--accent);
    }

    .tab-content { display: none; }
    .tab-content.active { display: block; }
    
    .unit-grid {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 1.5rem;
    }
    .item-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
    }
    
    @media (max-width: 1200px) {
        .item-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 980px) {
        .unit-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
        .item-grid { grid-template-columns: 1fr; }
    }

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
    
    /* --- NEW Discount Styles --- */
    .cost-original {
        text-decoration: line-through;
        color: var(--muted);
        font-size: 0.8em;
        margin-right: 5px;
    }
    .cost-discounted {
        color: var(--accent-2); /* Gold */
    }
    
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
        flex-shrink: 0;
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
        
        <div class="header-stat">
            <span>Charisma Bonus</span>
            <strong class="accent-green">
                <?= number_format($discountPercent * 100, 1) ?>% Discount
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
            $unitResourceKey = $unitData['unit']; 
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
                                $baseCost = $item['cost'];
                                
                                // --- NEW: Effective Cost Calculation for View ---
                                $effectiveCost = (int)floor($baseCost * (1 - $discountPercent));
                                
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
                                        <?php if (isset($item['credit_bonus'])): ?>
                                            <span class="stat-pill defense">+<?= $item['credit_bonus'] ?> Credits</span>
                                        <?php endif; ?>
                                    </div>

                                    <ul class="item-info">
                                        <li>
                                            <span>Cost:</span> 
                                            <div>
                                                <?php if ($hasDiscount): ?>
                                                    <span class="cost-original"><?= number_format($baseCost) ?></span>
                                                    <strong class="cost-discounted"><?= number_format($effectiveCost) ?></strong>
                                                <?php else: ?>
                                                    <strong><?= number_format($baseCost) ?></strong>
                                                <?php endif; ?>
                                            </div>
                                        </li>
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
                                                    data-item-cost="<?= $effectiveCost ?>" 
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

<script src="/js/armory.js"></script>