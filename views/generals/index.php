<?php
/**
 * @var array $generals
 * @var \App\Models\Entities\UserResource $resources
 * @var array $next_cost
 * @var int $army_cap
 * @var array $elite_weapons
 * @var string $csrf_token
 */

$getWeapon = function($key) use ($elite_weapons) {
    if ($key === null) return null;
    return $elite_weapons[$key] ?? null;
};
?>

<div class="container-full">
    <h1>High Command</h1>
    
    <!-- Army Cap Status -->
    <div class="data-card mb-2">
        <h3 class="text-accent">Army Logistics</h3>
        <div class="flex-between">
            <span>Soldiers: <?= number_format($resources->soldiers) ?></span>
            <span>Capacity: <?= number_format($army_cap) ?></span>
        </div>
        <div class="progress-bar-bg mt-05" style="background: rgba(255,255,255,0.1); height: 10px; border-radius: 5px; overflow: hidden;">
            <?php $pct = min(100, ($resources->soldiers / max(1, $army_cap)) * 100); ?>
            <div class="progress-bar-fill" style="width: <?= $pct ?>%; background: <?= $pct >= 100 ? 'var(--accent-red)' : 'var(--accent)' ?>; height: 100%;"></div>
        </div>
        <p class="text-muted font-08 mt-05">
            Each General increases your Army Capacity by 10,000.
        </p>
    </div>

    <!-- Generals Grid -->
    <div class="grid-2 gap-15">
        <?php foreach ($generals as $gen): ?>
            <?php $weapon = $getWeapon($gen['weapon_slot_1']); ?>
            <div class="data-card">
                <div class="flex-between border-bottom pb-05 mb-1">
                    <h3 class="m-0"><?= htmlspecialchars($gen['name']) ?></h3>
                    <span class="text-muted">Lvl <?= $gen['experience'] ?? 1 ?></span>
                </div>
                
                <div class="general-loadout mb-1">
                    <label class="d-block text-muted font-08 mb-05">Signature Weapon</label>
                    <?php if ($weapon): ?>
                        <div class="equipped-weapon p-05 border-glass bg-glass rounded">
                            <strong class="text-accent"><?= htmlspecialchars($weapon['name']) ?></strong>
                            <div class="font-08 mt-025"><?= htmlspecialchars($weapon['description']) ?></div>
                        </div>
                    <?php else: ?>
                        <div class="text-muted italic">None Equipped</div>
                    <?php endif; ?>
                </div>
                
                <button class="btn-submit btn-sm w-full" onclick="openEquipModal(<?= $gen['id'] ?>)">
                    <?= $weapon ? 'Change Weapon' : 'Equip Weapon' ?>
                </button>
            </div>
        <?php endforeach; ?>
        
        <!-- Recruit Card -->
        <div class="data-card flex-center-col text-center" style="border-style: dashed; opacity: 0.8;">
            <h3 class="text-accent">Commission General</h3>
            <p class="mb-1">Expand your army capacity by 10,000.</p>
            
            <div class="cost-box mb-1">
                <div><?= number_format($next_cost['credits']) ?> Credits</div>
                <div><?= number_format($next_cost['protoform']) ?> Protoform</div>
            </div>
            
            <form action="/generals/recruit" method="POST" class="w-full">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="text" name="name" placeholder="Name (Optional)" class="w-full mb-05 p-05 bg-dark text-light border-glass rounded" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                <button type="submit" class="btn-submit btn-accent w-full">Commission</button>
            </form>
        </div>
    </div>
</div>

<!-- Equip Modal -->
<div id="equip-modal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; justify-content:center; align-items:center; backdrop-filter: blur(5px);">
    <div class="modal-content data-card" style="width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <div class="flex-between border-bottom pb-1 mb-1">
            <h3>Select Signature Weapon</h3>
            <button onclick="closeEquipModal()" class="btn-text" style="font-size: 1.5rem;">&times;</button>
        </div>
        
        <form action="/generals/equip" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="general_id" id="modal-general-id">
            
            <div class="weapons-list">
                <?php foreach ($elite_weapons as $key => $w): ?>
                    <label class="weapon-option d-block p-1 border-glass mb-1 rounded cursor-pointer hover-highlight" style="border: 1px solid rgba(255,255,255,0.1); transition: background 0.2s;">
                        <div class="flex-between">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <input type="radio" name="weapon_key" value="<?= $key ?>" required>
                                <strong class="text-accent"><?= htmlspecialchars($w['name']) ?></strong>
                            </div>
                            <span class="badge" style="background: rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 4px; font-size: 0.8rem;"><?= htmlspecialchars($w['archetype']) ?></span>
                        </div>
                        <p class="font-09 mt-05 mb-05 text-muted"><?= htmlspecialchars($w['description']) ?></p>
                        
                        <!-- Stats -->
                        <div class="stats-grid font-08 grid-2 gap-05">
                            <?php foreach ($w['modifiers'] as $k => $v): ?>
                                <?php if (str_contains($k, 'flat')): ?>
                                    <div class="text-green" style="color: var(--accent-green);">+<?= number_format($v) ?> <?= ucfirst(str_replace('flat_', '', $k)) ?></div>
                                <?php elseif (str_contains($k, 'mult')): ?>
                                    <?php $pct = ($v * 100) - 100; ?>
                                    <?php if ($pct < 0): ?>
                                        <div class="text-red" style="color: var(--accent-red);"><?= $pct ?>% <?= ucfirst(str_replace(['global_', '_mult'], '', $k)) ?></div>
                                    <?php else: ?>
                                        <div class="text-green" style="color: var(--accent-green);">+<?= $pct ?>% <?= ucfirst(str_replace(['global_', '_mult'], '', $k)) ?></div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="cost-row mt-05 pt-05 border-top flex gap-1 font-08 text-muted" style="border-top: 1px solid rgba(255,255,255,0.1); gap: 1rem; display: flex;">
                            <?php foreach ($w['cost'] as $res => $amount): ?>
                                <span><?= number_format($amount) ?> <?= ucfirst(str_replace(['_', 'crystals'], [' ', ''], $res)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
            
            <button type="submit" class="btn-submit w-full mt-1">Confirm Requisition</button>
        </form>
    </div>
</div>

<script>
function openEquipModal(id) {
    document.getElementById('modal-general-id').value = id;
    document.getElementById('equip-modal').style.display = 'flex';
}
function closeEquipModal() {
    document.getElementById('equip-modal').style.display = 'none';
}

// Hover effect for labels
document.querySelectorAll('.weapon-option').forEach(opt => {
    opt.addEventListener('mouseenter', () => opt.style.background = 'rgba(255,255,255,0.05)');
    opt.addEventListener('mouseleave', () => opt.style.background = 'transparent');
});
</script>