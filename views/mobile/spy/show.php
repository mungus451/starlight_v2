<?php
// --- Mobile Spy Operations View ---
/* @var \App\Models\Entities\UserResource $resources */
/* @var \App\Models\Entities\UserStats $stats */
/* @var array $costs */
/* @var array $targets */
/* @var array $operation */
/* @var string $csrf_token */

$spies = $resources->spies;
$creditCost = $operation['total_credit_cost'];
$turnCost = $operation['turn_cost'];
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">Intelligence</h1>
        <p style="color: var(--mobile-text-secondary); font-size: 0.9rem; margin: 0.5rem 1rem 0 1rem; text-align: center;">Gather intel and sabotage rival commanders.</p>
    </div>

    <!-- Status Card -->
    <div class="mobile-card" style="text-align: center; border-color: var(--mobile-accent-green);">
        <div class="mobile-card-header" style="justify-content: center;">
            <h3 style="color: var(--mobile-accent-green);"><i class="fas fa-user-secret"></i> Spy Network</h3>
        </div>
        <div class="mobile-card-content" style="display: block;">
            <div style="display: flex; justify-content: space-around; font-size: 0.9rem;">
                <div>
                    <span class="text-muted">Active Spies</span><br>
                    <strong style="font-size: 1.2rem; color: #fff;"><?= number_format($spies) ?></strong>
                </div>
                <div>
                    <span class="text-muted">Attack Turns</span><br>
                    <strong style="font-size: 1.2rem; color: #fff;"><?= number_format($stats->attack_turns) ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Operation Card -->
    <div class="mobile-card" style="border-color: var(--mobile-accent-purple); box-shadow: 0 0 20px rgba(189, 0, 255, 0.2);">
        <div class="mobile-card-header" style="background: rgba(189, 0, 255, 0.05);">
            <h3 style="color: var(--mobile-accent-purple);"><i class="fas fa-crosshairs"></i> New Operation</h3>
        </div>
        <div class="mobile-card-content" style="display: block;">
            <form action="/spy/conduct" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                
                <div class="form-group">
                    <label for="target_name">Target Commander</label>
                    <input type="text" name="target_name" id="target_name" class="mobile-input" placeholder="Enter Name" required style="width: 100%;">
                </div>

                <div class="info-box" style="margin: 1rem 0; font-size: 0.85rem; text-align: left; background: rgba(0,0,0,0.3); border-color: rgba(189, 0, 255, 0.3);">
                    <div style="display: flex; justify-content: space-between;">
                        <span>Sending:</span>
                        <strong>ALL Spies (<?= number_format($spies) ?>)</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Cost:</span>
                        <strong><?= number_format($creditCost) ?> ₡</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Turns:</span>
                        <strong><?= number_format($turnCost) ?></strong>
                    </div>
                </div>

                <button type="submit" class="btn btn-special" style="width: 100%; background: linear-gradient(90deg, #bd00ff 0%, #7e00ff 100%); color: #fff; box-shadow: 0 0 15px rgba(189, 0, 255, 0.5);" <?= ($spies > 0 && $resources->credits >= $creditCost && $stats->attack_turns >= $turnCost) ? '' : 'disabled' ?>>
                    <i class="fas fa-eye"></i> Execute Mission
                </button>
            </form>
        </div>
    </div>

    <!-- Navigation Buttons -->
    <a href="/spy/reports" class="btn btn-outline" style="margin-bottom: 1.5rem; text-align: center;">
        <i class="fas fa-file-alt"></i> View Report Archives
    </a>

    <!-- Target List (If available) -->
    <?php if (!empty($targets)): 
        $page = $pagination['currentPage'];
        $totalPages = $pagination['totalPages'];
        $prevPage = $page > 1 ? $page - 1 : null;
        $nextPage = $page < $totalPages ? $page + 1 : null;
    ?>
        <h3 class="mobile-category-header">Potential Targets</h3>
        
        <!-- Limit Options -->
        <div class="mobile-tabs" style="justify-content: center; margin-bottom: 1rem; gap: 0.5rem;">
            <span style="color: var(--muted); font-size: 0.9rem; align-self: center;">Show:</span>
            <?php foreach ([5, 10, 25, 100] as $opt): ?>
                <a href="/spy/page/1?limit=<?= $opt ?>" 
                   class="tab-link <?= $perPage == $opt ? 'active' : '' ?>"
                   style="padding: 0.25rem 0.75rem; font-size: 0.8rem;">
                   <?= $opt == 100 ? 'ALL' : $opt ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="leaderboard-list">
            <?php foreach ($targets as $target): ?>
                <div class="mobile-card" style="padding: 0.75rem; display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <strong style="color: var(--mobile-text-primary); font-size: 1.1rem;"><?= htmlspecialchars($target['character_name']) ?></strong>
                        <div style="font-size: 0.8rem; color: var(--muted);">Rank #<?= $target['rank'] ?? '?' ?></div>
                    </div>
                    <button class="btn btn-sm" style="width: auto; padding: 0.5rem 1rem;" onclick="document.getElementById('target_name').value = '<?= htmlspecialchars($target['character_name']) ?>'; window.scrollTo(0,0);">
                        Select
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="mobile-tabs" style="justify-content: space-between; margin-top: 1rem;">
            <?php if ($prevPage): ?>
                <a href="/spy/page/<?= $prevPage ?>?limit=<?= $perPage ?>" class="btn">
                    <i class="fas fa-chevron-left"></i> Prev
                </a>
            <?php else: ?>
                <span class="btn disabled" style="opacity: 0.5; cursor: default;"><i class="fas fa-chevron-left"></i> Prev</span>
            <?php endif; ?>

            <span style="align-self: center; font-family: 'Orbitron', sans-serif; color: var(--mobile-text-secondary);">
                Page <?= $page ?> / <?= $totalPages ?>
            </span>

            <?php if ($nextPage): ?>
                <a href="/spy/page/<?= $nextPage ?>?limit=<?= $perPage ?>" class="btn">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="btn disabled" style="opacity: 0.5; cursor: default;">Next <i class="fas fa-chevron-right"></i></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
