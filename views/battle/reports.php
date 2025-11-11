<style>
    .reports-container {
        width: 100%;
        max-width: 800px;
        text-align: left;
    }
    .reports-container h1 {
        text-align: center;
    }
    .data-card {
        background: #2a2a4a;
        border: 1px solid #3a3a5a;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .data-card h3 {
        color: #f9c74f;
        margin-top: 0;
        border-bottom: 1px solid #3a3a5a;
        padding-bottom: 0.5rem;
    }
    .report-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .report-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #1e1e3f;
        padding: 1rem;
        border-radius: 5px;
        border: 1px solid #3a3a5a;
        font-size: 1.1rem;
    }
    .report-item a {
        color: #7683f5;
        font-weight: bold;
        text-decoration: none;
    }
    .report-item-result.victory {
        color: #4CAF50;
        font-weight: bold;
    }
    .report-item-result.defeat {
        color: #e53e3e;
        font-weight: bold;
    }
    .report-item-result.stalemate {
        color: #f9c74f;
        font-weight: bold;
    }
</style>

<div class="reports-container">
    <h1>Battle Reports</h1>

    <div class="data-card">
        <h3>Inbox</h3>
        <a href="/battle" class="btn-submit" style="text-align: center; display: block; margin-bottom: 1.5rem;">Back to Battle</a>

        <ul class="report-list">
            <?php if (empty($reports)): ?>
                <li style="text-align: center; color: #c0c0e0;">You have no battle reports.</li>
            <?php else: ?>
                <?php foreach ($reports as $report): ?>
                    <li class="report-item">
                        <div>
                            <a href="/battle/report/<?= $report->id ?>">
                                Report #<?= $report->id ?> (vs. <?= htmlspecialchars($report->defender_name ?? 'Unknown') ?>)
                            </a>
                            <span style="font-size: 0.9rem; color: #c0c0e0; display: block;"><?= $report->created_at ?></span>
                        </div>
                        <span class="report-item-result <?= $report->attack_result ?>">
                            <?= htmlspecialchars(ucfirst($report->attack_result)) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>