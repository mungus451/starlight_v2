<?php
// --- Mobile Leaderboard View ---
/* @var string $type 'players' or 'alliances' */
/* @var string $currentSort */
/* @var array $data */
/* @var array $pagination */
/* @var int $currentLimit */

$page = $pagination['currentPage'];
$totalPages = $pagination['totalPages'];
$prevPage = $page > 1 ? $page - 1 : null;
$nextPage = $page < $totalPages ? $page + 1 : null;

// Ensure currentSort has a default
$currentSort = $currentSort ?? 'net_worth';
$limitParam = "&limit={$currentLimit}";

$sortOptions = [
    'net_worth' => ['icon' => 'fa-coins', 'label' => 'Net Worth'],
    'prestige' => ['icon' => 'fa-medal', 'label' => 'Prestige'],
    'army' => ['icon' => 'fa-fist-raised', 'label' => 'Army Size'],
    'population' => ['icon' => 'fa-users', 'label' => 'Population'],
    'battles_won' => ['icon' => 'fa-trophy', 'label' => 'Wins']
];
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">Global Ranks</h1>
        <p style="color: var(--mobile-text-secondary); font-size: 0.9rem; margin: 0.5rem 1rem 0 1rem; text-align: center;">Top commanders and alliances across the galaxy.</p>
    </div>

    <!-- Type Tabs -->
    <div class="mobile-tabs" style="justify-content: center; margin-bottom: 1rem; flex-wrap: wrap;">
        <a href="/leaderboard/players<?= '?sort=' . $currentSort . $limitParam ?>" class="tab-link <?= $type === 'players' ? 'active' : '' ?>">Commanders</a>
        <a href="/leaderboard/alliances<?= '?sort=' . $currentSort . $limitParam ?>" class="tab-link <?= $type === 'alliances' ? 'active' : '' ?>">Alliances</a>
    </div>

    <!-- Sort Options (Players Only) -->
    <?php if ($type === 'players'): ?>
        <div class="mobile-card" style="padding: 0.5rem; margin-bottom: 1rem;">
            <div class="mobile-card-header" style="border-bottom: none; padding-bottom: 0.5rem;">
                <h3 style="font-size: 0.9rem; color: var(--mobile-text-secondary);">Sort By:</h3>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: center;">
                <?php foreach ($sortOptions as $key => $opt): ?>
                    <a href="/leaderboard/players/1?sort=<?= $key . $limitParam ?>" 
                       class="btn <?= $currentSort === $key ? 'btn-accent' : '' ?>" 
                       style="font-size: 0.8rem; padding: 0.5rem 0.75rem; flex: 1 1 auto; text-align: center; min-width: 100px;">
                        <i class="fas <?= $opt['icon'] ?>"></i> <?= $opt['label'] ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Limit Options -->
    <div class="mobile-tabs" style="justify-content: center; margin-bottom: 1rem; gap: 0.5rem;">
        <span style="color: var(--muted); font-size: 0.9rem; align-self: center;">Show:</span>
        <?php foreach ([5, 10, 25] as $opt): ?>
            <a href="/leaderboard/<?= $type ?>/1?sort=<?= $currentSort ?>&limit=<?= $opt ?>" 
               class="tab-link <?= $currentLimit == $opt ? 'active' : '' ?>"
               style="padding: 0.25rem 0.75rem; font-size: 0.8rem;">
               <?= $opt ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Leaderboard List -->
    <div class="leaderboard-list">
        <?php if (empty($data)): ?>
            <p class="text-center text-muted" style="padding: 2rem;">No records found.</p>
        <?php else: ?>
            <?php foreach ($data as $row): ?>
                <div class="mobile-card" style="display: flex; align-items: center; padding: 0.75rem; gap: 1rem;">
                    <!-- Rank -->
                    <div class="rank-badge" style="
                        font-family: 'Orbitron', sans-serif; 
                        font-size: 1.2rem; 
                        font-weight: bold; 
                        color: var(--mobile-accent-yellow); 
                        width: 40px; 
                        text-align: center;">
                        #<?= $row['rank'] ?>
                    </div>

                    <!-- Info -->
                    <div style="flex: 1; min-width: 0;">
                        <?php if ($type === 'players'): ?>
                            <h4 style="margin: 0; color: var(--mobile-text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <a href="/profile/<?= $row['user_id'] ?? '#' ?>" style="color: inherit; text-decoration: none;">
                                    <?= htmlspecialchars($row['character_name'] ?? 'Unknown') ?>
                                </a>
                            </h4>
                            <div style="font-size: 0.8rem; color: var(--mobile-text-secondary);">
                                <?php if (!empty($row['alliance_name'])): ?>
                                    <span style="color: var(--mobile-accent-blue);">[<?= htmlspecialchars($row['alliance_ticker'] ?? '') ?>]</span> 
                                    <?= htmlspecialchars($row['alliance_name']) ?>
                                <?php else: ?>
                                    <span class="text-muted">No Alliance</span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <h4 style="margin: 0; color: var(--mobile-text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <a href="/alliance/profile/<?= $row['id'] ?? '#' ?>" style="color: inherit; text-decoration: none;">
                                    [<?= htmlspecialchars($row['ticker'] ?? '') ?>] <?= htmlspecialchars($row['name'] ?? 'Unknown') ?>
                                </a>
                            </h4>
                            <div style="font-size: 0.8rem; color: var(--mobile-text-secondary);">
                                <i class="fas fa-users"></i> <?= number_format($row['member_count'] ?? 0) ?> Members
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Main Stat -->
                    <div class="stat-value" style="text-align: right;">
                        <div style="color: var(--mobile-accent-green); font-weight: bold; font-family: 'Orbitron', sans-serif;">
                            <?= number_format($row[$currentSort] ?? $row['net_worth'] ?? 0) ?>
                        </div>
                        <div style="font-size: 0.7rem; color: var(--muted); text-transform: uppercase;">
                            <?= $type === 'players' ? ($sortOptions[$currentSort]['label'] ?? 'Net Worth') : 'Net Worth' ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="mobile-tabs" style="justify-content: space-between; margin-top: 1rem;">
        <?php if ($prevPage): ?>
            <a href="/leaderboard/<?= $type ?>/<?= $prevPage ?>?sort=<?= $currentSort . $limitParam ?>" class="btn">
                <i class="fas fa-chevron-left"></i> Prev
            </a>
        <?php else: ?>
            <span class="btn disabled" style="opacity: 0.5; cursor: default;"><i class="fas fa-chevron-left"></i> Prev</span>
        <?php endif; ?>

        <span style="align-self: center; font-family: 'Orbitron', sans-serif; color: var(--mobile-text-secondary);">
            Page <?= $page ?> / <?= $totalPages ?>
        </span>

        <?php if ($nextPage): ?>
            <a href="/leaderboard/<?= $type ?>/<?= $nextPage ?>?sort=<?= $currentSort . $limitParam ?>" class="btn">
                Next <i class="fas fa-chevron-right"></i>
            </a>
        <?php else: ?>
            <span class="btn disabled" style="opacity: 0.5; cursor: default;">Next <i class="fas fa-chevron-right"></i></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
