<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\SpyReport[] $reports */
/* @var int $userId */
?>

<div class="container">
    <h1 style="margin-bottom: 0.5rem;">Spy Reports</h1>
    <p style="color: var(--muted); margin-bottom: 2rem;">Intelligence logs from your espionage network.</p>

    <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem; padding: 1rem; border: 1px solid var(--border); border-radius: 12px; background: rgba(255,255,255,0.03);">
        <span style="color: var(--muted); font-size: 0.9rem; display: flex; align-items: center;">
            Showing recent reports
        </span>
        <a href="/spy" class="btn-submit btn-accent" style="margin: 0; padding: 0.5rem 1rem; font-size: 0.9rem;">Spy Command</a>
    </div>

    <div style="display: flex; flex-direction: column; gap: 1rem;">
        <?php if (empty($reports)): ?>
            <div style="text-align: center; padding: 4rem; color: var(--muted); background: var(--bg-panel); border: 1px solid var(--border); border-radius: 12px;">
                No spy reports found.
            </div>
        <?php else: ?>
            <?php foreach ($reports as $report): 
                // --- LOGIC ---
                $isAttacker = ($report->attacker_id === $userId);
                
                if ($isAttacker) {
                    $viewerResult = $report->operation_result; // 'success' or 'failure'
                    $viewerResultText = ucfirst($viewerResult);
                    $statusClass = ($viewerResult === 'success') ? 'status-victory' : 'status-defeat';
                    $resultClass = ($viewerResult === 'success') ? 'res-win' : 'res-loss';
                    $icon = ($viewerResult === 'success') ? '📡' : '⚠️';
                } else {
                    // Defender logic
                    $viewerResult = ($report->operation_result === 'success') ? 'failure' : 'success';
                    $viewerResultText = ($report->operation_result === 'success') ? 'Failed (Breached)' : 'Success (Caught)';
                    $statusClass = ($viewerResult === 'success') ? 'status-victory' : 'status-defeat';
                    $resultClass = ($viewerResult === 'success') ? 'res-win' : 'res-loss';
                    $icon = ($viewerResult === 'success') ? '🛡️' : '🚨';
                }

                $opponentName = $isAttacker ? $report->defender_name : $report->attacker_name;
                $opponentName = htmlspecialchars($opponentName ?? 'Unknown Target');
                
                $roleText = $isAttacker ? 'Operator' : 'Target';
                $roleClass = $isAttacker ? 'role-attacker' : 'role-defender';
                // --- END LOGIC ---
            ?>
            
            <a href="/spy/report/<?= $report->id ?>" class="battle-card <?= $statusClass ?>">
                <div class="card-icon-circle">
                    <?= $icon ?>
                </div>

                <div class="battle-info">
                    <div class="battle-title">
                        <span><?= $isAttacker ? 'vs' : 'from' ?> <?= $opponentName ?></span>
                        <span class="role-badge <?= $roleClass ?>"><?= $roleText ?></span>
                    </div>
                    <div style="font-size: 0.85rem; color: var(--muted); margin-top: 0.2rem;">
                        ID: #<?= $report->id ?>
                    </div>
                </div>

                <div class="battle-result <?= $resultClass ?>">
                    <?= htmlspecialchars($viewerResultText) ?>
                    <span class="battle-date"><?= date('M j, H:i', strtotime($report->created_at)) ?></span>
                </div>
            </a>
            
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>