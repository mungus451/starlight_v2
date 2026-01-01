<?php
// --- Mobile Battle Command View ---
/* @var \App\Models\Entities\UserResource $attackerResources */
/* @var \App\Models\Entities\UserStats $attackerStats */
/* @var array $targets */
/* @var string $csrf_token */
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">War Room</h1>
        <p style="color: var(--mobile-text-secondary); font-size: 0.9rem; margin: 0.5rem 1rem 0 1rem; text-align: center;">Command your fleets and conquer the galaxy.</p>
    </div>

    <!-- Status Card -->
    <div class="mobile-card" style="text-align: center; border-color: var(--mobile-accent-red);">
        <div class="mobile-card-header" style="justify-content: center;">
            <h3 style="color: var(--mobile-accent-red);"><i class="fas fa-fighter-jet"></i> Military Status</h3>
        </div>
        <div class="mobile-card-content" style="display: block;">
            <div style="display: flex; justify-content: space-around; font-size: 0.9rem;">
                <div>
                    <span class="text-muted">Soldiers</span><br>
                    <strong style="font-size: 1.2rem; color: #fff;"><?= number_format($attackerResources->soldiers) ?></strong>
                </div>
                <div>
                    <span class="text-muted">Attack Turns</span><br>
                    <strong style="font-size: 1.2rem; color: #fff;"><?= number_format($attackerStats->attack_turns) ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Operation Card -->
    <div class="mobile-card">
        <div class="mobile-card-header">
            <h3><i class="fas fa-skull"></i> Deploy Fleet</h3>
        </div>
        <div class="mobile-card-content" style="display: block;">
            <form action="/battle/attack" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                
                <div class="form-group">
                    <label for="target_name">Target Commander</label>
                    <input type="text" name="target_name" id="target_name" class="mobile-input" placeholder="Enter Name" required style="width: 100%;">
                </div>

                <div class="form-group">
                    <label>Mission Profile</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <label class="btn btn-outline" style="flex: 1; margin-top: 0;">
                            <input type="radio" name="attack_type" value="raid" checked style="display:none;" onchange="this.parentElement.classList.add('active'); this.parentElement.nextElementSibling.classList.remove('active');">
                            Raid
                        </label>
                        <label class="btn btn-outline" style="flex: 1; margin-top: 0;">
                            <input type="radio" name="attack_type" value="invasion" style="display:none;" onchange="this.parentElement.classList.add('active'); this.parentElement.previousElementSibling.classList.remove('active');">
                            Invasion
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-danger" style="width: 100%; margin-top: 1rem;">
                    <i class="fas fa-meteor"></i> Launch Attack
                </button>
            </form>
        </div>
    </div>

    <!-- Navigation Buttons -->
    <a href="/battle/reports" class="btn btn-outline" style="margin-bottom: 1.5rem; text-align: center;">
        <i class="fas fa-history"></i> View Battle Logs
    </a>

    <!-- Target List -->
    <?php if (!empty($targets)): 
        $page = $pagination['currentPage'];
        $totalPages = $pagination['totalPages'];
        $limit = $pagination['limit'];
        $limitParam = "&limit={$limit}";
        $prevPage = $page > 1 ? $page - 1 : null;
        $nextPage = $page < $totalPages ? $page + 1 : null;
    ?>
        <h3 class="mobile-category-header">Enemy Commanders</h3>
        
        <!-- Limit Options -->
        <div class="mobile-tabs" style="justify-content: center; margin-bottom: 1rem; gap: 0.5rem;">
            <span style="color: var(--muted); font-size: 0.9rem; align-self: center;">Show:</span>
            <?php foreach ([5, 10, 25, 100] as $opt): ?>
                <a href="/battle?page=1&limit=<?= $opt ?>" 
                   class="tab-link <?= $limit == $opt ? 'active' : '' ?>"
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
                    <button class="btn btn-sm btn-danger" style="width: auto; padding: 0.5rem 1rem;" onclick="document.getElementById('target_name').value = '<?= htmlspecialchars($target['character_name']) ?>'; window.scrollTo(0,0);">
                        Target
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="mobile-tabs" style="justify-content: space-between; margin-top: 1rem;">
            <?php if ($prevPage): ?>
                <a href="/battle?page=<?= $prevPage . $limitParam ?>" class="btn">
                    <i class="fas fa-chevron-left"></i> Prev
                </a>
            <?php else: ?>
                <span class="btn disabled" style="opacity: 0.5; cursor: default;"><i class="fas fa-chevron-left"></i> Prev</span>
            <?php endif; ?>

            <span style="align-self: center; font-family: 'Orbitron', sans-serif; color: var(--mobile-text-secondary);">
                Page <?= $page ?> / <?= $totalPages ?>
            </span>

            <?php if ($nextPage): ?>
                <a href="/battle?page=<?= $nextPage . $limitParam ?>" class="btn">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="btn disabled" style="opacity: 0.5; cursor: default;">Next <i class="fas fa-chevron-right"></i></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
