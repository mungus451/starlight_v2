<?php
// --- Mobile Leaderboard View ---
/** @var string $type */
/** @var string $currentSort */
/** @var array $data */
/** @var array $pagination */
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">Leaderboard</h1>
        <p style="color: var(--mobile-text-secondary); font-size: 0.9rem; margin: 0.5rem 1rem 0 1rem; text-align: center;">View rankings for players and alliances.</p>
    </div>

    <!-- Tab Navigation -->
    <div class="mobile-tabs" style="margin-bottom: 1.5rem;">
        <a href="/leaderboard/players" class="tab-link <?= $type === 'players' ? 'active' : '' ?>">Players</a>
        <a href="/leaderboard/alliances" class="tab-link <?= $type === 'alliances' ? 'active' : '' ?>">Alliances</a>
    </div>

    <?php if ($type === 'players'): ?>
        <!-- Player Sort Dropdown -->
        <div class="form-group" style="margin: 0 1rem 1.5rem 1rem;">
            <label for="sort-select" style="color: var(--mobile-text-secondary); margin-bottom: 0.5rem; display: block;">Sort by:</label>
            <select id="sort-select" class="form-select bg-dark text-light border-secondary" onchange="window.location.href = '/leaderboard/players?sort=' + this.value;">
                <option value="net_worth" <?= $currentSort === 'net_worth' ? 'selected' : '' ?>>Net Worth</option>
                <option value="prestige" <?= $currentSort === 'prestige' ? 'selected' : '' ?>>Prestige</option>
                <option value="army" <?= $currentSort === 'army' ? 'selected' : '' ?>>Army Size</option>
                <option value="population" <?= $currentSort === 'population' ? 'selected' : '' ?>>Population</option>
                <option value="battles_won" <?= $currentSort === 'battles_won' ? 'selected' : '' ?>>Battles Won</option>
            </select>
        </div>
    <?php endif; ?>

    <!-- Leaderboard Data -->
    <?php if (empty($data)): ?>
        <p class="text-center text-muted" style="padding: 2rem;">No data available for this category.</p>
    <?php else: ?>
        <?php foreach ($data as $row): ?>
            <div class="mobile-card">
                <div class="mobile-card-header">
                    <h3 style="display: flex; align-items: center; gap: 1rem; color: var(--mobile-text-primary);">
                        <span class="rank-badge">#<?= htmlspecialchars($row['rank'] ?? 'N/A') ?></span>
                        <?php if ($type === 'players'): ?>
                            <a href="/profile/show/<?= htmlspecialchars($row['user_id'] ?? 0) ?>"><?= htmlspecialchars($row['character_name'] ?? 'Unknown') ?></a>
                        <?php else: ?>
                            <a href="/alliance/profile/<?= htmlspecialchars($row['alliance_id'] ?? 0) ?>"><?= htmlspecialchars($row['name'] ?? 'Unknown') ?></a>
                        <?php endif; ?>
                    </h3>
                </div>
                <div class="mobile-card-content" style="display: block;">
                    <ul class="mobile-stats-list">
                        <li><span><i class="fas fa-chart-line"></i> Net Worth</span> <strong><?= number_format($row['net_worth'] ?? 0) ?></strong></li>
                        <?php if ($type === 'players'): ?>
                            <li><span><i class="fas fa-star"></i> Prestige</span> <strong><?= number_format($row['war_prestige'] ?? 0) ?></strong></li>
                            <li><span><i class="fas fa-users"></i> Army</span> <strong><?= number_format($row['army_size'] ?? 0) ?></strong></li>
                            <li><span><i class="fas fa-city"></i> Population</span> <strong><?= number_format($row['population'] ?? 0) ?></strong></li>
                            <li>
                                <span><i class="fas fa-fist-raised"></i> W/L Ratio</span> 
                                <strong>
                                    <?= number_format($row['battles_won'] ?? 0) ?> / <?= number_format($row['battles_lost'] ?? 0) ?>
                                </strong>
                            </li>
                        <?php else: // Alliances ?>
                            <li><span><i class="fas fa-users"></i> Members</span> <strong><?= htmlspecialchars($row['member_count'] ?? 0) ?> / <?= htmlspecialchars($row['capacity'] ?? 0) ?></strong></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($pagination['totalPages'] > 1): ?>
    <div class="pagination-container" style="padding: 1rem;">
        <nav class="pagination">
            <?php if ($pagination['currentPage'] > 1): ?>
                <a href="/leaderboard/<?= $type ?>/<?= $pagination['currentPage'] - 1 ?>?sort=<?= $currentSort ?>" class="page-link">&laquo; Prev</a>
            <?php endif; ?>

            <span class="page-info">Page <?= $pagination['currentPage'] ?> of <?= $pagination['totalPages'] ?></span>

            <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                <a href="/leaderboard/<?= $type ?>/<?= $pagination['currentPage'] + 1 ?>?sort=<?= $currentSort ?>" class="page-link">Next &raquo;</a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
</div>

<style>
.rank-badge {
    display: inline-block;
    padding: 0.25rem 0.6rem;
    font-size: 0.9rem;
    font-weight: bold;
    color: var(--mobile-bg-primary);
    background-color: var(--mobile-text-primary);
    border-radius: 5px;
    min-width: 40px;
    text-align: center;
}
.pagination-container {
    display: flex;
    justify-content: center;
    align-items: center;
}
.pagination {
    display: flex;
    gap: 1rem;
    align-items: center;
}
.page-link {
    color: var(--mobile-text-secondary);
    text-decoration: none;
    padding: 0.5rem 1rem;
    border: 1px solid var(--mobile-border-color);
    border-radius: 5px;
    transition: background-color 0.2s, color 0.2s;
}
.page-link:hover {
    background-color: var(--mobile-accent-primary);
    color: #fff;
}
.page-info {
    color: var(--mobile-text-secondary);
    font-weight: bold;
}
</style>
