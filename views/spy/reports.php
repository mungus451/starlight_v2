<?php
// --- Helper variables from the controller ---
/* @var array[] $reports Array of ViewModels from SpyReportPresenter */
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
            <?php foreach ($reports as $report): ?>
            
            <a href="/spy/report/<?= $report['id'] ?>" class="battle-card <?= $report['status_class'] ?>">
                <div class="card-icon-circle">
                    <?= $report['icon'] ?>
                </div>

                <div class="battle-info">
                    <div class="battle-title">
                        <span><?= htmlspecialchars($report['versus_text']) ?></span>
                        <span class="role-badge <?= $report['role_class'] ?>"><?= $report['role_text'] ?></span>
                    </div>
                    <div style="font-size: 0.85rem; color: var(--muted); margin-top: 0.2rem;">
                        ID: #<?= $report['id'] ?>
                    </div>
                </div>

                <div class="battle-result <?= $report['result_class'] ?>">
                    <?= htmlspecialchars($report['short_result_text']) ?>
                    <span class="battle-date"><?= $report['formatted_date'] ?></span>
                </div>
            </a>
            
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>