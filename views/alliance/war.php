<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\War[] $activeWars */
/* @var \App\Models\Entities\WarHistory[] $historicalWars */
/* @var \App\Models\Entities\Alliance[] $otherAlliances */
/* @var \App\Models\Entities\User $viewer */
/* @var bool $canDeclareWar */
/* @var int $allianceId */
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
    .alliance-container-full {
        width: 100%;
        max-width: 1400px;
        margin-inline: auto;
        padding: 0;
        position: relative;
    }

    .alliance-container-full h1 {
        text-align: center;
        margin-bottom: 2rem;
        font-size: clamp(2.1rem, 3vw, 2.6rem);
        letter-spacing: -0.03em;
        color: #fff;
        padding-top: 1.5rem;
    }
    
    .alliance-container-full .btn-back {
        display: block;
        width: 100%;
        max-width: 400px;
        margin: 0 auto 1.5rem auto;
        text-align: center;
        text-decoration: none;
    }

    /* --- Grid for Action Cards (from Bank) --- */
    .item-grid {
        display: grid;
        grid-template-columns: 1fr 2fr; /* 1:2 ratio */
        gap: 1.5rem;
        max-width: 1200px;
        margin: 0 auto 1.5rem auto;
    }
    .grid-col-span-2 {
        grid-column: 1 / -1;
    }
    
    @media (max-width: 980px) {
        .item-grid {
            grid-template-columns: 1fr;
        }
    }

    /* --- Action Card (from Bank) --- */
    .item-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.25rem 1.5rem;
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
    }
    .item-card h4 {
        color: #fff;
        margin: 0 0 1rem 0;
        font-size: 1.1rem;
        border-bottom: 1px solid var(--border);
        padding-bottom: 0.75rem;
    }
    
    .item-card .btn-submit {
        width: 100%;
        margin-top: 0.5rem;
    }
    .btn-reject { background: var(--accent-red); }
    
    .form-group textarea {
        padding: 0.75rem;
        border-radius: 5px;
        border: 1px solid #3a3a5a;
        background: #2a2a4a;
        color: #e0e0e0;
        font-size: 1rem;
        min-height: 80px;
    }
    .form-note {
        font-size: 0.9rem;
        color: var(--muted);
        margin-top: -0.5rem;
        margin-bottom: 1rem;
    }

    /* --- List Styles (from Battle Reports) --- */
    .data-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .data-item {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        background: rgba(13, 15, 27, 0.7);
        padding: 1rem 1.25rem;
        border-radius: 12px;
        border: 1px solid var(--border);
    }
    .item-info .name {
        font-size: 1.1rem;
        color: var(--text);
        font-weight: 500;
    }
    .item-info .details {
        font-size: 0.9rem;
        color: var(--muted);
        display: block;
    }
</style>

<div class="alliance-container-full">
    <h1>Alliance War Room</h1>
    
    <a href="/alliance/profile/<?= $allianceId ?>" class="btn-submit btn-back" style="background: var(--accent-blue);">
        &laquo; Back to Alliance Profile
    </a>

    <div class="item-grid">
        <?php if ($canDeclareWar): ?>
            <div class="item-card">
                <h4>Declare War</h4>
                <form action="/alliance/war/declare" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    
                    <div class="form-group">
                        <label for="war_name">War Name</label>
                        <input type="text" name="war_name" id="war_name" required placeholder="e.g., The Eastern Expanse War">
                    </div>

                    <div class="form-group">
                        <label for="target_alliance_id">Target Alliance</label>
                        <select name="target_alliance_id" id="target_alliance_id" required>
                            <option value="">Select an alliance...</option>
                            <?php foreach ($otherAlliances as $a): ?>
                                <option value="<?= $a->id ?>">[<?= htmlspecialchars($a->tag) ?>] <?= htmlspecialchars($a->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="goal_key">War Goal</label>
                        <select name="goal_key" id="goal_key" required>
                            <option value="credits_plundered">Credits Plundered</option>
                            <option value="units_killed">Units Killed</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="goal_threshold">Goal Threshold</label>
                        <input type="number" name="goal_threshold" id="goal_threshold" required placeholder="e.g., 10000000">
                    </div>
                    
                    <div class="form-group">
                        <label for="casus_belli">Casus Belli (Reason for War)</label>
                        <textarea name="casus_belli" id="casus_belli" placeholder="e.g., Territorial expansion, retribution for spying..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit btn-reject">Declare War</button>
                </form>
            </div>
        <?php else: ?>
            <div class="item-card">
                <h4>Declare War</h4>
                <p class="action-message" style="color: var(--muted); text-align: center;">
                    You do not have the required permissions (`can_declare_war`) to declare war on behalf of your alliance.
                </p>
            </div>
        <?php endif; ?>

        <div class="item-card">
            <h4>Active Wars</h4>
            <ul class="data-list">
                <?php if (empty($activeWars)): ?>
                    <li class="data-item" style="color: var(--muted); justify-content: center;">There are no active wars.</li>
                <?php else: ?>
                    <?php foreach ($activeWars as $war): ?>
                        <li class="data-item">
                            <span class="item-info">
                                <span class="name"><?= htmlspecialchars($war->name) ?></span>
                                <span class="details">
                                    [YOU] vs [THEM] - Goal: <?= number_format($war->declarer_score) ?> / <?= number_format($war->goal_threshold) ?>
                                </span>
                            </span>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
            
            <h4 style="margin-top: 1.5rem;">War History</h4>
            <ul class="data-list">
                <?php if (empty($historicalWars)): ?>
                    <li class="data-item" style="color: var(--muted); justify-content: center;">There is no war history.</li>
                <?php else: ?>
                    <?php foreach ($historicalWars as $war): ?>
                        <li class="data-item">
                            <span class="item-info">
                                <span class="name"><?= htmlspecialchars($war->name) ?></span>
                                <span class="details">
                                    [WINNER] - Concluded on ...
                                </span>
                            </span>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>