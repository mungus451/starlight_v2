<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\BattleReport[] $reports */
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
            <?php foreach ($reports as $report): 
                // --- LOGIC ---
                $isAttacker = ($report->attacker_id === $userId);
                $isWinner = false;
                $isStalemate = ($report->attack_result === 'stalemate');
                
                if (!$isStalemate) {
                    if ($isAttacker) {
                        $isWinner = ($report->attack_result === 'victory');
                    } else {
                        $isWinner = ($report->attack_result === 'defeat');
                    }
                }
                
                $statusClass = 'status-stalemate';
                $resultText = 'STALEMATE';
                $resultTextClass = 'draw';
                $icon = '⚖️';
                
                if (!$isStalemate) {
                    if ($isWinner) {
                        $statusClass = 'status-victory';
                        $resultText = 'VICTORY';
                        $resultTextClass = 'win';
                        $icon = '🏆';
                    } else {
                        $statusClass = 'status-defeat';
                        $resultText = 'DEFEAT';
                        $resultTextClass = 'loss';
                        $icon = '💀';
                    }
                }

                $opponentName = $isAttacker ? $report->defender_name : $report->attacker_name;
                $opponentName = htmlspecialchars($opponentName ?? 'Unknown Target');
                
                $roleText = $isAttacker ? 'Attacker' : 'Defender';
                $roleClass = $isAttacker ? 'role-attacker' : 'role-defender';
                // --- END LOGIC ---
            ?>
            
            <a href="/battle/report/<?= $report->id ?>" class="battle-card <?= $statusClass ?>">
                
                <div class="card-icon-circle">
                    <?= $icon ?>
                </div>

                <div class="battle-info">
                    <h4>
                        vs <?= $opponentName ?>
                        <span class="role-badge <?= $roleClass ?>"><?= $roleText ?></span>
                    </h4>
                    
                    <div class="battle-details">
                        <?php if ($isWinner): ?>
                            <span class="positive">+<?= number_format($report->credits_plundered) ?> Credits</span>
                        <?php elseif (!$isStalemate): ?>
                            <span class="negative">Operation Failed</span>
                        <?php else: ?>
                            <span class="text-muted">No resources exchanged</span>
                        <?php endif; ?>
                        <span>|</span>
                        <?= number_format($report->experience_gained) ?> XP
                    </div>
                </div>

                <div class="battle-result-block">
                    <span class="result-text <?= $resultTextClass ?>"><?= $resultText ?></span>
                    <span class="battle-time"><?= date('M d, H:i', strtotime($report->created_at)) ?></span>
                </div>
            </a>
            
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>