<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\BattleReport[] $reports */
/* @var int $userId */
?>

<style>
    :root {
        --card: radial-gradient(circle at 30% -10%, rgba(45, 209, 209, 0.07), rgba(13, 15, 27, 0.6));
        --border: rgba(255, 255, 255, 0.03);
        --accent: #2dd1d1;
        --accent-2: #f9c74f;
        --accent-red: #e53e3e;
        --accent-green: #4CAF50; /* Added green */
        --text: #eff1ff;
        --muted: #a8afd4;
        --radius: 18px;
        --shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
    }

    /* --- Base Container --- */
    .reports-container-full {
        width: 100%;
        max-width: 1000px; /* Constrain the list for readability */
        margin-inline: auto;
        padding: 0;
        position: relative;
    }

    .reports-container-full h1 {
        text-align: center;
        margin-bottom: 2rem;
        font-size: clamp(2.1rem, 3vw, 2.6rem);
        letter-spacing: -0.03em;
        color: #fff;
        padding-top: 1.5rem;
    }

    /* --- Report List Container (from Battle) --- */
    .report-table-container {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.5rem;
        box-shadow: var(--shadow);
    }
    
    .report-table-container .btn-submit {
        display: block;
        width: 100%;
        max-width: 300px;
        margin: 0 auto 1.5rem auto;
        text-align: center;
    }

    .report-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .report-item {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        background: rgba(13, 15, 27, 0.7);
        padding: 1rem 1.25rem;
        border-radius: 12px;
        border: 1px solid var(--border);
        transition: border-color 0.2s ease;
    }
    .report-item:hover {
        border-color: var(--accent-soft);
    }
    
    .report-item a {
        color: var(--text);
        font-weight: 600;
        font-size: 1.1rem;
        text-decoration: none;
    }
    .report-item a:hover {
        color: var(--accent);
        text-decoration: underline;
    }
    .report-item span.report-date {
        font-size: 0.9rem;
        color: var(--muted);
        display: block;
        margin-top: 0.25rem;
    }
    
    .report-item-result {
        font-weight: 700;
        font-size: 1.1rem;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
    }
    
    /* --- MODIFIED: Use specific classes for viewer's result --- */
    .report-item-result.victory {
        color: var(--accent-green);
    }
    .report-item-result.defeat {
        color: var(--accent-red);
    }
    .report-item-result.stalemate {
        color: var(--accent-2);
    }
</style>

<div class="reports-container-full">
    <h1>Battle Reports</h1>

    <div class="report-table-container">
        
        <a href="/battle" class="btn-submit" style="background: var(--accent); color: #02030a;">Back to Battle</a>

        <ul class="report-list">
            <?php if (empty($reports)): ?>
                <li style="text-align: center; color: var(--muted); padding: 2rem;">You have no battle reports.</li>
            <?php else: ?>
                <?php foreach ($reports as $report): ?>
                    <?php
                        // --- NEW LOGIC ---
                        $isAttacker = ($report->attacker_id === $userId);
                        $opponentName = $isAttacker ? $report->defender_name : $report->attacker_name;
                        $title = $isAttacker ? "vs. " . htmlspecialchars($opponentName) : "from " . htmlspecialchars($opponentName);
                        
                        // Determine viewer's result
                        $viewerResult = $report->attack_result; // Attacker's result
                        if (!$isAttacker) {
                            // Invert result for defender
                            $viewerResult = match($report->attack_result) {
                                'victory' => 'defeat',
                                'defeat' => 'victory',
                                'stalemate' => 'stalemate',
                            };
                        }
                        $resultClass = $viewerResult; // 'victory', 'defeat', or 'stalemate'
                        // --- END NEW LOGIC ---
                    ?>
                    <li class="report-item">
                        <div>
                            <a href="/battle/report/<?= $report->id ?>">
                                Report #<?= $report->id ?> (<?= $title ?>)
                            </a>
                            <span class="report-date"><?= $report->created_at ?></span>
                        </div>
                        <span class="report-item-result <?= $resultClass ?>">
                            <?= htmlspecialchars(ucfirst($viewerResult)) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>