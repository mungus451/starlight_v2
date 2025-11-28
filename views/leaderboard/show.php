<?php
// --- Helper variables from the controller ---
/* @var string $type 'players' or 'alliances' */
/* @var array $data List of rows (Players or Alliances) */
/* @var array $pagination ['currentPage', 'totalPages', 'totalItems', 'perPage'] */
?>

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
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">Rank</th>
                        <th style="width: 35%;">Commander</th>
                        <th style="width: 15%;">Level</th>
                        <th style="width: 15%;">Alliance</th>
                        <th style="width: 15%;">Net Worth</th>
                        <th style="width: 10%;">Prestige</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--muted); padding: 2rem;">No rankings available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <td data-label="Rank">
                                    <strong class="text-accent">#<?= $row['rank'] ?></strong>
                                </td>
                                <td data-label="Commander" style="display: flex; align-items: center; gap: 1rem;">
                                    <?php if (!empty($row['profile_picture_url'])): ?>
                                        <img src="/serve/avatar/<?= htmlspecialchars($row['profile_picture_url']) ?>" alt="Avatar" class="player-avatar" style="width: 32px; height: 32px; border-width: 1px;">
                                    <?php else: ?>
                                        <div class="player-avatar" style="width: 32px; height: 32px; border-width: 1px; background: #111;"></div>
                                    <?php endif; ?>
                                    
                                    <a href="/profile/<?= $row['id'] ?>" style="font-weight: 600;">
                                        <?= htmlspecialchars($row['character_name']) ?>
                                    </a>
                                </td>
                                <td data-label="Level"><?= $row['level'] ?></td>
                                <td data-label="Alliance">
                                    <?php if ($row['alliance_id']): ?>
                                        <a href="/alliance/profile/<?= $row['alliance_id'] ?>" style="color: var(--accent-2);">
                                            [<?= htmlspecialchars($row['alliance_tag']) ?>]
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Net Worth" style="color: var(--accent-green);"><?= number_format($row['net_worth']) ?></td>
                                <td data-label="Prestige"><?= number_format($row['war_prestige']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

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
                                <td data-label="Rank">
                                    <strong class="text-accent">#<?= $row['rank'] ?></strong>
                                </td>
                                <td data-label="Alliance Name" style="display: flex; align-items: center; gap: 1rem;">
                                    <?php if (!empty($row['profile_picture_url'])): ?>
                                        <img src="/serve/alliance_avatar/<?= htmlspecialchars($row['profile_picture_url']) ?>" alt="Logo" class="player-avatar" style="width: 32px; height: 32px; border-width: 1px; border-color: var(--accent-2);">
                                    <?php endif; ?>
                                    
                                    <a href="/alliance/profile/<?= $row['id'] ?>" style="font-weight: 600;">
                                        <?= htmlspecialchars($row['name']) ?>
                                    </a>
                                </td>
                                <td data-label="Tag">
                                    <span style="font-weight: 700; color: var(--accent-2);">[<?= htmlspecialchars($row['tag']) ?>]</span>
                                </td>
                                <td data-label="Members"><?= number_format($row['member_count']) ?></td>
                                <td data-label="Net Worth" style="color: var(--accent-green);"><?= number_format($row['net_worth']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Pagination -->
        <div class="pagination">
            <?php if ($pagination['totalPages'] > 1): ?>
                <?php if ($pagination['currentPage'] > 1): ?>
                    <a href="/leaderboard/<?= $type ?>/<?= $pagination['currentPage'] - 1 ?>">&laquo; Prev</a>
                <?php endif; ?>

                <?php 
                // Simple logic to show a window of pages around current
                $start = max(1, $pagination['currentPage'] - 2);
                $end = min($pagination['totalPages'], $pagination['currentPage'] + 2);
                
                if ($start > 1) echo '<span style="background:transparent; border:none;">...</span>';
                ?>

                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <?php if ($i == $pagination['currentPage']): ?>
                        <span><?= $i ?></span>
                    <?php else: ?>
                        <a href="/leaderboard/<?= $type ?>/<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php 
                if ($end < $pagination['totalPages']) echo '<span style="background:transparent; border:none;">...</span>';
                ?>

                <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                    <a href="/leaderboard/<?= $type ?>/<?= $pagination['currentPage'] + 1 ?>">Next &raquo;</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>