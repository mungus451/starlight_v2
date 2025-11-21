<?php
// --- Helper variables from the controller ---
// $profile = $data['profile']
// $stats = $data['stats']
// $alliance = $data['alliance'] (Entity or null)
// $viewer = $data['viewer']
?>

<style>
    :root {
        --bg: radial-gradient(circle at 10% 0%, #0c101e 0%, #050712 42%, #02030a 75%);
        --panel: rgba(12, 14, 25, 0.68);
        --card: radial-gradient(circle at 30% -10%, rgba(45, 209, 209, 0.08), rgba(13, 15, 27, 0.75));
        --border: rgba(255, 255, 255, 0.035);
        --accent: #2dd1d1;
        --accent-2: #f9c74f;
        --accent-red: #e53e3e;
        --accent-blue: #7683f5;
        --text: #eff1ff;
        --muted: #a8afd4;
        --radius: 18px;
        --shadow: 0 14px 35px rgba(0, 0, 0, 0.4);
    }

    /* --- Base Layout --- */
    .profile-container-full {
        width: 100%;
        max-width: 1400px;
        margin: 0 auto;
        padding: 1.5rem 0 3.5rem;
    }
    
    .profile-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 1.5rem;
    }

    @media (max-width: 1024px) {
        .profile-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 768px) {
        .profile-grid {
            grid-template-columns: 1fr;
        }
    }
    
    /* --- Shared Card Styles --- */
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
    }
    .data-card h3::before {
        content: "";
        width: 4px;
        height: 16px;
        border-radius: 999px;
        background: linear-gradient(180deg, #2dd1d1, rgba(2, 3, 10, 0));
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
    .data-card li span:first-child {
        font-weight: 500;
        color: rgba(239, 241, 255, 0.7);
        font-size: 0.85rem;
    }
    .data-card li span:last-child {
        font-weight: 600;
        color: #fff;
        text-align: right;
        font-size: 0.85rem;
    }

    /* --- 1. Header Card --- */
    .profile-header-card {
        grid-column: 1 / -1; /* Span full width */
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
        padding: 2rem;
        background: radial-gradient(circle at top, rgba(45, 209, 209, 0.12), rgba(11, 13, 24, 0.9));
        border: 1px solid rgba(45, 209, 209, 0.4);
    }
    .profile-avatar {
        width: 130px;
        height: 130px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--accent);
        box-shadow: 0 0 40px rgba(45, 209, 209, 0.3);
    }
    .profile-avatar-svg {
        padding: 2rem;
        background: #1e1e3f;
        color: var(--muted);
    }
    .profile-header-card h1 {
        margin: 0;
        color: #fff;
        font-size: 2.2rem;
    }
    .profile-header-card .alliance-tag {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--accent-2);
        text-decoration: none;
    }
    .profile-header-card .alliance-tag:hover {
        text-decoration: underline;
    }
    .profile-header-card .member-since {
        font-size: 0.9rem;
        color: var(--muted);
    }

    /* --- 2. Action Card --- */
    .action-card {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .action-card .btn-submit {
        width: 100%;
        margin-top: 0;
        text-align: center;
    }
    .btn-attack { background: var(--accent-red); }
    .btn-attack:hover { background: #ff5252; }
    .btn-spy { background: var(--accent-blue); }
    .btn-spy:hover { background: #8a96ff; }
    .btn-invite { background: #4CAF50; }
    .btn-invite:hover { background: #66bb6a; }

    /* --- 3. Bio Card --- */
    .bio-card {
        grid-column: span 2; /* Take up 2/3 columns */
    }
    @media (max-width: 1024px) {
        .bio-card {
            grid-column: 1 / -1; /* Span full on tablet */
            order: 99; /* Move to bottom */
        }
    }
    @media (max-width: 768px) {
        .bio-card {
            grid-column: span 1; /* Back to 1 col */
        }
    }
    .bio-content {
        font-size: 1rem;
        color: var(--muted);
        line-height: 1.6;
        white-space: pre-wrap; /* Respects newlines */
    }

    /* --- Modal CSS --- */
    .modal-overlay {
        display: none;
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
    .modal-header h3 { margin: 0; color: #fff; font-size: 1.3rem; }
    .modal-close-btn { background: none; border: none; color: var(--muted); font-size: 1.5rem; cursor: pointer; line-height: 1; }
    .modal-close-btn:hover { color: #fff; }
    .modal-summary { font-size: 1rem; color: var(--muted); background: #1e1e3f; padding: 1rem; border-radius: 5px; border: 1px solid var(--border); margin-bottom: 1rem; }
    .modal-summary strong { color: var(--accent-2); }
</style>

<div class="profile-container-full">
    <div class="profile-grid">

        <div class="data-card profile-header-card">
            <?php if ($profile['profile_picture_url']): ?>
                <img src="/serve/avatar/<?= htmlspecialchars($profile['profile_picture_url']) ?>" alt="Avatar" class="profile-avatar">
            <?php else: ?>
                <svg class="profile-avatar profile-avatar-svg" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                </svg>
            <?php endif; ?>
            
            <h1><?= htmlspecialchars($profile['character_name']) ?></h1>
            
            <?php if ($alliance): ?>
                <a href="/alliance/profile/<?= $alliance->id ?>" class="alliance-tag">
                    [<?= htmlspecialchars($alliance->tag) ?>] <?= htmlspecialchars($alliance->name) ?>
                </a>
            <?php else: ?>
                <span class="member-since">No Alliance</span>
            <?php endif; ?>
            
            <span class="member-since">Member Since: <?= (new DateTime($profile['created_at']))->format('F j, Y') ?></span>
        </div>
        
        <div class="data-card action-card">
            <h3>Actions</h3>
            
            <button class="btn-submit btn-attack" data-modal-target="attack-modal">Attack</button>
            <button class="btn-submit btn-spy" data-modal-target="spy-modal">Spy</button>
            
            <?php if ($viewer['can_invite']): ?>
                <form action="/alliance/invite/<?= $profile['id'] ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    <button type="submit" class="btn-submit btn-invite">Invite to your Alliance</button>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="data-card">
            <h3>Character Stats</h3>
            <ul>
                <li><span>Level:</span> <span><?= $stats->level ?></span></li>
                <li><span>Net Worth:</span> <span><?= number_format($stats->net_worth) ?></span></li>
                <li><span>Experience:</span> <span><?= number_format($stats->experience) ?></span></li>
                <li><span>War Prestige:</span> <span><?= number_format($stats->war_prestige) ?></span></li>
            </ul>
        </div>
        
        <div class="data-card bio-card">
            <h3>Commander Bio</h3>
            <div class="bio-content">
                <?php if (!empty($profile['bio'])): ?>
                    <?= htmlspecialchars($profile['bio']) ?>
                <?php else: ?>
                    This commander has not written a bio.
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<div class="modal-overlay" id="attack-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirm Attack</h3>
            <button class="modal-close-btn">&times;</button>
        </div>
        
        <form action="/battle/attack" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            <input type="hidden" name="attack_type" value="plunder">
            <input type="hidden" name="target_name" value="<?= htmlspecialchars($profile['character_name']) ?>">
            
            <div class="modal-summary">
                You are about to launch an all-in "Plunder" operation against:
                <br>
                <strong><?= htmlspecialchars($profile['character_name']) ?></strong>
                <br><br>
                This will use one attack turn and send all available soldiers.
            </div>
            <button type="submit" class="btn-submit" style="width: 100%; background: var(--accent-red);">Launch Attack</button>
        </form>
    </div>
</div>

<div class="modal-overlay" id="spy-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirm Espionage</h3>
            <button class="modal-close-btn">&times;</button>
        </div>
        
        <form action="/spy/conduct" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            <input type="hidden" name="target_name" value="<?= htmlspecialchars($profile['character_name']) ?>">
            
            <div class="modal-summary">
                You are about to launch an all-in "Espionage" operation against:
                <br>
                <strong><?= htmlspecialchars($profile['character_name']) ?></strong>
                <br><br>
                This will use one attack turn and send all available spies.
            </div>
            <button type="submit" class="btn-submit" style="width: 100%; background: var(--accent-blue);">Launch Operation</button>
        </form>
    </div>
</div>

<script src="/js/profile.js"></script>