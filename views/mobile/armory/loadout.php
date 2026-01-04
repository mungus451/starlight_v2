<?php
// --- Mobile Loadout Manager View ---
/* @var string $unitKey */
/* @var string $unitName */
/* @var array $categories */
/* @var array $loadout */
/* @var array $eligibleItems */
/* @var array $itemLookup */
/* @var string $csrf_token */
?>

<div class="mobile-content">
    <!-- Header -->
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <a href="/armory" style="color: var(--mobile-text-secondary); font-size: 1.2rem;"><i class="fas fa-arrow-left"></i></a>
            <h1 style="font-family: 'Orbitron', sans-serif; font-size: 1.5rem; margin: 0;">Equip <?= htmlspecialchars($unitName) ?></h1>
            <div style="width: 20px;"></div>
        </div>
    </div>

    <!-- Loadout Slots -->
    <div class="loadout-container">
        <?php foreach ($categories as $catKey => $catData): 
            $equippedKey = $loadout[$catKey] ?? null;
            $equippedName = $equippedKey ? ($itemLookup[$equippedKey] ?? 'Unknown Item') : 'Empty Slot';
            $availableOptions = $eligibleItems[$catKey] ?? [];
            $hasOptions = count($availableOptions) > 0;
            $isExpanded = false; // Default closed
        ?>
            <div class="mobile-card mb-3 slot-card" id="slot-<?= $catKey ?>">
                <!-- Header (Click to Expand) -->
                <div class="mobile-card-header" onclick="toggleSlot('<?= $catKey ?>')" style="cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 class="m-0" style="font-size: 1rem; color: var(--mobile-text-secondary);"><?= htmlspecialchars($catData['title']) ?></h3>
                        <div style="font-size: 1.1rem; color: <?= $equippedKey ? 'var(--mobile-accent-primary)' : '#666' ?>; font-weight: bold;">
                            <?= htmlspecialchars($equippedName) ?>
                        </div>
                    </div>
                    <div>
                        <?php if ($hasOptions): ?>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        <?php else: ?>
                            <span style="font-size: 0.8rem; color: #666;">No Items</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Expansion Panel (Options) -->
                <div class="slot-options" id="options-<?= $catKey ?>" style="display: none; border-top: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2);">
                    
                    <!-- Unequip Option -->
                    <?php if ($equippedKey): ?>
                        <form action="/armory/equip" method="POST" class="equip-option">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <input type="hidden" name="unit_key" value="<?= htmlspecialchars($unitKey) ?>">
                            <input type="hidden" name="category_key" value="<?= htmlspecialchars($catKey) ?>">
                            <input type="hidden" name="item_key" value="">
                            <button type="submit" class="btn-option text-danger">
                                <i class="fas fa-times-circle"></i> Unequip (Clear Slot)
                            </button>
                        </form>
                    <?php endif; ?>

                    <!-- Item Options -->
                    <?php foreach ($availableOptions as $opt): 
                        if ($opt['is_equipped']) continue; // Skip currently equipped
                    ?>
                        <form action="/armory/equip" method="POST" class="equip-option">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <input type="hidden" name="unit_key" value="<?= htmlspecialchars($unitKey) ?>">
                            <input type="hidden" name="category_key" value="<?= htmlspecialchars($catKey) ?>">
                            <input type="hidden" name="item_key" value="<?= htmlspecialchars($opt['key']) ?>">
                            
                            <button type="submit" class="btn-option">
                                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                    <span><?= htmlspecialchars($opt['name']) ?></span>
                                    <span style="font-size: 0.8rem; color: #888;">Owned: <?= number_format($opt['owned']) ?></span>
                                </div>
                                <!-- Stats Mini-Badge -->
                                <div style="font-size: 0.75rem; color: var(--mobile-text-secondary); margin-top: 4px;">
                                    <?php 
                                        $atk = $opt['stats']['attack'] ?? 0;
                                        $def = $opt['stats']['defense'] ?? 0;
                                        if ($atk > 0) echo "+{$atk} Atk ";
                                        if ($def > 0) echo "+{$def} Def ";
                                    ?>
                                </div>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function toggleSlot(catKey) {
    const options = document.getElementById('options-' + catKey);
    const icon = document.querySelector('#slot-' + catKey + ' .toggle-icon');
    
    if (options.style.display === 'none') {
        // Close others first (accordion behavior)
        document.querySelectorAll('.slot-options').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.toggle-icon').forEach(el => el.style.transform = 'rotate(0deg)');
        
        options.style.display = 'block';
        if (icon) icon.style.transform = 'rotate(180deg)';
    } else {
        options.style.display = 'none';
        if (icon) icon.style.transform = 'rotate(0deg)';
    }
}
</script>

<style>
.btn-option {
    width: 100%;
    background: none;
    border: none;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    padding: 15px;
    text-align: left;
    color: var(--mobile-text-primary);
    cursor: pointer;
}
.btn-option:active {
    background: rgba(255,255,255,0.05);
}
.text-danger { color: #ff4444; }
.toggle-icon { transition: transform 0.3s ease; }
</style>
