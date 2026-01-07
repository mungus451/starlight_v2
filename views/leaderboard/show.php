<?php
// --- Leaderboard View (Advisor V2) ---
/* @var string $type 'players' or 'alliances' */
/* @var string $currentSort The active sort key */
/* @var array $data List of rows */
/* @var array $pagination */

// Helper to generate sort URL
$sortUrl = function($key) use ($type) {
    return "/leaderboard/{$type}/1?sort={$key}";
};

// Helper for active sort class
$isSort = function($key) use ($currentSort) {
    return $currentSort === $key ? 'active-sort' : '';
};

// Icon Mappings for Stats
$statIcons = [
    'net_worth' => 'fas fa-coins',
    'army' => 'fas fa-crosshairs',
    'population' => 'fas fa-users',
    'battles_won' => 'fas fa-skull-crossbones',
    'spy_success' => 'fas fa-user-secret',
    'prestige' => 'fas fa-trophy'
];

// Extract Podium (Top 3) if on Page 1
$podium = [];
$remainingData = $data;
if ($pagination['currentPage'] == 1 && count($data) >= 3) {
    $podium = array_slice($data, 0, 3);
    $remainingData = array_slice($data, 3);
}
?>

<div class="structures-page-content">
    
    <!-- 1. Page Header -->
    <div class="page-header-container">
        <h1 class="page-title-neon">Galactic Registry</h1>
        <p class="page-subtitle-tech">
            Universal Command Rankings // Alliance Standings
        </p>
        <div class="flex-center gap-2 mt-2">
            <div class="badge bg-dark border-secondary">
                Page <?= $pagination['currentPage'] ?> of <?= $pagination['totalPages'] ?>
            </div>
            <div class="badge bg-dark border-info">
                Sorted By: <?= ucfirst(str_replace('_', ' ', $currentSort)) ?>
            </div>
        </div>
    </div>

    <!-- 2. Navigation Deck -->
    <div class="structure-nav-container mb-4">
        <a href="/leaderboard/players" class="structure-nav-btn <?= $type === 'players' ? 'active' : '' ?>">
            <i class="fas fa-user-astronaut"></i> Top Commanders
        </a>
        <a href="/leaderboard/alliances" class="structure-nav-btn <?= $type === 'alliances' ? 'active' : '' ?>">
            <i class="fas fa-flag"></i> Top Alliances
        </a>
    </div>

    <!-- 3. Podium Section (Top 3) -->
    <?php if (!empty($podium)): ?>
        <div class="podium-container mb-5">
            <?php 
                // Reorder podium for visual display: 2, 1, 3
                $displayPodium = [];
                if (isset($podium[1])) $displayPodium[] = ['data' => $podium[1], 'rank' => 2, 'class' => 'silver'];
                if (isset($podium[0])) $displayPodium[] = ['data' => $podium[0], 'rank' => 1, 'class' => 'gold'];
                if (isset($podium[2])) $displayPodium[] = ['data' => $podium[2], 'rank' => 3, 'class' => 'bronze'];
            ?>
            
            <?php foreach ($displayPodium as $spot): 
                $row = $spot['data'];
                $avatarUrl = ($type === 'players') 
                    ? ($row['profile_picture_url'] ? "/serve/avatar/{$row['profile_picture_url']}" : null)
                    : ($row['profile_picture_url'] ? "/serve/alliance_avatar/{$row['profile_picture_url']}" : null);
                $profileUrl = ($type === 'players') ? "/profile/{$row['id']}" : "/alliance/profile/{$row['id']}";
                $name = ($type === 'players') ? $row['character_name'] : $row['name'];
            ?>
                <div class="podium-card <?= $spot['class'] ?>">
                    <div class="rank-badge">#<?= $spot['rank'] ?></div>
                    <div class="podium-avatar-wrapper">
                        <?php if ($avatarUrl): ?>
                            <img src="<?= $avatarUrl ?>" alt="Avatar">
                        <?php else: ?>
                            <div class="avatar-placeholder"><i class="fas fa-user"></i></div>
                        <?php endif; ?>
                    </div>
                    <h4 class="podium-name"><a href="<?= $profileUrl ?>"><?= htmlspecialchars($name) ?></a></h4>
                    <div class="podium-stat">
                        <i class="<?= $statIcons['net_worth'] ?>"></i> <?= number_format($row['net_worth']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- 4. Data Table -->
    <div class="structure-card table-card">
        <div class="card-header-main" style="background: rgba(0,0,0,0.4);">
            <div class="card-icon"><i class="fas fa-list-ol"></i></div>
            <div class="card-title-group">
                <span>Registry Data</span>
                <h4>Rankings Registry</h4>
            </div>
        </div>

        <div class="card-body-main p-0">
            <div class="table-responsive">
                <table class="registry-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Commander</th>
                            <?php if ($type === 'players'): ?>
                                <th>Alliance</th>
                                <th class="<?= $isSort('net_worth') ?>"><a href="<?= $sortUrl('net_worth') ?>">Net Worth</a></th>
                                <th class="<?= $isSort('army') ?>"><a href="<?= $sortUrl('army') ?>">Army</a></th>
                                <th class="<?= $isSort('population') ?>"><a href="<?= $sortUrl('population') ?>">Pop</a></th>
                                <th class="<?= $isSort('battles_won') ?>"><a href="<?= $sortUrl('battles_won') ?>">Combat</a></th>
                                <th class="<?= $isSort('prestige') ?>"><a href="<?= $sortUrl('prestige') ?>">Prestige</a></th>
                            <?php else: ?>
                                <th>Tag</th>
                                <th>Members</th>
                                <th class="<?= $isSort('net_worth') ?>"><a href="<?= $sortUrl('net_worth') ?>">Net Worth</a></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($remainingData) && empty($podium)): ?>
                            <tr><td colspan="10" class="text-center p-5 text-muted">No records found in this sector.</td></tr>
                        <?php else: ?>
                            <?php foreach ($remainingData as $row): 
                                $avatarUrl = ($type === 'players') 
                                    ? ($row['profile_picture_url'] ? "/serve/avatar/{$row['profile_picture_url']}" : null)
                                    : ($row['profile_picture_url'] ? "/serve/alliance_avatar/{$row['profile_picture_url']}" : null);
                                $profileUrl = ($type === 'players') ? "/profile/{$row['id']}" : "/alliance/profile/{$row['id']}";
                                $name = ($type === 'players') ? $row['character_name'] : $row['name'];
                            ?>
                                <tr>
                                    <td><span class="rank-num"><?= $row['rank'] ?></span></td>
                                    <td class="commander-cell">
                                        <div class="mini-avatar">
                                            <?php if ($avatarUrl): ?>
                                                <img src="<?= $avatarUrl ?>" alt="">
                                            <?php else: ?>
                                                <i class="fas fa-user"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <a href="<?= $profileUrl ?>" class="name-link"><?= htmlspecialchars($name) ?></a>
                                            <?php if ($type === 'players'): ?>
                                                <span class="lvl-label">Lvl <?= $row['level'] ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    
                                    <?php if ($type === 'players'): ?>
                                        <td>
                                            <?php if ($row['alliance_id']): ?>
                                                <a href="/alliance/profile/<?= $row['alliance_id'] ?>" class="alliance-tag">
                                                    [<?= htmlspecialchars($row['alliance_tag']) ?>]
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="metric-cell text-success"><?= number_format($row['net_worth']) ?></td>
                                        <td class="metric-cell"><?= number_format($row['army_size']) ?></td>
                                        <td class="metric-cell"><?= number_format($row['population']) ?></td>
                                        <td>
                                            <span class="text-success"><?= $row['battles_won'] ?>W</span>
                                            <span class="text-muted">/</span>
                                            <span class="text-danger"><?= $row['battles_lost'] ?>L</span>
                                        </td>
                                        <td class="metric-cell text-neon-blue"><?= number_format($row['war_prestige']) ?></td>
                                    <?php else: ?>
                                        <td><span class="alliance-tag">[<?= htmlspecialchars($row['tag']) ?>]</span></td>
                                        <td class="metric-cell"><?= number_format($row['member_count']) ?></td>
                                        <td class="metric-cell text-success"><?= number_format($row['net_worth']) ?></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 5. Pagination -->
    <?php if ($pagination['totalPages'] > 1): ?>
        <div class="pagination-v2 mt-4">
            <?php 
                $link = function($p) use ($type, $currentSort) {
                    return "/leaderboard/{$type}/{$p}?sort={$currentSort}";
                };
            ?>
            
            <?php if ($pagination['currentPage'] > 1): ?>
                <a href="<?= $link($pagination['currentPage'] - 1) ?>" class="pag-btn"><i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>

            <div class="pag-numbers">
                <?php 
                $start = max(1, $pagination['currentPage'] - 2);
                $end = min($pagination['totalPages'], $pagination['currentPage'] + 2);
                ?>
                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <a href="<?= $link($i) ?>" class="pag-num <?= $i == $pagination['currentPage'] ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>

            <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                <a href="<?= $link($pagination['currentPage'] + 1) ?>" class="pag-btn"><i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<style>
/* Leaderboard Specific Styling */
.podium-container {
    display: flex;
    justify-content: center;
    align-items: flex-end;
    gap: 1rem;
    padding: 2rem 0;
}

.podium-card {
    background: rgba(13, 17, 23, 0.8);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    position: relative;
    transition: transform 0.3s ease;
    flex: 1;
    max-width: 220px;
}

.podium-card:hover { transform: translateY(-5px); }

.podium-card.gold { border-color: #ffd700; box-shadow: 0 0 20px rgba(255, 215, 0, 0.15); height: 280px; z-index: 2; }
.podium-card.silver { border-color: #c0c0c0; height: 240px; }
.podium-card.bronze { border-color: #cd7f32; height: 220px; }

.rank-badge {
    position: absolute;
    top: -15px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--border);
    padding: 0.2rem 1rem;
    border-radius: 20px;
    font-weight: 800;
    font-family: 'Orbitron', sans-serif;
    border: 1px solid inherit;
}
.gold .rank-badge { background: #ffd700; color: #000; }
.silver .rank-badge { background: #c0c0c0; color: #000; }
.bronze .rank-badge { background: #cd7f32; color: #000; }

.podium-avatar-wrapper {
    width: 80px;
    height: 80px;
    margin: 0.5rem auto 1rem;
    border-radius: 50%;
    border: 3px solid var(--border);
    overflow: hidden;
    background: #111;
}
.gold .podium-avatar-wrapper { width: 100px; height: 100px; border-color: #ffd700; }

.podium-avatar-wrapper img { width: 100%; height: 100%; object-fit: cover; }
.podium-name { font-family: 'Orbitron', sans-serif; margin-bottom: 0.5rem; font-size: 1.1rem; }
.podium-name a { color: #fff; text-decoration: none; }
.podium-stat { font-weight: bold; color: var(--accent-green); font-size: 0.9rem; }

/* Registry Table */
.registry-table { width: 100%; border-collapse: collapse; }
.registry-table th { 
    text-align: left; 
    padding: 1rem; 
    font-size: 0.8rem; 
    text-transform: uppercase; 
    color: var(--muted);
    border-bottom: 1px solid var(--border);
}
.registry-table th a { color: inherit; text-decoration: none; display: flex; align-items: center; gap: 5px; }
.registry-table th.active-sort { color: var(--accent); }
.registry-table th.active-sort::after { content: ' \25BC'; font-size: 0.7em; }

.registry-table td { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.03); vertical-align: middle; }
.registry-table tr:hover { background: rgba(255,255,255,0.02); }

.rank-num { font-weight: 800; font-family: 'Orbitron', sans-serif; color: var(--accent); }
.commander-cell { display: flex; align-items: center; gap: 1rem; }
.mini-avatar { width: 36px; height: 36px; border-radius: 4px; border: 1px solid var(--border); overflow: hidden; background: #000; display: flex; align-items: center; justify-content: center; }
.mini-avatar img { width: 100%; height: 100%; object-fit: cover; }
.name-link { font-weight: 600; color: #fff; text-decoration: none; display: block; }
.name-link:hover { color: var(--accent); }
.lvl-label { font-size: 0.75rem; color: var(--muted); }
.alliance-tag { color: var(--accent-2); font-weight: bold; text-decoration: none; }
.metric-cell { font-family: 'Courier New', Courier, monospace; font-weight: 600; }

/* Pagination V2 */
.pagination-v2 { display: flex; justify-content: center; align-items: center; gap: 1rem; }
.pag-btn, .pag-num { 
    background: rgba(0,0,0,0.3); 
    border: 1px solid var(--border); 
    color: #fff; 
    padding: 0.5rem 1rem; 
    border-radius: 6px; 
    text-decoration: none; 
    transition: all 0.2s;
}
.pag-num.active { background: var(--accent); color: #000; border-color: var(--accent); }
.pag-btn:hover, .pag-num:hover:not(.active) { background: rgba(255,255,255,0.1); }

@media (max-width: 768px) {
    .podium-container { flex-direction: column; align-items: center; }
    .podium-card { width: 100%; max-width: 100%; height: auto !important; }
}
</style>
