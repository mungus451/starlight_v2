<?php
// --- Partial View: Structures ---
?>
<div class="mobile-card">
    <div class="mobile-card-header">
        <h3><i class="fas fa-cogs"></i> Structure Network</h3>
    </div>
    <div class="mobile-card-content" style="display: block;">
        <ul class="mobile-stats-list">
            <li><span><i class="fas fa-fort-awesome"></i> Fortification</span> <strong>Lvl <?= $structures->fortification_level ?></strong></li>
            <li><span><i class="fas fa-bolt"></i> Offense Upgrade</span> <strong>Lvl <?= $structures->offense_upgrade_level ?></strong></li>
            <li><span><i class="fas fa-shield-alt"></i> Defense Upgrade</span> <strong>Lvl <?= $structures->defense_upgrade_level ?></strong></li>
            <li><span><i class="fas fa-user-secret"></i> Spy Upgrade</span> <strong>Lvl <?= $structures->spy_upgrade_level ?></strong></li>
            <li><span><i class="fas fa-university"></i> Economy</span> <strong>Lvl <?= $structures->economy_upgrade_level ?></strong></li>
            <li><span><i class="fas fa-users"></i> Population</span> <strong>Lvl <?= $structures->population_level ?></strong></li>
            <li><span><i class="fas fa-tools"></i> Armory</span> <strong>Lvl <?= $structures->armory_level ?></strong></li>
            <li><span><i class="fas fa-flask"></i> Quantum Research Lab</span> <strong>Lvl <?= $structures->quantum_research_lab_level ?></strong></li>
            <li><span><i class="fas fa-industry"></i> Nanite Forge</span> <strong>Lvl <?= $structures->nanite_forge_level ?></strong></li>
            <li><span><i class="fas fa-atom"></i> Dark Matter Siphon</span> <strong>Lvl <?= $structures->dark_matter_siphon_level ?></strong></li>
            <li><span><i class="fas fa-globe-americas"></i> Planetary Shield</span> <strong>Lvl <?= $structures->planetary_shield_level ?></strong></li>
        </ul>
        <a href="/structures" class="btn">Upgrade Structures</a>
    </div>
</div>
