<?php
/**
 * @var array $general
 * @var \App\Models\Entities\UserResource $resources
 * @var array $elite_weapons
 * @var string $csrf_token
 */

$currentWeaponKey = $general['weapon_slot_1'];
$currentWeapon = ($currentWeaponKey !== null) ? ($elite_weapons[$currentWeaponKey] ?? null) : null;

// Map config resource keys to UserResource properties if slightly different
// Config: 'naquadah_crystals' -> UserResource: 'naquadah_crystals'. Matches.
// Config: 'dark_matter' -> UserResource: 'dark_matter'. Matches.
// Config: 'credits' -> UserResource: 'credits'. Matches.
?>

<div class="container-full">
    <div class="flex-between mb-2">
        <a href="/generals" class="btn-text text-muted" style="text-decoration: none;"><i class="fas fa-chevron-left"></i> Back to Command</a>
        <h1 class="m-0">Elite Armory</h1>
        <div style="width: 100px;"></div> <!-- Spacer -->
    </div>

    <!-- Header Stats -->
    <div class="resource-header-card">
        <div class="header-stat">
            <span>Credits</span>
            <strong class="accent-gold"><?= number_format($resources->credits) ?></strong>
        </div>
        <div class="header-stat">
            <span>Workers</span>
            <strong class="accent"><?= number_format($resources->workers) ?></strong>
        </div>
        <div class="header-stat">
            <span>Soldiers</span>
            <strong class="accent-red"><?= number_format($resources->soldiers) ?></strong>
        </div>
        <div class="header-stat">
            <span>Guards</span>
            <strong class="accent-blue"><?= number_format($resources->guards) ?></strong>
        </div>
        <div class="header-stat">
            <span>Spies</span>
            <strong class="accent-green"><?= number_format($resources->spies) ?></strong>
        </div>
        <div class="header-stat">
            <span>Sentries</span>
            <strong class="accent-green"><?= number_format($resources->sentries) ?></strong>
        </div>
        <div class="header-stat">
            <span>Naquadah</span>
            <strong class="accent-purple"><?= number_format($resources->naquadah_crystals, 0) ?></strong>
        </div>
        <div class="header-stat">
            <span>Dark Matter</span>
            <strong class="accent-blue"><?= number_format($resources->dark_matter) ?></strong>
        </div>
        <div class="header-stat">
            <span>Research</span>
            <strong class="accent"><?= number_format($resources->research_data) ?></strong>
        </div>
    </div>
    
    <!-- General Profile Card -->
    <div class="data-card mb-2" style="border-left: 4px solid var(--accent);">
        <div class="flex-between">
            <div>
                <h2 class="m-0"><?= htmlspecialchars($general['name']) ?></h2>
                <p class="text-muted m-0">Experience Level: <?= $general['experience'] ?? 1 ?></p>
            </div>
            <div class="text-right">
                <span class="d-block text-muted font-08">Current Loadout</span>
                <?php if ($currentWeapon): ?>
                    <strong class="text-accent"><?= htmlspecialchars($currentWeapon['name']) ?></strong>
                <?php else: ?>
                    <span class="italic">Standard Issue Sidearm</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Weapon Registry -->
    <div class="structures-grid">
        <div class="structure-category" style="width:100%;">
            <h2>Available Requisitions</h2>
            <p class="text-muted">Select an artifact to equip. Cost is deducted upon requisition.</p>
        </div>

        <?php foreach ($elite_weapons as $key => $w): ?>
            <?php 
                $isEquipped = ($key === $currentWeaponKey);
                // Check affordability
                $canAfford = true;
                foreach ($w['cost'] as $resKey => $amount) {
                    $userAmt = $resources->{$resKey} ?? 0;
                    if ($userAmt < $amount) {
                        $canAfford = false;
                        break;
                    }
                }
            ?>
            
            <div class="structure-card <?= $isEquipped ? 'max-level' : '' ?>" style="<?= $isEquipped ? 'border-color: var(--accent);' : '' ?>">
                <div class="card-header-main">
                    <span class="card-icon"><i class="fas fa-crosshairs"></i></span>
                    <div class="card-title-group">
                        <h3 class="card-title"><?= htmlspecialchars($w['name']) ?></h3>
                        <p class="card-level"><?= htmlspecialchars($w['archetype']) ?></p>
                    </div>
                </div>

                <div class="card-body-main">
                    <p class="card-description"><?= htmlspecialchars($w['description']) ?></p>
                    
                    <div class="stats-grid font-08 grid-2 gap-05 mt-1">
                        <?php foreach ($w['modifiers'] as $k => $v): ?>
                            <?php if (str_contains($k, 'flat')): ?>
                                <div class="text-green">+<?= number_format($v) ?> <?= ucfirst(str_replace('flat_', '', $k)) ?></div>
                            <?php elseif (str_contains($k, 'mult')): ?>
                                <?php $pct = ($v * 100) - 100; ?>
                                <div class="<?= $pct < 0 ? 'text-red' : 'text-green' ?>">
                                    <?= $pct > 0 ? '+' : '' ?><?= $pct ?>% <?= ucfirst(str_replace(['global_', '_mult'], '', $k)) ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <div class="card-costs-next mt-1">
                        <?php foreach ($w['cost'] as $res => $amount): ?>
                            <div class="cost-item <?= (($resources->{$res} ?? 0) < $amount) ? 'insufficient' : '' ?>">
                                <span class="value"><?= number_format($amount) ?> <?= ucfirst(str_replace(['_', 'crystals'], [' ', ''], $res)) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card-footer-actions">
                    <?php if ($isEquipped): ?>
                        <button class="btn-submit w-full" disabled style="opacity: 0.7; background: var(--accent-dark); border-color: var(--accent);">Active Loadout</button>
                    <?php else: ?>
                        <form action="/generals/equip" method="POST" class="w-full">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="general_id" value="<?= $general['id'] ?>">
                            <input type="hidden" name="weapon_key" value="<?= $key ?>">
                            
                            <button type="submit" class="btn-submit w-full" <?= !$canAfford ? 'disabled' : '' ?>>
                                <?= $canAfford ? 'Requisition' : 'Insufficient Resources' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>