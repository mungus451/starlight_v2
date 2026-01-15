<div id="alliances" class="tab-content">
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
    <div>
        <div class="mobile-card">
            <div class="mobile-card-header"><h3><i class="fas fa-coins text-warning"></i> Total Plunder</h3></div>
            <div class="mobile-card-content text-center">
                <div id="alliance-plunder" class="fs-2 fw-bold text-light">0</div>
                <p class="small text-muted mt-1">Credits stolen by all members.</p>
            </div>
        </div>
        <div class="mobile-card">
            <div class="mobile-card-header"><h3><i class="fas fa-trophy text-success"></i> Victories</h3></div>
            <div class="mobile-card-content text-center">
                <div id="alliance-wins" class="fs-2 fw-bold text-light">0</div>
                <p class="small text-muted mt-1">Total member battles won.</p>
            </div>
        </div>
        <div class="mobile-card">
            <div class="mobile-card-header"><h3><i class="fas fa-times-circle text-danger"></i> Defeats</h3></div>
            <div class="mobile-card-content text-center">
                <div id="alliance-losses" class="fs-2 fw-bold text-light">0</div>
                <p class="small text-muted mt-1">Total member battles lost.</p>
            </div>
        </div>
        <div class="mobile-card">
            <div class="mobile-card-header"><h3><i class="fas fa-chart-pie"></i> Win Distribution</h3></div>
            <div class="mobile-card-content" style="height: 250px;"><canvas id="alliance-wl-chart"></canvas></div>
        </div>
        <div class="mobile-card">
            <div class="mobile-card-header"><h3><i class="fas fa-id-card"></i> Active Roster</h3></div>
            <div class="mobile-card-content p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle small">
                        <thead class="small text-muted text-uppercase">
                            <tr>
                                <th class="ps-3">Name</th>
                                <th class="text-end pe-3">Role</th>
                            </tr>
                        </thead>
                        <tbody id="alliance-roster">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
