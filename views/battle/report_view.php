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
    .result-victory {
        color: #4CAF50;
        font-weight: bold;
    }
    .result-defeat {
        color: #e53e3e;
        font-weight: bold;
    }
    .result-stalemate {
        color: #f9c74f;
        font-weight: bold;
    }
    .report-header {
        font-size: 1.2rem;
        text-align: center;
        margin-bottom: 1rem;
    }
</style>

<div class="report-view-container">
    <h1>Battle Report #<?= $report->id ?></h1>

    <div class="data-card">
        <h3>Operation Summary</h3>
        <p class="report-header">
            Attack against <strong><?= htmlspecialchars($report->defender_name) ?></strong> on <?= $report->created_at ?>: 
            <span class="result-<?= $report->attack_result ?>">
                <?= htmlspecialchars(strtoupper($report->attack_result)) ?>
            </span>
        </p>
        <ul>
            <li><span>Attacker:</span> <span><?= htmlspecialchars($report->attacker_name) ?></span></li>
            <li><span>Defender:</span> <span><?= htmlspecialchars($report->defender_name) ?></span></li>
            <li><span>Attack Type:</span> <span><?= htmlspecialchars(ucfirst($report->attack_type)) ?></span></li>
        </ul>
    </div>

    <div class="data-card">
        <h3>Battle Details</h3>
        <ul>
            <li><span>Attacker Offense:</span> <span><?= number_format($report->attacker_offense_power) ?></span></li>
            <li><span>Defender Defense:</span> <span><?= number_format($report->defender_defense_power) ?></span></li>
            <li><span>Soldiers Sent:</span> <span><?= number_format($report->soldiers_sent) ?></span></li>
            <li><span>Soldiers Lost:</span> <span style="color: #e53e3e;"><?= number_format($report->attacker_soldiers_lost) ?></span></li>
            <li><span>Enemy Guards Lost:</span> <span style="color: #4CAF50;"><?= number_format($report->defender_guards_lost) ?></span></li>
        </ul>
    </div>

    <div class="data-card">
        <h3>Spoils of War</h3>
        <ul>
            <li><span>Credits Plundered:</span> <span><?= number_format($report->credits_plundered) ?></span></li>
            <li><span>Net Worth Stolen:</span> <span><?= number_format($report->net_worth_stolen) ?></span></li>
            <li><span>Experience Gained:</span> <span><?= number_format($report->experience_gained) ?></span></li>
            <li><span>War Prestige Gained:</span> <span><?= number_format($report->war_prestige_gained) ?></span></li>
        </ul>
    </div>

    <a href="/battle/reports" class="btn-submit" style="text-align: center; display: block;">Back to Reports</a>
</div>