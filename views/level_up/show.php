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
        
        <div class="structures-grid">
            <?php foreach ($statConfig as $key => $config): ?>
                <div class="structure-card">
                    <!-- Header -->
                    <div class="card-header-main">
                        <span class="card-icon" style="color: <?= $config['color'] ?>; border-color: <?= $config['color'] ?>33; background: <?= $config['color'] ?>11;">
                            <i class="<?= $config['icon'] ?>"></i>
                        </span>
                        <div class="card-title-group">
                            <h3 class="card-title"><?= ucfirst($key) ?></h3>
                            <p class="card-level" style="color: <?= $config['color'] ?>;">
                                Current: <?= number_format($config['current']) ?>
                            </p>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="card-body-main">
                        <p class="card-description" style="min-height: 40px;">
                            <?= htmlspecialchars($config['desc']) ?>
                        </p>
                        
                        <div class="resource-cost-grid">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Cost per point:</span>
                                <span class="text-warning font-weight-bold">1 Point</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer (Input) -->
                    <div class="card-footer-actions p-3 pt-0">
                        <div class="d-flex align-items-center gap-2" style="background: rgba(0,0,0,0.3); padding: 5px; border-radius: 6px; border: 1px solid var(--border);">
                            <button type="button" class="btn btn-sm btn-outline-warning btn-dec" data-target="<?= $key ?>">
                                <i class="fas fa-minus"></i>
                            </button>
                            
                            <input type="number" 
                                   name="<?= $key ?>" 
                                   id="<?= $key ?>" 
                                   class="form-control stat-input text-center p-1" 
                                   value="0" 
                                   min="0" 
                                   max="<?= $stats->level_up_points ?>"
                                   style="border: none; background: transparent; height: 30px; font-size: 1.1rem; font-weight: bold;">
                            
                            <button type="button" class="btn btn-sm btn-outline-success btn-inc" data-target="<?= $key ?>">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Sticky Footer for Confirmation -->
        <div class="hud-floating-bar" id="level-up-hud" style="bottom: 20px; right: 20px; left: auto; width: 300px; display: block; animation: none;">
            <div class="hud-header">
                <h3><i class="fas fa-dna"></i> Augmentation Queue</h3>
            </div>
            
            <div class="hud-content p-3">
                <div class="hud-total-row mb-2">
                    <span>Available:</span>
                    <strong class="text-success"><?= number_format($stats->level_up_points) ?></strong>
                </div>
                <div class="hud-total-row mb-3">
                    <span>Allocated:</span>
                    <strong class="text-warning" id="total-allocated">0</strong>
                </div>
                
                <div class="progress mb-3" style="height: 6px; background: rgba(255,255,255,0.1);">
                    <div class="progress-bar bg-warning" id="allocation-bar" style="width: 0%;"></div>
                </div>

                <button type="submit" class="btn btn-primary w-100" id="btn-confirm" disabled>
                    <i class="fas fa-check-circle"></i> Confirm Upgrades
                </button>
            </div>
        </div>

    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const maxPoints = <?= (int)$stats->level_up_points ?>;
    const inputs = document.querySelectorAll('.stat-input');
    const totalAllocatedDisplay = document.getElementById('total-allocated');
    const allocationBar = document.getElementById('allocation-bar');
    const confirmBtn = document.getElementById('btn-confirm');
    const headerDisplay = document.getElementById('header-points-display');

    function updateTotals() {
        let totalUsed = 0;
        inputs.forEach(input => {
            let val = parseInt(input.value) || 0;
            if (val < 0) { val = 0; input.value = 0; }
            totalUsed += val;
        });

        // Update UI
        totalAllocatedDisplay.innerText = totalUsed;
        const remaining = maxPoints - totalUsed;
        headerDisplay.innerText = remaining.toLocaleString();
        
        // Progress Bar
        const pct = Math.min(100, (totalUsed / maxPoints) * 100);
        allocationBar.style.width = pct + '%';

        // Validation
        if (totalUsed > maxPoints) {
            totalAllocatedDisplay.classList.add('text-danger');
            totalAllocatedDisplay.classList.remove('text-warning');
            confirmBtn.disabled = true;
            confirmBtn.innerText = 'Insufficient Points';
            confirmBtn.classList.add('btn-secondary');
            confirmBtn.classList.remove('btn-primary');
        } else if (totalUsed === 0) {
            confirmBtn.disabled = true;
            confirmBtn.innerText = 'No Changes';
        } else {
            totalAllocatedDisplay.classList.remove('text-danger');
            totalAllocatedDisplay.classList.add('text-warning');
            confirmBtn.disabled = false;
            confirmBtn.innerText = 'Confirm Upgrades';
            confirmBtn.classList.add('btn-primary');
            confirmBtn.classList.remove('btn-secondary');
        }
    }

    // Input Listeners
    inputs.forEach(input => {
        input.addEventListener('input', updateTotals);
    });

    // +/- Button Listeners
    document.querySelectorAll('.btn-inc').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.target;
            const input = document.getElementById(targetId);
            
            // Calculate current total to prevent going over in real-time
            let currentTotal = 0;
            inputs.forEach(i => currentTotal += (parseInt(i.value) || 0));
            
            if (currentTotal < maxPoints) {
                input.value = (parseInt(input.value) || 0) + 1;
                updateTotals();
            }
        });
    });

    document.querySelectorAll('.btn-dec').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.target;
            const input = document.getElementById(targetId);
            const val = parseInt(input.value) || 0;
            if (val > 0) {
                input.value = val - 1;
                updateTotals();
            }
        });
    });

    // Initial State
    updateTotals();
});
</script>
