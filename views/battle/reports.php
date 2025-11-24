<?php
// --- Helper variables from the controller ---
/* @var array[] $reports Array of ViewModels from BattleReportPresenter */
/* @var int $userId */
?>

<div class="battle-container">
    <div class="battle-header">
        <h1>Battle Log</h1>
        <p>Tactical history of your recent military engagements.</p>
    </div>

    <div class="battle-controls">
        <span>Showing recent operations</span>
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
</div>