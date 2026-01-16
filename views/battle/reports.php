<?php
// --- Helper variables from the controller ---
/* @var array[] $reports Array of ViewModels from BattleReportPresenter */
/* @var array $pagination */
/* @var int $userId */
?>

<div class="battle-container">
    <div class="battle-header">
        <h1>Battle Log</h1>
        <p>Tactical history of your recent military engagements.</p>
    </div>

    <div class="battle-controls">
        <span>Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?></span>
        <a href="/battle" class="btn-submit btn-new-op">New Operation</a>
    </div>

    <div style="display: flex; flex-direction: column; gap: 1rem;">
        <?php if (empty($reports)): ?>
            <div style="text-align: center; padding: 4rem; color: #687385; border: 1px dashed rgba(255,255,255,0.1); border-radius: 12px;">
                No battle reports found.
            </div>
        <?php else: ?>
            <?php foreach ($reports as $report): ?>
            
            <a href="/battle/report/<?= $report['id'] ?>" class="battle-card <?= $report['status_class'] ?>">
                
                <div class="card-icon-circle">
                    <?= $report['icon'] ?>
                </div>

                <div class="battle-info">
                    <h4>
                        <?= htmlspecialchars($report['versus_text']) ?>
                        <span class="role-badge <?= $report['role_class'] ?>"><?= $report['role_text'] ?></span>
                    </h4>
                    
                    <div class="battle-details">
                        <?php if ($report['is_winner']): ?>
                            <span class="positive">+<?= number_format($report['credits_plundered']) ?> Credits</span>
                        <?php elseif (!$report['is_stalemate']): ?>
                            <span class="negative">Operation Failed</span>
                        <?php else: ?>
                            <span class="text-muted">No resources exchanged</span>
                        <?php endif; ?>
                        <span>|</span>
                        <?= number_format($report['experience_gained']) ?> XP
                    </div>
                </div>

                <div class="battle-result-block">
                    <span class="result-text <?= $report['result_class'] ?>"><?= $report['result_text'] ?></span>
                    <span class="battle-time"><?= $report['formatted_date'] ?></span>
                </div>
            </a>
            
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="pagination">
        <?php
        $current = $pagination['current_page'];
        $total = $pagination['total_pages'];
        
        // Prev Link
        if ($current > 1): ?>
            <a href="?page=<?= $current - 1 ?>">&laquo; Prev</a>
        <?php endif;

        // Pagination Logic: Max 10 items total (including ellipsis)
        // We'll show: 1, ..., current-2, current-1, current, current+1, current+2, ..., total
        // Adjusting to stay within max 10
        
        $links = [];
        if ($total <= 10) {
            for ($i = 1; $i <= $total; $i++) $links[] = $i;
        } else {
            // Always include first
            $links[] = 1;
            
            $start = max(2, $current - 2);
            $end = min($total - 1, $current + 2);
            
            // Adjust start/end to try and show more links if at edges
            if ($current <= 4) $end = 8;
            if ($current > $total - 4) $start = $total - 7;
            
            if ($start > 2) $links[] = '...';
            
            for ($i = $start; $i <= $end; $i++) {
                $links[] = $i;
            }
            
            if ($end < $total - 1) $links[] = '...';
            
            // Always include last
            $links[] = $total;
        }

        foreach ($links as $link) {
            if ($link === '...') {
                echo '<span style="border:none; background:transparent;">...</span>';
            } elseif ($link == $current) {
                echo "<span>$link</span>";
            } else {
                echo "<a href=\"?page=$link\">$link</a>";
            }
        }

        // Next Link
        if ($current < $total): ?>
            <a href="?page=<?= $current + 1 ?>">Next &raquo;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>