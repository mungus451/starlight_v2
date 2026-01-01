<!-- Alliance Selection -->
<div class="mobile-card">
    <div class="mobile-card-content" style="display: block;">
        <label class="form-label text-warning text-uppercase fw-bold small">Select Faction</label>
        <select id="alliance-select" class="form-select bg-dark text-light border-secondary">
            <option value="" selected disabled>-- Choose an Alliance --</option>
            <?php if (!empty($alliances)): ?>
                <?php foreach ($alliances as $a): ?>
                    <option value="<?= $a['id'] ?>">[<?= htmlspecialchars($a['tag']) ?>] <?= htmlspecialchars($a['name']) ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>
</div>

<!-- Alliance Dossier Results (Hidden by default) -->
<div id="alliance-dossier" class="d-none">
    <!-- Profile Header -->
    <div class="mobile-card">
        <div class="mobile-card-content d-flex flex-column align-items-center text-center">
            <div class="mb-3">
                <img id="alliance-avatar" src="" class="rounded border border-2 border-warning p-1" style="width: 140px; height: 140px; object-fit: cover;">
            </div>
            <div>
                <h2 id="alliance-name" class="text-warning h4 fw-bold mb-1">Alliance Name</h2>
                <p id="alliance-desc" class="text-muted small mb-3">"Description..."</p>
                <div class="d-flex gap-2 justify-content-center">
                    <span class="badge bg-black border border-secondary text-light"><i class="fas fa-users"></i> <span id="alliance-member-count">0</span> Members</span>
                    <span class="badge bg-danger bg-opacity-25 border border-danger text-danger"><i class="fas fa-fire"></i> <span id="alliance-wars">0</span> Wars</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="structures-grid">
        <!-- Stats Cards here... -->
    </div>
</div>
