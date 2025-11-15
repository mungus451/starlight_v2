<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\BattleReport $report */
?>

<style>
    :root {
        --card: radial-gradient(circle at 30% -10%, rgba(45, 209, 209, 0.07), rgba(13, 15, 27, 0.6));
        --border: rgba(255, 255, 255, 0.03);
        --accent: #2dd1d1;
        --accent-2: #f9c74f;
        --accent-red: #e53e3e;
        --accent-green: #4CAF50;
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
        grid-template-columns: repeat(2, 1fr); /* 2 columns */
        gap: 1.5rem;
    }
    
    @media (max-width: 768px) {
        .report-grid {
            grid-template-columns: 1fr; /* 1 column on mobile */
        }
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
        background: linear-gradient(180deg, var(--accent), rgba(2, 3, 10, 0));
        box-shadow: 0 0 20px rgba(45, 209, 209, 0.35);
    }
    .data-card ul {
        list-style: none;
        padding-left: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
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
    .data-card li .value-loss { color: var(--accent-red); }
    .data-card li .value-gain { color: var(--accent-green); }

    /* --- Header Card --- */
    .summary-card {
        grid-column: 1 / -1; /* Span full width */
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
    .result-victory { color: var(--accent-green); }
    .result-defeat { color: var(--accent-red); }
    .result-stalemate { color: var(--accent-2); }
    
    .summary-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem 1.5rem;
        text-align: left;
    }

    /* --- FIX: Responsive summary grid --- */
    @media (max-width: 500px) {
        .summary-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="report-view-container-full">
    <h1>Battle Report #<?= $report->id ?></h1>

    <div class="report-grid">
        
        <div class="data-card summary-card">
            <p class="report-header">
                Attack against <strong><?= htmlspecialchars($report->defender_name) ?></strong> on <?= $report->created_at ?>
            </p>
            
            <?php
                $resultText = match($report->attack_result) {
                    'victory' => 'You were VICTORIOUS! in battle!',
                    'defeat' => 'You were DEFEATED! HA!',
                    'stalemate' => 'The battle was a stalemate.'
                };
            ?>
            <span class="report-result result-<?= $report->attack_result ?>">
                <?= $resultText ?>
            </span>
            <ul class="summary-grid" style="margin-top: 1.5rem;">
                <li><span>Attacker:</span> <span class="value"><?= htmlspecialchars($report->attacker_name) ?></span></li>
                <li><span>Defender:</span> <span class="value"><?= htmlspecialchars($report->defender_name) ?></span></li>
                <li><span>Attack Type:</span> <span class="value"><?= htmlspecialchars(ucfirst($report->attack_type)) ?></span></li>
            </ul>
        </div>

        <div class="data-card">
            <h3>Battle Details</h3>
            <ul>
                <li><span>Attacker Offense:</span> <span class="value"><?= number_format($report->attacker_offense_power) ?></span></li>
                <li><span>Defender Defense:</span> <span class="value"><?= number_format($report->defender_defense_power) ?></span></li>
                <li><span>Soldiers Sent:</span> <span class="value"><?= number_format($report->soldiers_sent) ?></span></li>
                <li><span>Soldiers Lost:</span> <span class="value value-loss"><?= number_format($report->attacker_soldiers_lost) ?></span></li>
                <li><span>Enemy Guards Lost:</span> <span class="value value-gain"><?= number_format($report->defender_guards_lost) ?></span></li>
            </ul>
        </div>

        <div class="data-card">
            <h3>Spoils of War</h3>
            <ul>
                <li><span>Credits Plundered:</span> <span class="value value-gain"><?= number_format($report->credits_plundered) ?></span></li>
                <li><span>Net Worth Stolen:</span> <span class="value value-gain"><?= number_format($report->net_worth_stolen) ?></span></li>
                <li><span>Experience Gained:</span> <span class="value value-gain"><?= number_format($report->experience_gained) ?></span></li>
                <li><span>War Prestige Gained:</span> <span class="value value-gain"><?= number_format($report->war_prestige_gained) ?></span></li>
            </ul>
        </div>

        <a href="/battle/reports" class="btn-submit" style="text-align: center; grid-column: 1 / -1;">Back to Reports</a>
        
    </div> </div>