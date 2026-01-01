<!-- Player Selection -->
<div class="mobile-card">
    <div class="mobile-card-content" style="display: block;">
        <label class="form-label text-neon-blue text-uppercase fw-bold small">Select Commander</label>
        <select id="player-select" class="form-select bg-dark text-light border-secondary">
            <option value="" selected disabled>-- Choose a Pilot --</option>
            <?php if (!empty($players)): ?>
                <?php foreach ($players as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['character_name']) ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>
</div>

<!-- Player Dossier Results (Hidden by default) -->
<div id="player-dossier" class="d-none">
    <!-- Profile Header -->
    <div class="mobile-card">
        <div class="mobile-card-content d-flex flex-column align-items-center text-center">
            <div class="position-relative mb-3">
                <div class="scanner-line"></div>
                <img id="player-avatar" src="" class="rounded-circle border border-2 border-neon-blue p-1" style="width: 120px; height: 120px; object-fit: cover;">
            </div>
            <div>
                <h2 id="player-name" class="text-neon-blue h4 fw-bold mb-1">Commander Name</h2>
                <p id="player-bio" class="text-muted fst-italic mb-2 small">"No biography available."</p>
                <span id="player-joined" class="badge bg-black border border-secondary text-light small">Joined: YYYY-MM-DD</span>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="structures-grid">
        <!-- Stats Cards here... -->
    </div>
</div>
