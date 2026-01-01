<?php
// --- Mobile Battle Reports View ---
/* @var array $reports */
/* @var int $userId */
/* @var array $pagination */

$page = $pagination['currentPage'];
$totalPages = $pagination['totalPages'];
$limit = $pagination['limit'];
$prevPage = $page > 1 ? $page - 1 : null;
$nextPage = $page < $totalPages ? $page + 1 : null;
$limitParam = "&limit={$limit}";
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">Battle Logs</h1>
        <p style="color: var(--mobile-text-secondary); font-size: 0.9rem; margin: 0.5rem 1rem 0 1rem; text-align: center;">History of recent conflicts.</p>
    </div>

    <!-- Limit Options -->
    <div class="mobile-tabs" style="justify-content: center; margin-bottom: 1rem; gap: 0.5rem;">
        <span style="color: var(--muted); font-size: 0.9rem; align-self: center;">Show:</span>
        <?php foreach ([5, 10, 25, 100] as $opt): ?>
            <a href="/battle/reports?page=1&limit=<?= $opt ?>" 
               class="tab-link <?= $limit == $opt ? 'active' : '' ?>"
               style="padding: 0.25rem 0.75rem; font-size: 0.8rem;">
               <?= $opt == 100 ? 'ALL' : $opt ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($reports)): ?>
        <p class="text-center text-muted" style="padding: 2rem;">No battles recorded.</p>
    <?php else: ?>
        <?php foreach ($reports as $report): 
            $res = $report['result_class']; // win, loss, draw
            
            $color = 'var(--text)';
            $icon = 'fa-question';
            
            if ($res === 'win') {
                $color = 'var(--mobile-accent-green)';
                $icon = 'fa-trophy';
            } elseif ($res === 'loss') {
                $color = 'var(--mobile-accent-red)';
                $icon = 'fa-skull';
            } elseif ($res === 'draw') {
                $color = 'var(--mobile-accent-yellow)';
                $icon = 'fa-balance-scale';
            }
        ?>
        <div class="mobile-card" style="border-left: 4px solid <?= $color ?>;">
            <div class="mobile-card-content" style="display: block; padding: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <strong style="color: #fff; font-size: 1.1rem;">
                        <i class="fas <?= $icon ?>" style="color: <?= $color ?>;"></i> 
                        <?= htmlspecialchars($report['versus_text']) ?>
                    </strong>
                    <span style="font-size: 0.8rem; color: var(--muted);"><?= $report['formatted_date'] ?></span>
                </div>
                
                <div style="font-size: 0.9rem; color: var(--mobile-text-secondary); margin-bottom: 1rem;">
                    Result: <span style="color: <?= $color ?>; text-transform: uppercase; font-weight: bold;"><?= $report['result_text'] ?></span>
                </div>

                <a href="/battle/report/<?= $report['id'] ?>" class="btn btn-outline" style="width: 100%; text-align: center; margin-top: 0;">
                    View Debrief
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="mobile-tabs" style="justify-content: space-between; margin-top: 1rem;">
        <?php if ($prevPage): ?>
            <a href="/battle/reports?page=<?= $prevPage . $limitParam ?>" class="btn">
                <i class="fas fa-chevron-left"></i> Prev
            </a>
        <?php else: ?>
            <span class="btn disabled" style="opacity: 0.5; cursor: default;"><i class="fas fa-chevron-left"></i> Prev</span>
        <?php endif; ?>

        <span style="align-self: center; font-family: 'Orbitron', sans-serif; color: var(--mobile-text-secondary);">
            Page <?= $page ?> / <?= $totalPages ?>
        </span>

        <?php if ($nextPage): ?>
            <a href="/battle/reports?page=<?= $nextPage . $limitParam ?>" class="btn">
                Next <i class="fas fa-chevron-right"></i>
            </a>
        <?php else: ?>
            <span class="btn disabled" style="opacity: 0.5; cursor: default;">Next <i class="fas fa-chevron-right"></i></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div style="margin-top: 2rem; text-align: center;">
        <a href="/battle" class="btn btn-outline" style="width: 100%;">
            <i class="fas fa-arrow-left"></i> Back to War Room
        </a>
    </div>
</div>
