<?php
// --- Helper variables from the controller ---
// $profile = $data['profile']
// $stats = $data['stats']
// $alliance = $data['alliance'] (Entity or null)
// $viewer = $data['viewer']
?>

<div class="container-full">
    <div class="dashboard-grid">

        <!-- Header -->
        <div class="player-header">
            <div class="player-info">
                <?php if ($profile['profile_picture_url']): ?>
                    <img src="/serve/avatar/<?= htmlspecialchars($profile['profile_picture_url']) ?>" alt="Avatar" class="player-avatar">
                <?php else: ?>
                    <svg class="player-avatar player-avatar-svg" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                    </svg>
                <?php endif; ?>
                
                <div>
                    <h2><?= htmlspecialchars($profile['character_name']) ?></h2>
                    <span class="sub-text">
                        <?php if ($alliance): ?>
                            <a href="/alliance/profile/<?= $alliance->id ?>">
                                [<?= htmlspecialchars($alliance->tag) ?>] <?= htmlspecialchars($alliance->name) ?>
                            </a>
                        <?php else: ?>
                            No Alliance
                        <?php endif; ?>
                    </span>
                    <span class="sub-text" style="font-size: 0.8rem;">
                        Member Since: <?= $profile['formatted_created_at'] ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Stats Card -->
        <div class="data-card grid-col-span-1">
            <div class="card-header">
                <h3>Public Stats</h3>
            </div>
            <ul class="card-stats-list">
                <li><span>Level</span> <span><?= $stats->level ?></span></li>
                <li><span>Net Worth</span> <span><?= number_format($stats->net_worth) ?></span></li>
                <li><span>War Prestige</span> <span><?= number_format($stats->war_prestige) ?></span></li>
            </ul>
        </div>

        <!-- Bio Card -->
        <div class="data-card grid-col-span-2">
            <div class="card-header">
                <h3>Commander Bio</h3>
            </div>
            <div style="font-size: 0.95rem; color: var(--muted); line-height: 1.6; white-space: pre-wrap;">
                <?= !empty($profile['bio']) ? htmlspecialchars($profile['bio']) : 'This commander has not written a bio.' ?>
            </div>
        </div>
        
        <!-- Actions Card -->
        <div class="data-card grid-col-span-3">
            <div class="card-header">
                <h3>Actions</h3>
            </div>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <button class="btn-submit btn-reject btn-attack-modal" data-target-name="<?= htmlspecialchars($profile['character_name']) ?>" style="width: auto; margin: 0;">
                    Attack
                </button>
                
                <button class="btn-submit btn-accent btn-spy-modal" style="width: auto; margin: 0;">
                    Spy
                </button>
                
                <?php if ($viewer['can_invite']): ?>
                    <form action="/alliance/invite/<?= $profile['id'] ?>" method="POST" style="margin: 0;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                        <button type="submit" class="btn-submit btn-accept" style="width: auto; margin: 0;">
                            Invite to Alliance
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- Attack Modal -->
<div class="modal-overlay" id="attack-modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirm Attack</h3>
            <button class="modal-close-btn" id="attack-modal-close">&times;</button>
        </div>
        <form action="/battle/attack" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            <input type="hidden" name="attack_type" value="plunder">
            <input type="hidden" name="target_id" value="<?= $profile['id'] ?>">
            
            <div class="modal-summary">
                Launch a full scale attack on <strong><?= htmlspecialchars($profile['character_name']) ?></strong>?
            </div>

            <div class="mb-3">
                <label for="attack_turns" class="form-label text-muted">Select Attack Turns (1-10):</label>
                <select name="attack_turns" id="attack_turns" class="form-select bg-dark text-light border-secondary">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?= $i ?>"><?= $i ?> Turn(s)</option>
                    <?php endfor; ?>
                </select>
            </div>

            <button type="submit" class="btn-submit btn-reject" style="width: 100%;">Launch Attack</button>
        </form>
    </div>
</div>

<!-- Spy Modal -->
<div class="modal-overlay" id="spy-modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirm Espionage</h3>
            <button class="modal-close-btn" id="spy-modal-close">&times;</button>
        </div>
        <form action="/spy/handle" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            <input type="hidden" name="target_id" value="<?= $profile['id'] ?>">
            
            <div class="modal-summary">
                Deploy spies against <strong><?= htmlspecialchars($profile['character_name']) ?></strong>?
            </div>
            <button type="submit" class="btn-submit btn-accent" style="width: 100%;">Launch Operation</button>
        </form>
    </div>
</div>

<!-- Simple script to handle profile specific modals since battle.js/spy.js might assume list view -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Attack Modal
    const atkModal = document.getElementById('attack-modal-overlay');
    const atkBtns = document.querySelectorAll('.btn-attack-modal');
    const atkClose = document.getElementById('attack-modal-close');
    
    if(atkModal && atkBtns) {
        atkBtns.forEach(btn => btn.addEventListener('click', (e) => {
            e.preventDefault();
            atkModal.classList.add('active');
        }));
        atkClose.addEventListener('click', () => atkModal.classList.remove('active'));
    }

    // Spy Modal
    const spyModal = document.getElementById('spy-modal-overlay');
    const spyBtns = document.querySelectorAll('.btn-spy-modal');
    const spyClose = document.getElementById('spy-modal-close');
    
    if(spyModal && spyBtns) {
        spyBtns.forEach(btn => btn.addEventListener('click', (e) => {
            e.preventDefault();
            spyModal.classList.add('active');
        }));
        spyClose.addEventListener('click', () => spyModal.classList.remove('active'));
    }

    // Close on outside click
    window.addEventListener('click', (e) => {
        if (e.target === atkModal) atkModal.classList.remove('active');
        if (e.target === spyModal) spyModal.classList.remove('active');
    });
});
</script>