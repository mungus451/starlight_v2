<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\BattleReport[] $reports */
/* @var int $userId */
?>

<style>
    :root {
        --bg-dark: #050712;
        --card-bg: rgba(13, 15, 27, 0.85);
        --card-border: rgba(255, 255, 255, 0.05);
        --text-main: #eff1ff;
        --text-muted: #8f9bb3;
        --accent-teal: #2dd1d1;
        --accent-gold: #f9c74f;
        --accent-red: #ff4d4d;
        --accent-green: #00e676;
        --font-heading: "Orbitron", sans-serif;
    }

    .reports-container {
        width: 100%;
        max-width: 900px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }

    .page-header {
        text-align: center;
        margin-bottom: 1rem;
    }
    
    .page-header h1 {
        font-family: var(--font-heading);
        color: #fff;
        font-size: 2.5rem;
        text-shadow: 0 0 20px rgba(45, 209, 209, 0.4);
        margin-bottom: 0.5rem;
    }
    
    .page-header p {
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    /* Controls */
    .controls-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: rgba(255,255,255,0.03);
        padding: 1rem;
        border-radius: 12px;
        border: 1px solid var(--card-border);
    }

    .btn-action {
        background: linear-gradient(135deg, rgba(45, 209, 209, 0.2), rgba(45, 209, 209, 0.05));
        border: 1px solid rgba(45, 209, 209, 0.4);
        color: var(--accent-teal);
        padding: 0.6rem 1.2rem;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        font-size: 0.9rem;
    }
    .btn-action:hover {
        background: var(--accent-teal);
        color: #000;
        box-shadow: 0 0 15px rgba(45, 209, 209, 0.4);
    }

    /* Report Grid */
    .report-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .battle-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-left: 4px solid var(--card-border); /* Status indicator default */
        border-radius: 8px;
        padding: 1.2rem;
        display: grid;
        grid-template-columns: auto 1fr auto;
        align-items: center;
        gap: 1.5rem;
        transition: transform 0.2s, box-shadow 0.2s;
        text-decoration: none;
        color: inherit;
        position: relative;
        overflow: hidden;
    }
    
    .battle-card:hover {
        transform: translateX(4px);
        background: rgba(20, 22, 35, 0.95);
    }

    /* Status Styles */
    .status-victory { border-left-color: var(--accent-green); }
    .status-defeat { border-left-color: var(--accent-red); }
    .status-stalemate { border-left-color: var(--accent-gold); }

    .status-victory:hover { box-shadow: 0 4px 20px rgba(0, 230, 118, 0.1); }
    .status-defeat:hover { box-shadow: 0 4px 20px rgba(255, 77, 77, 0.1); }

    /* Icon Column */
    .card-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        background: rgba(255,255,255,0.05);
        color: var(--text-muted);
    }
    .status-victory .card-icon { color: var(--accent-green); background: rgba(0, 230, 118, 0.1); }
    .status-defeat .card-icon { color: var(--accent-red); background: rgba(255, 77, 77, 0.1); }

    /* Info Column */
    .card-info {
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
    }

    .card-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .role-badge {
        font-size: 0.65rem;
        text-transform: uppercase;
        padding: 2px 6px;
        border-radius: 4px;
        background: rgba(255,255,255,0.1);
        color: var(--text-muted);
        letter-spacing: 0.05em;
    }
    .role-attacker { color: var(--accent-gold); border: 1px solid rgba(249, 199, 79, 0.3); background: rgba(249, 199, 79, 0.1); }
    .role-defender { color: var(--accent-teal); border: 1px solid rgba(45, 209, 209, 0.3); background: rgba(45, 209, 209, 0.1); }

    .card-meta {
        font-size: 0.85rem;
        color: var(--text-muted);
    }
    
    .card-summary {
        font-size: 0.85rem;
        color: #ccc;
        margin-top: 0.2rem;
    }
    .gain-text { color: var(--accent-green); font-weight: 600; }
    .loss-text { color: var(--accent-red); font-weight: 600; }

    /* Result Column */
    .card-result {
        text-align: right;
        font-family: var(--font-heading);
        font-weight: bold;
        font-size: 1.2rem;
        letter-spacing: 0.05em;
    }
    .res-win { color: var(--accent-green); text-shadow: 0 0 10px rgba(0, 230, 118, 0.4); }
    .res-loss { color: var(--accent-red); text-shadow: 0 0 10px rgba(255, 77, 77, 0.4); }
    .res-draw { color: var(--accent-gold); }
    
    .card-date {
        display: block;
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 0.25rem;
        font-family: sans-serif;
        font-weight: normal;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .battle-card {
            grid-template-columns: 1fr auto; /* Icon moves to top or hidden */
            gap: 1rem;
            padding: 1rem;
        }
        .card-icon {
            display: none; /* Hide icon on small mobile to save space */
        }
        .card-title {
            font-size: 1rem;
        }
    }
</style>

<div class="reports-container">
    
    <div class="page-header">
        <h1>Battle Log</h1>
        <p>Tactical history of your recent military engagements.</p>
    </div>

    <div class="controls-bar">
        <span style="color: var(--text-muted); font-size: 0.9rem;">
            Showing recent operations
        </span>
        <a href="/battle" class="btn-action">New Operation</a>
    </div>

    <div class="report-grid">
        <?php if (empty($reports)): ?>
            <div style="text-align: center; padding: 4rem; color: var(--text-muted); background: var(--card-bg); border-radius: 8px;">
                No battle reports found. The galaxy is quiet... for now.
            </div>
        <?php else: ?>
            <?php foreach ($reports as $report): 
                // --- FIXED LOGIC START ---
                $isAttacker = ($report->attacker_id === $userId);
                
                // Determine if the viewer won based on their role and the attack_result
                $isWinner = false;
                $isStalemate = ($report->attack_result === 'stalemate');
                
                if (!$isStalemate) {
                    if ($isAttacker) {
                        // As attacker, 'victory' means I won
                        $isWinner = ($report->attack_result === 'victory');
                    } else {
                        // As defender, 'defeat' (attacker's result) means I won
                        $isWinner = ($report->attack_result === 'defeat');
                    }
                }
                
                // Determine Visual State
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

                // Opponent Name
                $opponentName = $isAttacker ? $report->defender_name : $report->attacker_name;
                $opponentName = htmlspecialchars($opponentName ?? 'Unknown Target');
                
                // Role Text
                $roleText = $isAttacker ? 'Attacker' : 'Defender';
                $roleClass = $isAttacker ? 'role-attacker' : 'role-defender';
                // --- FIXED LOGIC END ---
            ?>
            
            <a href="/battle/report/<?= $report->id ?>" class="battle-card <?= $statusClass ?>">
                
                <div class="card-icon">
                    <?= $icon ?>
                </div>

                <div class="card-info">
                    <div class="card-title">
                        <span>vs <?= $opponentName ?></span>
                        <span class="role-badge <?= $roleClass ?>"><?= $roleText ?></span>
                    </div>
                    
                    <div class="card-summary">
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

                <div class="card-result <?= $resultClass ?>">
                    <?= $resultText ?>
                    <span class="card-date"><?= date('M j, H:i', strtotime($report->created_at)) ?></span>
                </div>
            </a>
            
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>