<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\UserResource $attackerResources */
/* @var \App\Models\Entities\UserStats $attackerStats */
/* @var array $costs */
/* @var array $targets */
/* @var array $pagination */
/* @var int $perPage */
?>

<style>
    :root {
        --bg: radial-gradient(circle at 10% 0%, #0c101e 0%, #050712 42%, #02030a 75%);
        --card: radial-gradient(circle at 30% -10%, rgba(45, 209, 209, 0.07), rgba(13, 15, 27, 0.6));
        --border: rgba(255, 255, 255, 0.03);
        --accent: #2dd1d1; /* Main Accent (Teal) */
        --accent-soft: rgba(45, 209, 209, 0.12);
        --accent-2: #f9c74f; /* Secondary Accent (Gold) */
        --accent-red: #e53e3e;
        --text: #eff1ff;
        --muted: #a8afd4;
        --radius: 18px;
        --shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
    }

    /* --- Base Container --- */
    .battle-container-full {
        width: 100%;
        max-width: 1400px;
        margin-inline: auto;
        padding: 0;
        position: relative;
    }

    .battle-container-full h1 {
        text-align: center;
        margin-bottom: 2rem;
        font-size: clamp(2.1rem, 3vw, 2.6rem);
        letter-spacing: -0.03em;
        color: #fff;
        padding-top: 1.5rem;
    }

    /* --- Attacker Header Card (from Bank) --- */
    .attacker-header-card {
        background: radial-gradient(circle at top, rgba(45, 209, 209, 0.12), rgba(11, 13, 24, 0.9));
        border: 1px solid rgba(45, 209, 209, 0.4);
        border-radius: var(--radius);
        padding: 1rem 1.5rem;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-around;
        gap: 1.5rem;
        box-shadow: var(--shadow);
        backdrop-filter: blur(6px);
        margin-bottom: 2rem;
    }
    .header-stat {
        text-align: center;
        flex-grow: 1;
        /* --- FIX 1: Add flex alignment properties --- */
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .header-stat span {
        display: block;
        font-size: 0.9rem;
        color: var(--muted);
        margin-bottom: 0.25rem;
    }
    /* --- FIX 1.1: Ensure link/button inside span also centers --- */
    .header-stat span .btn-submit {
        margin-top: 0; /* Remove default button margin */
    }
    .header-stat strong {
        font-size: 1.5rem;
        color: #fff;
    }
    .header-stat strong.accent-gold {
        color: var(--accent-2);
    }
    .header-stat strong.accent-red {
        color: var(--accent-red);
    }

    /* --- Player List Table --- */
    .player-table-container {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1rem;
        box-shadow: var(--shadow);
        overflow-x: auto; /* Allow horizontal scroll on small screens */
    }
    
    .player-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 600px; /* Prevent squishing on mobile */
    }
    .player-table th, .player-table td {
        padding: 0.75rem 1rem;
        text-align: left;
        border-bottom: 1px solid var(--border);
        vertical-align: middle; /* Added for consistency */
    }
    .player-table th {
        color: var(--accent-2);
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .player-table tr:last-child td {
        border-bottom: none;
    }

    .player-cell {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .player-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--accent-soft);
        cursor: pointer;
        transition: transform 0.2s ease, border-color 0.2s ease;
    }
    .player-avatar:hover {
        transform: scale(1.1);
        border-color: var(--accent);
    }
    .player-avatar-svg {
        padding: 0.5rem;
        background: #1e1e3f;
        color: var(--muted);
    }
    .player-name {
        font-weight: 600;
        font-size: 1.1rem;
    }
    .player-name a {
        color: var(--text);
        text-decoration: none;
    }
    .player-name a:hover {
        text-decoration: underline;
        color: var(--accent);
    }
    .player-level {
        font-size: 0.9rem;
        color: var(--muted);
    }

    .btn-attack {
        padding: 0.6rem 1rem;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        background: var(--accent-red);
        color: white;
        border: none;
        cursor: pointer;
    }
    .btn-attack:hover {
        filter: brightness(1.1);
    }

    /* --- Pagination --- */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
        margin-top: 1.5rem;
    }
    .pagination a, .pagination span {
        color: var(--muted);
        text-decoration: none;
        padding: 0.5rem 0.75rem;
        border-radius: 5px;
        border: 1px solid var(--border);
        background: var(--card);
    }
    .pagination a:hover {
        background: rgba(255,255,255, 0.03);
        color: #fff;
        border-color: var(--accent);
    }
    .pagination span {
        background: var(--accent);
        color: #02030a;
        border-color: var(--accent);
        font-weight: bold;
    }
    
    /* --- Attack Modal --- */
    .modal-overlay {
        display: none; /* Hidden by default */
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.75);
        backdrop-filter: blur(8px);
        z-index: 100;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .modal-overlay.active {
        opacity: 1;
    }
    
    .modal-content {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 1.5rem;
        width: 100%;
        max-width: 500px;
        transform: scale(0.95);
        transition: transform 0.3s ease;
    }
    .modal-overlay.active .modal-content {
        transform: scale(1);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border);
        padding-bottom: 0.75rem;
        margin-bottom: 1rem;
    }
    .modal-header h3 {
        margin: 0;
        color: #fff;
        font-size: 1.3rem;
    }
    .modal-close-btn {
        background: none;
        border: none;
        color: var(--muted);
        font-size: 1.5rem;
        cursor: pointer;
        line-height: 1;
    }
    .modal-close-btn:hover {
        color: #fff;
    }
    
    .modal-summary {
        font-size: 1rem;
        color: var(--muted);
        background: #1e1e3f;
        padding: 1rem;
        border-radius: 5px;
        border: 1px solid var(--border);
        margin-bottom: 1rem;
    }
    .modal-summary strong {
        color: var(--accent-2);
    }
</style>

<div class="battle-container-full">
    <h1>Battle</h1>

    <div class="attacker-header-card">
        <div class="header-stat">
            <span>Your Army</span>
            <strong class="accent-red"><?= number_format($attackerResources->soldiers) ?> Soldiers</strong>
        </div>
        <div class="header-stat">
            <span>Your Attack Turns</span>
            <strong class="accent-gold"><?= number_format($attackerStats->attack_turns) ?> Turns</strong>
        </div>
        <div class="header-stat">
            <span>Operation Cost</span>
            <strong><?= $costs['attack_turn_cost'] ?> Turn(s)</strong>
        </div>
        <div class="header-stat">
            <span><a href="/battle/reports" class="btn-submit" style="background: var(--accent); color: #02030a;">View Battle Reports</a></span>
        </div>
    </div>

    <div class="player-table-container">
        <table class="player-table">
            <thead>
                <tr>
                    <th>Player</th>
                    <th>Level</th>
                    <th>Credits (On Hand)</th>
                    <th>Army Size</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($targets)): ?>
                    <tr><td colspan="5" style="text-align: center;">No other players found.</td></tr>
                <?php else: ?>
                    <?php foreach ($targets as $target): ?>
                        <tr class="player-row" 
                            data-target-id="<?= $target['id'] ?>"
                            data-target-name="<?= htmlspecialchars($target['character_name']) ?>">
                            
                            <td>
                                <div class="player-cell">
                                    
                                    <?php if ($target['profile_picture_url']): ?>
                                        <img src="/serve/avatar/<?= htmlspecialchars($target['profile_picture_url']) ?>" alt="Avatar" class="player-avatar btn-attack-modal">
                                    <?php else: ?>
                                        <svg class="player-avatar player-avatar-svg btn-attack-modal" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                        </svg>
                                    <?php endif; ?>

                                    <div>
                                        <span class="player-name">
                                            <a href="/profile/<?= $target['id'] ?>"><?= htmlspecialchars($target['character_name']) ?></a>
                                        </span>
                                        </div>
                                </div>
                            </td>
                            
                            <td><?= $target['level'] ?></td>

                            <td><?= number_format($target['credits']) ?></td>
                            <td><?= number_format($target['army_size']) ?></td>
                            
                            <td>
                                <button class="btn-attack btn-attack-modal">Attack</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <?php if ($pagination['totalPages'] > 1): ?>
            <?php if ($pagination['currentPage'] > 1): ?>
                <a href="/battle/page/<?= $pagination['currentPage'] - 1 ?>">&laquo; Prev</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                <?php if ($i == $pagination['currentPage']): ?>
                    <span><?= $i ?></span>
                <?php else: ?>
                    <a href="/battle/page/<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                <a href="/battle/page/<?= $pagination['currentPage'] + 1 ?>">Next &raquo;</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="modal-overlay" id="attack-modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirm Attack</h3>
            <button class="modal-close-btn" id="modal-close-btn">&times;</button>
        </div>
        
        <form action="/battle/attack" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            <input type="hidden" name="attack_type" value="plunder">
            <input type="hidden" name="target_name" id="modal-target-name" value="">
            
            <div class="modal-summary">
                You are about to launch an all-in "Plunder" operation against:
                <br>
                <strong id="modal-target-name-display"></strong>
                <br><br>
                This will send all <strong><?= number_format($attackerResources->soldiers) ?> soldiers</strong>
                and cost <strong><?= $costs['attack_turn_cost'] ?> attack turn(s)</strong>.
            </div>

            <button type="submit" class="btn-submit" style="width: 100%; background: var(--accent-red);" <?= $attackerResources->soldiers <= 0 ? 'disabled' : '' ?>>
                <?= $attackerResources->soldiers <= 0 ? 'You have no soldiers' : 'Launch Attack' ?>
            </button>
        </form>
    </div>
</div>

<script src="/js/battle.js"></script>