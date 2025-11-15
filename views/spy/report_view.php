<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\SpyReport $report */
?>

<style>
    :root {
        --card: radial-gradient(circle at 30% -10%, rgba(45, 209, 209, 0.07), rgba(13, 15, 27, 0.6));
        --border: rgba(255, 255, 255, 0.03);
        --accent: #2dd1d1;
        --accent-2: #f9c74f;
        --accent-red: #e53e3e;
        --accent-green: #4CAF50;
        --accent-blue: #7683f5;
        --text: #eff1ff;
        --muted: #a8afd4;
        --radius: 18px;
        --shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
    }

    /* --- Base Container --- */
    .report-view-container-full {
        width: 100%;
        max-width: 1000px; /* Constrain for readability */
        margin-inline: auto;
        padding: 0;
        position: relative;
    }

    .report-view-container-full h1 {
        text-align: center;
        margin-bottom: 2rem;
        font-size: clamp(2.1rem, 3vw, 2.6rem);
        letter-spacing: -0.03em;
        color: #fff;
        padding-top: 1.5rem;
    }

    /* --- Grid Layout --- */
    .report-grid {
        display: grid;
        grid-template-columns: 1fr; /* 1 column layout */
        gap: 1.5rem;
    }
    
    /* --- Shared Card Styles (from Profile) --- */
    .data-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.25rem 1.5rem;
        box-shadow: var(--shadow);
        backdrop-filter: blur(6px);
    }
    .data-card h3 {
        color: #fff;
        margin-top: 0;
        margin-bottom: 0.85rem;
        border-bottom: 1px solid rgba(233, 219, 255, 0.03);
        padding-bottom: 0.5rem;
        font-size: 0.9rem;
        letter-spacing: 0.02em;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        text-transform: uppercase;
    }
    .data-card h3::before {
        content: "";
        width: 4px;
        height: 16px;
        border-radius: 999px;
        background: linear-gradient(180deg, var(--accent-blue), rgba(2, 3, 10, 0));
        box-shadow: 0 0 20px rgba(118, 131, 245, 0.35);
    }
    .data-card ul {
        list-style: none;
        padding-left: 0;
        margin: 0;
        /* --- NEW: Grid for intel --- */
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.35rem 1.5rem;
    }
    .data-card li {
        font-size: 0.9rem;
        color: #e0e0e0;
        padding: 0.55rem 0.25rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(58, 58, 90, 0.08);
        gap: 1rem;
    }
    .data-card li:last-child {
        border-bottom: none;
    }
    .data-card li span:first-child {
        font-weight: 500;
        color: rgba(239, 241, 255, 0.7);
        font-size: 0.85rem;
    }
    .data-card li span:last-child, .data-card li .value {
        font-weight: 600;
        color: #fff;
        text-align: right;
        font-size: 0.85rem;
    }

    /* --- Header Card --- */
    .summary-card {
        text-align: center;
    }
    .report-header {
        font-size: 1.2rem;
        color: var(--muted);
        margin-bottom: 1rem;
    }
    .report-header strong {
        color: var(--text);
    }
    .report-result {
        font-weight: 700;
        font-size: 1.5rem;
    }
    .result-success { color: var(--accent-green); }
    .result-failure { color: var(--accent-red); }
    
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem 1.5rem;
        text-align: left;
    }
    
    /* Responsive Grids */
    @media (max-width: 768px) {
        .data-card ul {
            grid-template-columns: 1fr; /* Stack intel list */
        }
    }
    @media (max-width: 500px) {
        .summary-grid {
            grid-template-columns: 1fr; /* Stack summary list */
        }
    }
</style>

<div class="report-view-container-full">
    <h1>Spy Report #<?= $report->id ?></h1>

    <div class="report-grid">
        
        <div class="data-card summary-card">
            <p class="report-header">
                Operation against <strong><?= htmlspecialchars($report->defender_name) ?></strong> on <?= $report->created_at ?>
            </p>
            
            <?php
                $resultText = match($report->operation_result) {
                    'success' => 'Operation Successful',
                    'failure' => 'Operation Failed'
                };
            ?>
            <span class="report-result result-<?= $report->operation_result ?>">
                <?= $resultText ?>
            </span>
            
            <ul class="summary-grid" style="margin-top: 1.5rem;">
                <li><span>Spies Sent:</span> <span class="value"><?= number_format($report->spies_sent) ?></span></li>
                <li><span>Spies Lost:</span> <span class="value" style="color: var(--accent-red);"><?= number_format($report->spies_lost_attacker) ?></span></li>
                <li><span>Sentries Destroyed:</span> <span class="value" style="color: var(--accent-green);"><?= number_format($report->sentries_lost_defender) ?></span></li>
            </ul>
        </div>

        <div class="data-card">
            <h3>Intel Gathered</h3>
            <?php if ($report->operation_result === 'success'): ?>
                <ul>
                    <li><span>Credits:</span> <span class="value"><?= number_format($report->credits_seen) ?></span></li>
                    <li><span>Gemstones:</span> <span class="value"><?= number_format($report->gemstones_seen) ?></span></li>
                    
                    <li><span>Workers:</span> <span class="value"><?= number_format($report->workers_seen) ?></span></li>
                    <li><span>Soldiers:</span> <span class="value"><?= number_format($report->soldiers_seen) ?></span></li>
                    <li><span>Guards:</span> <span class="value"><?= number_format($report->guards_seen) ?></span></li>
                    <li><span>Spies:</span> <span class="value"><?= number_format($report->spies_seen) ?></span></li>
                    <li><span>Sentries:</span> <span class="value"><?= number_format($report->sentries_seen) ?></span></li>
                    
                    <li><span>Fortification:</span> <span class="value">Level <?= number_format($report->fortification_level_seen) ?></span></li>
                    <li><span>Offense Upgrade:</span> <span class="value">Level <?= number_format($report->offense_upgrade_level_seen) ?></span></li>
                    <li><span>Defense Upgrade:</span> <span class="value">Level <?= number_format($report->defense_upgrade_level_seen) ?></span></li>
                    <li><span>Spy Upgrade:</span> <span class="value">Level <?= number_format($report->spy_upgrade_level_seen) ?></span></li>
                    <li><span>Economy Upgrade:</span> <span class="value">Level <?= number_format($report->economy_upgrade_level_seen) ?></span></li>
                    <li><span>Population:</span> <span class="value">Level <?= number_format($report->population_level_seen) ?></span></li>
                    <li><span>Armory:</span> <span class="value">Level <?= number_format($report->armory_level_seen) ?></span></li>
                </ul>
            <?php else: ?>
                <p style="text-align: center; color: var(--muted); padding: 1rem 0;">
                    Your spies were unable to gather any intel.
                </p>
            <?php endif; ?>
        </div>

        <a href="/spy/reports" class="btn-submit" style="text-align: center; background: var(--accent-blue);">Back to Reports</a>
        
    </div> </div>