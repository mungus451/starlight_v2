<?php
// --- Level Up View ---
/* @var \App\Models\Entities\UserStats $stats */
/* @var array $costs */
/* @var string $csrf_token */

// Stat Configuration
$statConfig = [
    'strength' => [
        'icon' => 'fas fa-fist-raised',
        'color' => 'var(--accent-red)',
        'desc' => 'Increases raw damage output in battles.',
        'current' => $stats->strength_points
    ],
    'constitution' => [
        'icon' => 'fas fa-heart',
        'color' => 'var(--accent-green)',
        'desc' => 'Boosts maximum health and defense.',
        'current' => $stats->constitution_points
    ],
    'wealth' => [
        'icon' => 'fas fa-coins',
        'color' => 'var(--accent-2)', // Gold
        'desc' => 'Enhances passive income generation.',
        'current' => $stats->wealth_points
    ],
    'dexterity' => [
        'icon' => 'fas fa-running',
        'color' => '#00f3ff', // Cyan
        'desc' => 'Improves dodge chance and initiative.',
        'current' => $stats->dexterity_points
    ],
    'charisma' => [
        'icon' => 'fas fa-user-tie',
        'color' => '#b794f4', // Purple
        'desc' => 'Reduces costs and improves diplomacy.',
        'current' => $stats->charisma_points
    ]
];
?>

<link rel="stylesheet" href="/css/level_up.css">

<div class="structures-page-content">
    
    <!-- 1. Page Header -->
    <div class="page-header-container">
        <h1 class="page-title-neon">Command Authorization</h1>
        <p class="page-subtitle-tech">
            Bio-Augmentation // Neural Remapping // Stat Allocation
        </p>
        <div class="flex-center gap-2 mt-2">
            <div class="badge bg-dark border-secondary">
                Current Level <?= $stats->level ?>
            </div>
            <div class="badge bg-dark border-success">
                Available Points: <span id="header-points-display"><?= number_format($stats->level_up_points) ?></span>
            </div>
        </div>
    </div>

    <!-- 2. Main Form Wrapper -->
    <form action="/level-up/spend" method="POST" id="level-up-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
        
        <div class="level-up-container">
            <!-- LEFT COLUMN: Stat Selection -->
            <div class="stat-grid">
                <?php foreach ($statConfig as $key => $config): ?>
                    <div class="stat-row" 
                         data-stat="<?= $key ?>"
                         data-name="<?= ucfirst($key) ?>"
                         data-desc="<?= htmlspecialchars($config['desc']) ?>"
                         data-current="<?= $config['current'] ?>"
                         data-icon="<?= $config['icon'] ?>"
                         data-color="<?= $config['color'] ?>"
                         onclick="selectStat(this)">
                        
                        <div class="stat-icon-box" style="color: <?= $config['color'] ?>;">
                            <i class="<?= $config['icon'] ?>"></i>
                        </div>
                        
                        <div class="stat-info">
                            <h4><?= ucfirst($key) ?></h4>
                            <div class="meta">
                                Current: <?= number_format($config['current']) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- RIGHT COLUMN: Inspector -->
            <div class="stat-inspector" id="inspector-panel">
                <div class="inspector-header">
                    <h3 class="inspector-title" id="insp-title">SELECT STAT</h3>
                </div>

                <div class="wireframe-container">
                    <div class="wireframe-placeholder" id="insp-wireframe">
                        <i id="insp-icon" style="font-size: 3rem; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);"></i>
                    </div>
                </div>

                <div class="inspector-body">
                    <p class="lore-text" id="insp-desc">
                        Select a core attribute from the allocation grid to view details and assign augmentation points.
                    </p>

                    <!-- Input Controls (Only visible when stat selected) -->
                    <div id="insp-controls" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Cost per point:</span>
                            <span class="text-warning font-weight-bold">1 Point</span>
                        </div>
                        
                        <div class="d-flex align-items-center gap-2 mb-3" style="background: rgba(0,0,0,0.3); padding: 5px; border-radius: 6px; border: 1px solid var(--border);">
                            <button type="button" class="btn btn-sm btn-outline-warning btn-dec" data-target="">
                                <i class="fas fa-minus"></i>
                            </button>
                            
                            <input type="number" 
                                   name="amount" 
                                   id="stat-input" 
                                   class="form-control stat-input text-center p-1" 
                                   value="0" 
                                   min="0" 
                                   max="<?= $stats->level_up_points ?>"
                                   style="border: none; background: transparent; height: 30px; font-size: 1.1rem; font-weight: bold;">
                            
                            <button type="button" class="btn btn-sm btn-outline-success btn-inc" data-target="">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>

                        <input type="hidden" name="stat" id="selected-stat" value="">
                        
                        <button type="submit" class="btn btn-primary w-100" id="btn-confirm">
                            <i class="fas fa-check-circle"></i> Confirm Upgrade
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>
<script src="/js/level_up.js?v=<?= time() ?>"></script>
<script src="/js/level_up.js?v=<?= time() ?>"></script>