<?php
// --- Mobile Alliance List View ---
/* @var array $alliances */
/* @var array $pagination */
/* @var int|null $currentUserAllianceId */
/* @var int $perPage */

$page = $pagination['currentPage'];
$totalPages = $pagination['totalPages'];
$limitParam = "&limit={$perPage}";
$prevPage = $page > 1 ? $page - 1 : null;
$nextPage = $page < $totalPages ? $page + 1 : null;
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">Alliance Network</h1>
        <p style="color: var(--mobile-text-secondary); font-size: 0.9rem; margin: 0.5rem 1rem 0 1rem; text-align: center;">Join forces with other commanders.</p>
    </div>

    <!-- Actions -->
    <?php if (!$currentUserAllianceId): ?>
        <a href="/alliance/create" class="btn btn-accent" style="margin-bottom: 1.5rem; text-align: center;">
            <i class="fas fa-plus-circle"></i> Create New Alliance
        </a>
    <?php else: ?>
        <a href="/alliance/profile/<?= $currentUserAllianceId ?>" class="btn btn-outline" style="margin-bottom: 1.5rem; text-align: center;">
            <i class="fas fa-home"></i> Go to My Alliance
        </a>
    <?php endif; ?>

    <!-- Limit Options -->
    <div class="mobile-tabs" style="justify-content: center; margin-bottom: 1rem; gap: 0.5rem;">
        <span style="color: var(--muted); font-size: 0.9rem; align-self: center;">Show:</span>
        <?php foreach ([5, 10, 25, 100] as $opt): ?>
            <a href="/alliance/list?page=1&limit=<?= $opt ?>" 
               class="tab-link <?= $perPage == $opt ? 'active' : '' ?>"
               style="padding: 0.25rem 0.75rem; font-size: 0.8rem;">
               <?= $opt == 100 ? 'ALL' : $opt ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="leaderboard-list">
        <?php if (empty($alliances)): ?>
            <p class="text-center text-muted" style="padding: 2rem;">No alliances found.</p>
        <?php else: ?>
            <?php foreach ($alliances as $alliance): ?>
                <div class="mobile-card" style="padding: 0.75rem; display: flex; align-items: center; justify-content: space-between;">
                    <div style="flex: 1;">
                        <h4 style="margin: 0; color: var(--mobile-text-primary); font-size: 1.1rem;">
                            <span style="color: var(--mobile-accent-blue);">[<?= htmlspecialchars($alliance->tag) ?>]</span> 
                            <?= htmlspecialchars($alliance->name) ?>
                        </h4>
                        <div style="font-size: 0.8rem; color: var(--muted); margin-top: 0.25rem;">
                            Net Worth: <?= number_format($alliance->net_worth) ?>
                        </div>
                    </div>
                    <a href="/alliance/profile/<?= $alliance->id ?>" class="btn btn-sm btn-outline" style="width: auto; padding: 0.5rem 1rem;">
                        View
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="mobile-tabs" style="justify-content: space-between; margin-top: 1rem;">
        <?php if ($prevPage): ?>
            <a href="/alliance/list?page=<?= $prevPage . $limitParam ?>" class="btn">
                <i class="fas fa-chevron-left"></i> Prev
            </a>
        <?php else: ?>
            <span class="btn disabled" style="opacity: 0.5; cursor: default;"><i class="fas fa-chevron-left"></i> Prev</span>
        <?php endif; ?>

        <span style="align-self: center; font-family: 'Orbitron', sans-serif; color: var(--mobile-text-secondary);">
            Page <?= $page ?> / <?= $totalPages ?>
        </span>

        <?php if ($nextPage): ?>
            <a href="/alliance/list?page=<?= $nextPage . $limitParam ?>" class="btn">
                Next <i class="fas fa-chevron-right"></i>
            </a>
        <?php else: ?>
            <span class="btn disabled" style="opacity: 0.5; cursor: default;">Next <i class="fas fa-chevron-right"></i></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
