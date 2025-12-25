<?php
// --- Mobile Generals View ---
/* @var array $generals */
/* @var \App\Models\Entities\UserResource $resources */
/* @var array $next_cost */
/* @var int $army_cap */
/* @var array $elite_weapons */
/* @var string $csrf_token */

$generalCount = count($generals);
$protoform = $resources->protoform ?? 0;
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">High Command</h1>
        <p style="color: var(--mobile-text-secondary); font-size: 0.9rem; margin: 0.5rem 1rem 0 1rem; text-align: center;">Commission elite generals to lead your fleets.</p>
    </div>

    <!-- Status Card -->
    <div class="mobile-card" style="text-align: center; border-color: var(--mobile-text-secondary);">
        <div class="mobile-card-header" style="justify-content: center;">
            <h3 style="color: var(--mobile-text-secondary);"><i class="fas fa-chess-king"></i> Command Status</h3>
        </div>
        <div class="mobile-card-content" style="display: block;">
            <div style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 15px rgba(0, 140, 255, 0.4);">
                <?= $generalCount ?> Active
            </div>
            <div style="margin-top: 0.5rem; font-size: 0.9rem; color: var(--muted);">
                Total Army Capacity: <strong style="color: var(--mobile-text-primary);"><?= number_format($army_cap) ?></strong>
            </div>
        </div>
    </div>

    <!-- WRAPPER FOR TABS LOGIC -->
    <div class="nested-tabs-container">
        
        <!-- Tab Navigation -->
        <div class="mobile-tabs nested-tabs">
            <a href="#" class="tab-link active" data-tab-target="tab-roster">Roster</a>
            <a href="#" class="tab-link" data-tab-target="tab-recruit">Commission</a>
        </div>

        <!-- Tab Content -->
        <div id="tab-content">
            
            <!-- Roster Tab -->
            <div id="tab-roster" class="nested-tab-content active">
                <?php if (empty($generals)): ?>
                    <p class="text-center text-muted" style="padding: 2rem;">No generals commissioned.</p>
                <?php else: ?>
                    <?php foreach ($generals as $gen): 
                        $weaponName = 'None';
                        if (!empty($gen['weapon_slot_1']) && isset($elite_weapons[$gen['weapon_slot_1']])) {
                            $weaponName = $elite_weapons[$gen['weapon_slot_1']]['name'];
                        }
                    ?>
                    <div class="mobile-card">
                        <div class="mobile-card-header">
                            <h3><i class="fas fa-user-tie"></i> <?= htmlspecialchars($gen['name']) ?></h3>
                            <span class="structure-level" style="color: var(--mobile-text-primary);">Lvl <?= $gen['level'] ?? 1 ?></span>
                        </div>
                        <div class="mobile-card-content" style="display: block;">
                            <div class="structure-benefit" style="margin-bottom: 0.5rem;">
                                <i class="fas fa-gavel"></i> Weapon: <strong><?= htmlspecialchars($weaponName) ?></strong>
                            </div>
                            <div class="structure-description" style="margin-bottom: 1rem; font-size: 0.85rem;">
                                <i class="fas fa-star"></i> XP: <strong><?= number_format($gen['experience'] ?? 0) ?></strong>
                            </div>
                            
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="/generals/armory/<?= $gen['id'] ?>" class="btn btn-outline" style="flex: 1; text-align: center; margin-top: 0;">
                                    <i class="fas fa-shield-alt"></i> Equip
                                </a>
                                <form action="/generals/decommission" method="POST" style="flex: 1;" onsubmit="return confirm('Are you sure you want to decommission this general? This cannot be undone.');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <input type="hidden" name="general_id" value="<?= $gen['id'] ?>">
                                    <button type="submit" class="btn btn-accent" style="width: 100%; margin-top: 0;">
                                        <i class="fas fa-times"></i> Retire
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Commission Tab -->
            <div id="tab-recruit" class="nested-tab-content">
                <div class="mobile-card">
                    <div class="mobile-card-content" style="display: block;">
                        <p class="text-center text-muted">Next Commission Cost:</p>
                        <div style="text-align: center; margin-bottom: 1.5rem;">
                            <div style="font-size: 1.2rem; color: var(--mobile-accent-yellow); font-family: 'Orbitron', sans-serif;">
                                <?= number_format($next_cost['credits']) ?> Credits
                            </div>
                            <div style="font-size: 1.2rem; color: var(--mobile-accent-purple); font-family: 'Orbitron', sans-serif;">
                                <?= number_format($next_cost['protoform']) ?> Protoform
                            </div>
                        </div>

                        <form action="/generals/recruit" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            
                            <div class="form-group">
                                <label for="gen_name">General Name (Optional)</label>
                                <input type="text" name="name" id="gen_name" class="mobile-input" placeholder="e.g. Thrawn" style="width: 100%;">
                            </div>

                            <button type="submit" class="btn" style="width: 100%;">
                                <i class="fas fa-plus-circle"></i> Commission General
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="info-box" style="margin-top: 1rem; font-size: 0.85rem;">
                    <i class="fas fa-info-circle"></i> Each General increases your max unit capacity and can wield powerful Elite Weapons.
                </div>
            </div>

        </div>
    </div> <!-- End nested-tabs-container -->
</div>
