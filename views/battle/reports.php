<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\BattleReport[] $reports */
/* @var int $userId */
?>

<div class="container">
    <h1 style="margin-bottom: 0.5rem;">Battle Log</h1>
    <p style="color: var(--muted); margin-bottom: 2rem;">Tactical history of your recent military engagements.</p>

    <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem; padding: 1rem; border: 1px solid var(--border); border-radius: 12px; background: rgba(255,255,255,0.03);">
        <span style="color: var(--muted); font-size: 0.9rem; display: flex; align-items: center;">
            Showing recent operations
        </span>
        <a href="/battle" class="btn-submit btn-accent" style="margin: 0; padding: 0.5rem 1rem; font-size: 0.9rem;">New Operation</a>
    </div>

    <div style="display: flex; flex-direction: column; gap: 1rem;">
        <?php if (empty($reports)): ?>
            <div style="text-align: center; padding: 4rem; color: var(--muted); background: var(--bg-panel); border: 1px solid var(--border); border-radius: 12px;">
                No battle reports found. The galaxy is quiet... for now.
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
                $resultClass = 'res-draw';
                $icon = '⚖️';
                
                if (!$isStalemate) {
                    if ($isWinner) {
                        $statusClass = 'status-victory';
                        $resultText = 'VICTORY';
                        $resultClass = 'res-win';
                        $icon = '🏆';
                    } else {
                        $statusClass = 'status-defeat';
                        $resultText = 'DEFEAT';
                        $resultClass = 'res-loss';
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
                    <div class="battle-title">
                        <span>vs <?= $opponentName ?></span>
                        <span class="role-badge <?= $roleClass ?>"><?= $roleText ?></span>
                    </div>
                    
                    <div style="font-size: 0.85rem; color: #ccc; margin-top: 0.2rem;">
                        <?php if ($isWinner): ?>
                            <span class="gain-text">+<?= number_format($report->credits_plundered) ?> Credits</span>
                        <?php elseif (!$isStalemate): ?>
                            <span class="loss-text">Operation Failed</span>
                        <?php else: ?>
                            <span>No resources exchanged</span>
                        <?php endif; ?>
                        <span style="color: #555; margin: 0 6px;">|</span>
                        <span><?= number_format($report->experience_gained) ?> XP</span>
                    </div>
                </div>

                <div class="battle-result <?= $resultClass ?>">
                    <?= $resultText ?>
                    <span class="battle-date"><?= date('M j, H:i', strtotime($report->created_at)) ?></span>
                </div>
            </a>
            
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>