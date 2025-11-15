<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\BattleReport $report */

// --- Determine result text and CSS class ---
$resultText = match($report->attack_result) {
    'victory' => 'VICTORY',
    'defeat' => 'DEFEAT',
    'stalemate' => 'STALEMATE'
};
$resultClass = $report->attack_result;

// --- Determine if the viewer is the attacker or defender ---
// (We assume the viewer is always the attacker for this report view)
$viewerIsAttacker = true; 
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
    
    /* --- NEW HEADER CARD --- */
    .report-header-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.5rem 2rem;
        box-shadow: var(--shadow);
        backdrop-filter: blur(6px);
        display: grid;
        grid-template-columns: 1fr auto 1fr; /* Attacker | Result | Defender */
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .header-player { text-align: left; }
    .header-player.defender { text-align: right; }
    
    .header-player-name {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text);
    }
    .header-player-label {
        font-size: 0.9rem;
        color: var(--muted);
    }
    
    .header-result { text-align: center; }
    .header-result-text {
        font-size: 2.2rem;
        font-weight: 900;
        font-family: "Orbitron", sans-serif;
    }
    .header-result-id {
        font-size: 0.8rem;
        color: var(--muted);
        margin-top: 0.25rem;
    }
    
    .result-victory { color: var(--accent-green); }
    .result-defeat { color: var(--accent-red); }
    .result-stalemate { color: var(--accent-2); }
    
    @media (max-width: 600px) {
        .report-header-card {
            grid-template-columns: 1fr; /* Stack all */
            text-align: center;
        }
        .header-player.defender { text-align: center; }
        .header-result { order: -1; } /* Move result to top */
    }
    
    /* --- NEW ENGAGEMENT SUMMARY CARD --- */
    .summary-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.25rem 1.5rem;
        box-shadow: var(--shadow);
        backdrop-filter: blur(6px);
    }
    .summary-card h3 {
        color: #fff;
        margin-top: 0;
        margin-bottom: 1.5rem; /* More space */
        border-bottom: 1px solid var(--border);
        padding-bottom: 0.75rem;
        font-size: 1.1rem;
        letter-spacing: 0.02em;
        text-align: center;
        text-transform: uppercase;
    }
    
    .summary-grid {
        display: grid;
        grid-template-columns: 1fr 1fr; /* Two columns */
        gap: 1.5rem 2.5rem;
    }
    
    .summary-col h4 {
        color: var(--accent-2);
        margin: 0 0 1rem 0;
        font-size: 1rem;
        border-bottom: 1px solid var(--border);
        padding-bottom: 0.5rem;
    }
    
    .summary-col ul {
        list-style: none;
        padding-left: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.75rem; /* More spacing */
    }
    .summary-col li {
        font-size: 0.95rem; /* Larger font */
        color: #e0e0e0;
        padding: 0.35rem 0.25rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(58, 58, 90, 0.15);
        gap: 1rem;
    }
    .summary-col li:last-child {
        border-bottom: none;
    }
    .summary-col li span:first-child {
        font-weight: 500;
        color: var(--muted);
        font-size: 0.9rem;
    }
    .summary-col li span:last-child, .summary-col li .value {
        font-weight: 600;
        color: #fff;
        text-align: right;
        font-size: 0.95rem;
    }
    
    /* Value colors */
    .value-loss { color: var(--accent-red) !important; }
    .value-gain { color: var(--accent-green) !important; }
    .value-neutral { color: var(--accent-2) !important; }

    /* Responsive Grid */
    @media (max-width: 768px) {
        .summary-grid {
            grid-template-columns: 1fr; /* Stack columns on mobile */
        }
    }
</style>

<div class="report-view-container-full">
    <h1>Battle Report</h1>

    <div class="report-header-card">
        <div class="header-player attacker">
            <span class="header-player-name"><?= htmlspecialchars($report->attacker_name) ?></span>
            <span class="header-player-label">(You) - Attacker</span>
        </div>
        
        <div class="header-result">
            <span class="header-result-text result-<?= $resultClass ?>"><?= $resultText ?></span>
            <div class="header-result-id">Battle ID: <?= $report->id ?></div>
        </div>
        
        <div class="header-player defender">
            <span class="header-player-name"><?= htmlspecialchars($report->defender_name) ?></span>
            <span class="header-player-label">(Opponent) - Defender</span>
        </div>
    </div>

    <div class="summary-card">
        <h3>Engagement Summary</h3>
        
        <div class="summary-grid">
            <div class="summary-col">
                <h4>Your Attack</h4>
                <ul>
                    <li>
                        <span>Attack Strength:</span>
                        <span class="value value-neutral"><?= number_format($report->attacker_offense_power) ?></span>
                    </li>
                    <li>
                        <span>Soldiers Sent:</span>
                        <span class="value"><?= number_format($report->soldiers_sent) ?></span>
                    </li>
                    <li>
                        <span>Soldiers Lost:</span>
                        <span class="value value-loss">- <?= number_format($report->attacker_soldiers_lost) ?></span>
                    </li>
                    <li>
                        <span>XP Gained:</span>
                        <span class="value value-gain">+ <?= number_format($report->experience_gained) ?></span>
                    </li>
                    <li>
                        <span>War Prestige Gained:</span>
                        <span class="value value-gain">+ <?= number_format($report->war_prestige_gained) ?></span>
                    </li>
                </ul>
            </div>
            
            <div class="summary-col">
                <h4>Opponent's Defense</h4>
                <ul>
                    <li>
                        <span>Defense Strength:</span>
                        <span class="value value-neutral"><?= number_format($report->defender_defense_power) ?></span>
                    </li>
                    <li>
                        <span>Guards Lost:</span>
                        <span class="value value-loss">- <?= number_format($report->defender_guards_lost) ?></span>
                    </li>
                    <li>
                        <span>Credits Plundered:</span>
                        <span class="value value-gain">+ <?= number_format($report->credits_plundered) ?></span>
                    </li>
                    <li>
                        <span>Net Worth Stolen:</span>
                        <span class="value value-gain">+ <?= number_format($report->net_worth_stolen) ?></span>
                    </li>
                    <li>
                        <span>Attack Type:</span>
                        <span class="value"><?= htmlspecialchars(ucfirst($report->attack_type)) ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <a href="/battle/reports" class="btn-submit" style="text-align: center; max-width: 400px; margin: 1.5rem auto 0; display: block;">
        Back to Reports
    </a>

</div>