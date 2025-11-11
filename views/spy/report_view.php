<style>
    .report-view-container {
        width: 100%;
        max-width: 800px;
        text-align: left;
    }
    .report-view-container h1 {
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
    .data-card ul {
        list-style: none;
        padding-left: 0;
        margin: 0;
        display: grid;
        grid-template-columns: 1fr; /* 1 column on mobile */
        gap: 0.5rem;
    }
    @media (min-width: 768px) {
        .data-card ul {
            grid-template-columns: 1fr 1fr; /* 2 columns on desktop */
        }
    }
    .data-card li {
        font-size: 1.1rem;
        color: #e0e0e0;
        padding: 0.25rem 0;
        display: flex;
        justify-content: space-between;
    }
    .data-card li span:first-child {
        font-weight: bold;
        color: #c0c0e0;
    }
    .result-success {
        color: #4CAF50;
        font-weight: bold;
    }
    .result-failure {
        color: #e53e3e;
        font-weight: bold;
    }
    .report-header {
        font-size: 1.2rem;
        text-align: center;
        margin-bottom: 1rem;
    }
</style>

<div class="report-view-container">
    <h1>Spy Report #<?= $report->id ?></h1>

    <div class="data-card">
        <h3>Operation Summary</h3>
        <p class="report-header">
            Operation against <strong><?= htmlspecialchars($report->defender_name) ?></strong> on <?= $report->created_at ?>: 
            <span class="result-<?= $report->operation_result ?>">
                <?= htmlspecialchars(strtoupper($report->operation_result)) ?>
            </span>
        </p>
        <ul>
            <li><span>Spies Sent:</span> <span><?= number_format($report->spies_sent) ?></span></li>
            <li><span>Spies Lost:</span> <span><?= number_format($report->spies_lost_attacker) ?></span></li>
            <li><span>Enemy Sentries Destroyed:</span> <span><?= number_format($report->sentries_lost_defender) ?></span></li>
        </ul>
    </div>

    <div class="data-card">
        <h3>Intel Gathered</h3>
        <?php if ($report->operation_result === 'success'): ?>
            <ul>
                <li><span>Credits:</span> <span><?= number_format($report->credits_seen) ?></span></li>
                <li><span>Gemstones:</span> <span><?= number_format($report->gemstones_seen) ?></span></li>
                
                <li><span>Workers:</span> <span><?= number_format($report->workers_seen) ?></span></li>
                <li><span>Soldiers:</span> <span><?= number_format($report->soldiers_seen) ?></span></li>
                <li><span>Guards:</span> <span><?= number_format($report->guards_seen) ?></span></li>
                <li><span>Spies:</span> <span><?= number_format($report->spies_seen) ?></span></li>
                <li><span>Sentries:</span> <span><?= number_format($report->sentries_seen) ?></span></li>
                
                <li><span>Fortification:</span> <span>Level <?= number_format($report->fortification_level_seen) ?></span></li>
                <li><span>Offense Upgrade:</span> <span>Level <?= number_format($report->offense_upgrade_level_seen) ?></span></li>
                <li><span>Defense Upgrade:</span> <span>Level <?= number_format($report->defense_upgrade_level_seen) ?></span></li>
                <li><span>Spy Upgrade:</span> <span>Level <?= number_format($report->spy_upgrade_level_seen) ?></span></li>
                <li><span>Economy Upgrade:</span> <span>Level <?= number_format($report->economy_upgrade_level_seen) ?></span></li>
                <li><span>Population:</span> <span>Level <?= number_format($report->population_level_seen) ?></span></li>
                <li><span>Armory:</span> <span>Level <?= number_format($report->armory_level_seen) ?></span></li>
            </ul>
        <?php else: ?>
            <p style="text-align: center; color: #c0c0e0;">Your spies were unable to gather any intel.</p>
        <?php endif; ?>
    </div>

    <a href="/spy/reports" class="btn-submit" style="text-align: center; display: block;">Back to Reports</a>
</div>