<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\Treaty[] $treaties */
/* @var \App\Models\Entities\Rivalry[] $rivalries */
/* @var \App\Models\Entities\Alliance[] $otherAlliances */
/* @var \App\Models\Entities\User $viewer */
/* @var bool $canManage */
/* @var int $allianceId */

// --- NEW: Filter treaties for display ---
$proposed_to_us = [];
$proposed_by_us = [];
$active = [];
$historical = [];

foreach ($treaties as $treaty) {
    if ($treaty->status === 'proposed') {
        if ($treaty->alliance2_id === $allianceId) {
            $proposed_to_us[] = $treaty;
        } else {
            $proposed_by_us[] = $treaty;
        }
    } elseif ($treaty->status === 'active') {
        $active[] = $treaty;
    } else {
        $historical[] = $treaty;
    }
}
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
        grid-template-columns: 1fr 1fr; /* 2 cards on desktop */
        gap: 1.5rem;
        max-width: 1200px; /* Constrain the forms */
        margin: 0 auto 1.5rem auto; /* Center the form grid */
    }
    .grid-col-span-2 {
        grid-column: 1 / -1;
    }
    
    @media (max-width: 980px) {
        .item-grid {
            grid-template-columns: 1fr; /* 1 card on mobile */
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
    .btn-accept { background: var(--accent-green); }
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
    .item-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .item-actions .btn-submit {
        margin-top: 0;
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
        width: auto;
    }
    .status-proposed { color: var(--accent-blue); }
    .status-active { color: var(--accent-green); }
    .status-broken, .status-declined { color: var(--accent-red); }
</style>

<div class="alliance-container-full">
    <h1>Alliance Diplomacy</h1>
    
    <a href="/alliance/profile/<?= $allianceId ?>" class="btn-submit btn-back" style="background: var(--accent-blue);">
        &laquo; Back to Alliance Profile
    </a>

    <div class="item-grid">
        <?php if ($canManage): ?>
            <div class="item-card">
                <h4>Propose Treaty</h4>
                <form action="/alliance/diplomacy/treaty/propose" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
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
                        <label for="treaty_type">Treaty Type</label>
                        <select name="treaty_type" id="treaty_type" required>
                            <option value="peace">Peace</option>
                            <option value="non_aggression">Non-Aggression Pact</option>
                            <option value="mutual_defense">Mutual Defense</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="terms">Terms (Optional)</label>
                        <textarea name="terms" id="terms" placeholder="e.g., Alliance A pays 10M credits to Alliance B..."></textarea>
                    </div>
                    <button type="submit" class="btn-submit">Propose Treaty</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="item-card">
            <h4>Declare Rivalry</h4>
            <form action="/alliance/diplomacy/rivalry/declare" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                <div class="form-group">
                    <label for="target_rival_id">Target Alliance</label>
                    <select name="target_alliance_id" id="target_rival_id" required>
                        <option value="">Select an alliance...</option>
                        <?php foreach ($otherAlliances as $a): ?>
                            <option value="<?= $a->id ?>">[<?= htmlspecialchars($a->tag) ?>] <?= htmlspecialchars($a->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-submit btn-reject">Declare Rivalry</button>
            </form>
        </div>

        <div class="item-card grid-col-span-2">
            <h4>Diplomatic Relations</h4>
            
            <h5 style="color: var(--accent-blue); margin: 0.5rem 0;">Pending Proposals (<?= count($proposed_to_us) ?>)</h5>
            <ul class="data-list">
                <?php if (empty($proposed_to_us)): ?>
                    <li class="data-item" style="color: var(--muted); justify-content: center;">No pending proposals received.</li>
                <?php endif; ?>
                <?php foreach ($proposed_to_us as $treaty): ?>
                    <li class="data-item">
                        <span class="item-info">
                            <span class="name"><?= htmlspecialchars(ucfirst($treaty->treaty_type)) ?></span>
                            <span class="details">Proposed by <strong><?= htmlspecialchars($treaty->alliance1_name) ?></strong></span>
                        </span>
                        <?php if ($canManage): ?>
                            <div class="item-actions">
                                <form action="/alliance/diplomacy/treaty/accept/<?= $treaty->id ?>" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                    <button type="submit" class="btn-submit btn-accept">Accept</button>
                                </form>
                                <form action="/alliance/diplomacy/treaty/decline/<?= $treaty->id ?>" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                    <button type="submit" class="btn-submit btn-reject">Decline</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <h5 style="color: var(--accent-green); margin: 1.5rem 0 0.5rem 0;">Active Treaties (<?= count($active) ?>)</h5>
            <ul class="data-list">
                <?php if (empty($active)): ?>
                    <li class="data-item" style="color: var(--muted); justify-content: center;">No active treaties.</li>
                <?php endif; ?>
                <?php foreach ($active as $treaty): ?>
                    <li class="data-item">
                        <span class="item-info">
                            <span class="name"><?= htmlspecialchars(ucfirst($treaty->treaty_type)) ?></span>
                            <span class="details">With <strong><?= htmlspecialchars($treaty->alliance1_id === $allianceId ? $treaty->alliance2_name : $treaty->alliance1_name) ?></strong></span>
                        </span>
                        <?php if ($canManage): ?>
                            <div class="item-actions">
                                <form action="/alliance/diplomacy/treaty/break/<?= $treaty->id ?>" method="POST" onsubmit="return confirm('Are you sure you want to break this treaty?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                    <button type="submit" class="btn-submit btn-reject">Break Treaty</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="item-card grid-col-span-2">
            <h4>Rivalries</h4>
            <ul class="data-list">
                <?php if (empty($rivalries)): ?>
                    <li class="data-item" style="color: var(--muted); justify-content: center;">No rivalries declared.</li>
                <?php endif; ?>
                <?php foreach ($rivalries as $rivalry): ?>
                    <?php 
                    $rivalName = $rivalry->alliance_a_id === $allianceId ? $rivalry->alliance_b_name : $rivalry->alliance_a_name;
                    ?>
                    <li class="data-item">
                        <span class="item-info">
                            <span class="name">Rivalry with <strong><?= htmlspecialchars($rivalName) ?></strong></span>
                            <span class="details">Heat Level: <?= $rivalry->heat_level ?></span>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>