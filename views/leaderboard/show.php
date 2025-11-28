<?php
// --- Helper variables from the controller ---
/* @var string $type 'players' or 'alliances' */
/* @var string $currentSort The active sort key */
/* @var array $data List of rows */
/* @var array $pagination */

// Helper to generate sort URL
$sortUrl = function($key) use ($type) {
    return "/leaderboard/{$type}/1?sort={$key}";
};

// Helper for active class
$isSort = function($key) use ($currentSort) {
    return $currentSort === $key ? 'text-accent' : 'text-muted';
};
?>

<style>
    .sort-header { cursor: pointer; white-space: nowrap; user-select: none; }
    .sort-header a { text-decoration: none; color: inherit; display: block; width: 100%; height: 100%; }
    .sort-header:hover { background: rgba(255,255,255,0.05); color: var(--accent); }
    .metric-cell { font-family: monospace; font-size: 0.95rem; }
    .sub-stat { font-size: 0.75rem; color: var(--muted); display: block; }
</style>

<div class="container-full">
    <h1>Galactic Leaderboard</h1>

    <!-- Tabs -->
    <div class="tabs-nav" style="justify-content: center; margin-bottom: 2rem;">
        <a href="/leaderboard/players" class="tab-link <?= $type === 'players' ? 'active' : '' ?>" style="min-width: 150px; text-align: center;">
            Top Players
        </a>
        <a href="/leaderboard/alliances" class="tab-link <?= $type === 'alliances' ? 'active' : '' ?>" style="min-width: 150px; text-align: center;">
            Top Alliances
        </a>
    </div>

    <div class="data-table-container">
        
        <?php if ($type === 'players'): ?>
            <!-- PLAYERS TABLE -->
            <div style="overflow-x: auto;">
                <table class="data-table" style="min-width: 1000px;"> <!-- Force width to prevent crunching -->
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 20%;">Commander</th>
                            <th style="width: 10%;">Alliance</th>
                            
                            <!-- Sortable Columns -->
                            <th class="sort-header <?= $isSort('net_worth') ?>"><a href="<?= $sortUrl('net_worth') ?>">Net Worth</a></th>
                            <th class="sort-header <?= $isSort('army') ?>"><a href="<?= $sortUrl('army') ?>">Army Size</a></th>
                            <th class="sort-header <?= $isSort('population') ?>"><a href="<?= $sortUrl('population') ?>">Population</a></th>
                            <th class="sort-header <?= $isSort('battles_won') ?>"><a href="<?= $sortUrl('battles_won') ?>">Combat Record</a></th>
                            <th class="sort-header <?= $isSort('spy_success') ?>"><a href="<?= $sortUrl('spy_success') ?>">Spy Record</a></th>
                            <th class="sort-header <?= $isSort('prestige') ?>"><a href="<?= $sortUrl('prestige') ?>">Prestige</a></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data)): ?>
                            <tr><td colspan="9" style="text-align: center; color: var(--muted); padding: 2rem;">No rankings available.</td></tr>
                        <?php else: ?>
                            <?php foreach ($data as $row): ?>
                                <tr>
                                    <td><strong class="text-accent"><?= $row['rank'] ?></strong></td>
                                    
                                    <td style="display: flex; align-items: center; gap: 0.75rem;">
                                        <?php if (!empty($row['profile_picture_url'])): ?>
                                            <img src="/serve/avatar/<?= htmlspecialchars($row['profile_picture_url']) ?>" class="player-avatar" style="width: 32px; height: 32px; border-width: 1px;">
                                        <?php else: ?>
                                            <div class="player-avatar" style="width: 32px; height: 32px; border-width: 1px; background: #111;"></div>
                                        <?php endif; ?>
                                        <div>
                                            <a href="/profile/<?= $row['id'] ?>" style="font-weight: 600; display: block; line-height: 1;">
                                                <?= htmlspecialchars($row['character_name']) ?>
                                            </a>
                                            <span class="sub-stat">Lvl <?= $row['level'] ?></span>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <?php if ($row['alliance_id']): ?>
                                            <a href="/alliance/profile/<?= $row['alliance_id'] ?>" style="color: var(--accent-2); font-weight: 600;">
                                                [<?= htmlspecialchars($row['alliance_tag']) ?>]
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="metric-cell" style="color: var(--accent-green);">
                                        <?= number_format($row['net_worth']) ?>
                                    </td>
                                    
                                    <td class="metric-cell">
                                        <?= number_format($row['army_size']) ?>
                                    </td>
                                    
                                    <td class="metric-cell">
                                        <?= number_format($row['population']) ?>
                                    </td>
                                    
                                    <!-- Combat (Wins / Losses) -->
                                    <td>
                                        <span style="color: var(--accent-green); font-weight: bold;"><?= number_format($row['battles_won']) ?> W</span>
                                        <span class="text-muted">/</span>
                                        <span style="color: var(--accent-red); font-size: 0.9em;"><?= number_format($row['battles_lost']) ?> L</span>
                                    </td>
                                    
                                    <!-- Spy (Success / Fail) -->
                                    <td>
                                        <span style="color: var(--accent-blue); font-weight: bold;"><?= number_format($row['spy_successes']) ?> S</span>
                                        <span class="text-muted">/</span>
                                        <span style="color: var(--accent-red); font-size: 0.9em;"><?= number_format($row['spy_failures']) ?> F</span>
                                    </td>
                                    
                                    <td class="metric-cell text-accent">
                                        <?= number_format($row['war_prestige']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <!-- ALLIANCES TABLE -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">Rank</th>
                        <th style="width: 40%;">Alliance Name</th>
                        <th style="width: 15%;">Tag</th>
                        <th style="width: 15%;">Members</th>
                        <th style="width: 20%;">Net Worth</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--muted); padding: 2rem;">No alliances ranked.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <td>
                                    <strong class="text-accent">#<?= $row['rank'] ?></strong>
                                </td>
                                <td style="display: flex; align-items: center; gap: 1rem;">
                                    <?php if (!empty($row['profile_picture_url'])): ?>
                                        <img src="/serve/alliance_avatar/<?= htmlspecialchars($row['profile_picture_url']) ?>" class="player-avatar" style="width: 32px; height: 32px; border-width: 1px; border-color: var(--accent-2);">
                                    <?php endif; ?>
                                    
                                    <a href="/alliance/profile/<?= $row['id'] ?>" style="font-weight: 600;">
                                        <?= htmlspecialchars($row['name']) ?>
                                    </a>
                                </td>
                                <td>
                                    <span style="font-weight: 700; color: var(--accent-2);">[<?= htmlspecialchars($row['tag']) ?>]</span>
                                </td>
                                <td><?= number_format($row['member_count']) ?></td>
                                <td style="color: var(--accent-green); font-weight: bold;"><?= number_format($row['net_worth']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Pagination -->
        <div class="pagination">
            <?php if ($pagination['totalPages'] > 1): ?>
                <?php 
                    // Ensure sorting param persists in pagination links
                    $link = function($p) use ($type, $currentSort) {
                        return "/leaderboard/{$type}/{$p}?sort={$currentSort}";
                    };
                ?>
            
                <?php if ($pagination['currentPage'] > 1): ?>
                    <a href="<?= $link($pagination['currentPage'] - 1) ?>">&laquo; Prev</a>
                <?php endif; ?>

                <?php 
                $start = max(1, $pagination['currentPage'] - 2);
                $end = min($pagination['totalPages'], $pagination['currentPage'] + 2);
                if ($start > 1) echo '<span style="background:transparent; border:none;">...</span>';
                ?>

                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <?php if ($i == $pagination['currentPage']): ?>
                        <span><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= $link($i) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($end < $pagination['totalPages']) echo '<span style="background:transparent; border:none;">...</span>'; ?>

                <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                    <a href="<?= $link($pagination['currentPage'] + 1) ?>">Next &raquo;</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>