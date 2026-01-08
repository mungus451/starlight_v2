<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\UserResource $resources */
/* @var array $units (The new enriched unit data) */
?>

<link rel="stylesheet" href="/css/training.css">

<div class="container-fluid">
    <!-- Header: Tactical Status -->
    <div class="barracks-header">
        <div>
            <h1 class="barracks-title glitch-text" data-text="BARRACKS">BARRACKS</h1>
            <div class="barracks-status">
                SECTOR 7 // COMMAND NODE // <span class="text-neon-blue">ONLINE</span>
            </div>
        </div>
        <div class="header-stats-row">
            <div class="stat-group">
                <span class="stat-label">CREDITS</span>
                <div style="display:flex; align-items:baseline;">
                    <span class="stat-val" id="global-credits" data-val="<?= $resources->credits ?>">
                        <?= number_format($resources->credits) ?>
                    </span>
                    <span id="ghost-credits" class="ghost-val"></span>
                </div>
            </div>
            <div class="stat-group">
                <span class="stat-label">CITIZENS</span>
                <div style="display:flex; align-items:baseline;">
                    <span class="stat-val" id="global-citizens" data-val="<?= $resources->untrained_citizens ?>">
                        <?= number_format($resources->untrained_citizens) ?>
                    </span>
                    <span id="ghost-citizens" class="ghost-val"></span>
                </div>
            </div>
        </div>
    </div>

    <form action="/training/train" method="POST" id="main-train-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
        <input type="hidden" name="unit_type" id="selected-unit-type" value="">
        <input type="hidden" name="amount" id="selected-amount" value="0">

        <div class="barracks-container">
            <!-- LEFT COLUMN: Requisition Grid -->
            <div class="requisition-grid">
                <?php foreach ($units as $key => $unit): ?>
                    <?php
                    $ownedKey = $key;
                    $ownedCount = $resources->{$ownedKey} ?? 0;
                    $maxByCredits = floor($resources->credits / max(1, $unit['credits']));
                    $maxByCitizens = floor($resources->untrained_citizens / max(1, $unit['citizens']));
                    $maxAffordable = min($maxByCredits, $maxByCitizens);
                    ?>
                    
                    <div class="unit-row" 
                         data-unit="<?= $key ?>" 
                         data-name="<?= htmlspecialchars($unit['name']) ?>"
                         data-desc="<?= htmlspecialchars($unit['desc']) ?>"
                         data-atk="<?= $unit['atk'] ?>"
                         data-def="<?= $unit['def'] ?>"
                         data-cost-credits="<?= $unit['credits'] ?>"
                         data-cost-citizens="<?= $unit['citizens'] ?>"
                         data-icon="<?= $unit['icon'] ?>"
                         data-max="<?= $maxAffordable ?>"
                         onclick="selectUnit(this)">
                        
                        <div class="unit-icon-box">
                            <i class="<?= $unit['icon'] ?> text-neon-blue"></i>
                        </div>
                        
                        <div class="unit-info">
                            <h4><?= htmlspecialchars($unit['name']) ?></h4>
                            <div class="meta">
                                Role: <?= htmlspecialchars($unit['role']) ?> | Owned: <?= number_format($ownedCount) ?>
                            </div>
                        </div>

                        <div class="unit-controls">
                            <!-- Quick selection handled by main inspector, but we show affordability here -->
                            <span class="cost-preview text-muted">
                                <?= number_format($unit['credits']) ?> Cr
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- RIGHT COLUMN: Tactical Inspector -->
            <div class="tactical-inspector" id="inspector-panel">
                <div class="inspector-header">
                    <h3 class="inspector-title" id="insp-title">SELECT UNIT</h3>
                </div>

                <div class="wireframe-container">
                    <div class="wireframe-placeholder" id="insp-wireframe">
                        <i id="insp-icon" style="font-size: 3rem; color: var(--accent); position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);"></i>
                    </div>
                </div>

                <div class="inspector-body">
                    <p class="lore-text" id="insp-desc">
                        Initializing tactical database... Select a unit from the requisition grid to view details and commence fabrication.
                    </p>

                    <!-- Dynamic Stats -->
                    <div class="tactical-stats" id="insp-stats" style="opacity: 0.5;">
                        <div class="stat-bar-group">
                            <div class="stat-bar-label"><span>ATTACK POWER</span> <span id="val-atk">0</span></div>
                            <div class="progress-track"><div class="progress-fill" id="bar-atk" style="width: 0%"></div></div>
                        </div>
                        <div class="stat-bar-group">
                            <div class="stat-bar-label"><span>DEFENSE RATING</span> <span id="val-def">0</span></div>
                            <div class="progress-track"><div class="progress-fill" id="bar-def" style="width: 0%"></div></div>
                        </div>
                    </div>

                    <!-- Input Controls (Only visible when unit selected) -->
                    <div id="insp-controls" style="display:none;">
                        <div class="slider-container">
                            <input type="range" id="insp-slider" min="0" value="0" style="width:100%;">
                        </div>
                        
                        <div class="quick-actions" style="margin-bottom: 1rem; justify-content:space-between;">
                            <button type="button" class="btn-pill" onclick="adjustAmount(1)">+1</button>
                            <button type="button" class="btn-pill" onclick="adjustAmount(10)">+10</button>
                            <button type="button" class="btn-pill" onclick="adjustAmount(100)">+100</button>
                            <button type="button" class="btn-pill" onclick="setMax()">MAX</button>
                        </div>

                        <div style="text-align: center; margin-bottom: 1rem;">
                            <span style="font-family: 'Courier New'; font-size: 1.5rem;" id="display-amount">0</span>
                            <span class="text-muted">UNITS</span>
                        </div>

                        <button type="submit" class="fabricate-btn">
                            INITIALIZE FABRICATION
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Ambient Comms Log -->
    <div class="comms-log">
        <span class="ticker-content">
            SYSTEM: BARRACKS ONLINE ... UPDATE: SOLDIER TRAINING EFFICIENCY AT 98% ... INTEL: ENEMY ACTIVITY DETECTED IN SECTOR 4 ... MAINTENANCE: CLONING VAT 3 REQUIRES FLUSH ...
        </span>
    </div>
</div>

<script src="/js/training.js"></script>