<?php
// --- Partial View: Military ---
?>
<div class="mobile-card">
    <div class="mobile-card-header">
        <h3><i class="fas fa-fist-raised"></i> Power Ratings</h3>
    </div>
    <div class="mobile-card-content" style="display: block;">
        <ul class="mobile-stats-list">
            <li><span><i class="fas fa-bolt value-red"></i> Offense Power</span> <strong class="value-red"><?= number_format($offenseBreakdown['total']) ?></strong></li>
            <li><span><i class="fas fa-shield-alt value-blue"></i> Defense Rating</span> <strong class="value-blue"><?= number_format($defenseBreakdown['total']) ?></strong></li>
            <li><span><i class="fas fa-user-secret value-green"></i> Spy Power</span> <strong class="value-green"><?= number_format($spyBreakdown['total']) ?></strong></li>
            <li><span><i class="fas fa-broadcast-tower value-green"></i> Sentry Power</span> <strong class="value-green"><?= number_format($sentryBreakdown['total']) ?></strong></li>
        </ul>
    </div>
</div>

<div class="mobile-card">
    <div class="mobile-card-header">
        <h3><i class="fas fa-tools"></i> Armory</h3>
    </div>
    <div class="mobile-card-content" style="display: block;">
        <ul class="mobile-stats-list">
            <li><span><i class="fas fa-gavel"></i> Attack Weapon</span> <strong><?= htmlspecialchars($armory_loadout['attack_weapon_name'] ?? 'None') ?></strong></li>
            <li><span><i class="fas fa-shield-alt"></i> Defense Weapon</span> <strong><?= htmlspecialchars($armory_loadout['defense_weapon_name'] ?? 'None') ?></strong></li>
            <li><span><i class="fas fa-user-secret"></i> Spy Weapon</span> <strong><?= htmlspecialchars($armory_loadout['spy_weapon_name'] ?? 'None') ?></strong></li>
            <li><span><i class="fas fa-broadcast-tower"></i> Sentry Weapon</span> <strong><?= htmlspecialchars($armory_loadout['sentry_weapon_name'] ?? 'None') ?></strong></li>
        </ul>
        <a href="/armory" class="btn">Manage Armory</a>
    </div>
</div>
