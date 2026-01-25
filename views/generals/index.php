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
    <h1>Elite Units</h1>
    <p class="subtitle" style="text-align: center; color: var(--muted); margin-top: -1rem; margin-bottom: 2rem;">
        Command elite officers to lead your armies and equip them with signature artifacts.
    </p>

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

    <!-- Army Capacity Bar -->
    <div class="data-card mb-2" style="border-color: var(--accent);">
        <h3 class="text-accent">Army Logistics</h3>
        <div class="flex-between">
            <span>Total Soldiers: <?= number_format($resources->soldiers) ?></span>
            <span>Max Capacity: <?= number_format($army_cap) ?></span>
        </div>
        
        <div class="progress-bar-bg mt-05" style="background: rgba(255,255,255,0.1); height: 10px; border-radius: 5px; overflow: hidden;">
            <?php 
                $effectiveSoldiers = min($resources->soldiers, $army_cap);
                $effectivePct = ($effectiveSoldiers / max(1, $army_cap)) * 100;
            ?>
            <div class="progress-bar-fill" style="width: <?= $effectivePct ?>%; background: var(--accent); height: 100%;"></div>
        </div>
        <?php if ($resources->soldiers > $army_cap): ?>
            <p class="text-red font-08 mt-05">
                <?= number_format($resources->soldiers - $army_cap) ?> soldiers are over capacity and contribute no power.
            </p>
        <?php endif; ?>
    </div>

    <div class="structures-grid">
        
        <!-- SECTION 1: GENERALS -->
        <div class="structure-category">
            <h2>High Command (Generals)</h2>
        </div>

        <?php foreach ($generals as $gen): ?>
            <?php $weapon = $getWeapon($gen['weapon_slot_1']); ?>
            <div class="structure-card">
                <div class="card-header-main">
                    <span class="card-icon"><i class="fas fa-user-tie"></i></span>
                    <div class="card-title-group">
                        <h3 class="card-title"><?= htmlspecialchars($gen['name']) ?></h3>
                        <p class="card-level">Lvl <?= $gen['experience'] ?? 1 ?></p>
                    </div>
                </div>

                <div class="card-body-main">
                    <p class="card-description">
                        Commands +10,000 Soldiers.
                        <?php if ($weapon): ?>
                            <br><strong class="text-accent">Equipped: <?= htmlspecialchars($weapon['name']) ?></strong>
                        <?php else: ?>
                            <br><span class="text-muted">No Signature Weapon</span>
                        <?php endif; ?>
                    </p>
                </div>

                <div class="card-footer-actions grid-2 gap-05" style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                    <a href="/generals/armory/<?= $gen['id'] ?>" class="btn-submit w-full text-center" style="text-decoration:none; display:flex; align-items:center; justify-content:center;">
                        <?= $weapon ? 'Loadout' : 'Armory' ?>
                    </a>
                    
                    <form action="/generals/decommission" method="POST" class="w-full" onsubmit="return confirm('Are you sure you want to decommission <?= htmlspecialchars($gen['name']) ?>? This will reduce your Army Capacity.');">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="general_id" value="<?= $gen['id'] ?>">
                        <button type="submit" class="btn-submit w-full btn-reject" style="background: var(--accent-red); border-color: var(--accent-red);">Dismiss</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Recruit Card -->
        <div class="structure-card" style="border-style: dashed; opacity: 0.9;">
            <div class="card-header-main">
                <span class="card-icon"><i class="fas fa-plus"></i></span>
                <div class="card-title-group">
                    <h3 class="card-title">Commission General</h3>
                    <p class="card-level">New Officer</p>
                </div>
            </div>
            
            <div class="card-body-main">
                <div class="card-costs-next">
                    <div class="cost-item">
                        <span class="value"><?= number_format($next_cost['credits']) ?> Credits</span>
                    </div>
                </div>
            </div>

            <div class="card-footer-actions">
                <form action="/generals/recruit" method="POST" class="w-full">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="text" name="name" placeholder="Name (Optional)" class="w-full mb-05 p-05 bg-dark text-light border-glass rounded" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #fff; margin-bottom: 10px;">
                    <button type="submit" class="btn-submit btn-accent w-full">Commission</button>
                </form>
            </div>
        </div>

    </div>
</div>