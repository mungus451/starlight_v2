<div class="container-full">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-4 text-center text-neon-blue glitch-text" data-text="The Almanac">The Almanac</h1>
            <p class="text-center text-muted">Historical Dossiers & Records</p>
        </div>
    </div>

    <!-- Navigation Tabs (Armory Style) -->
    <div class="tabs-nav mb-4 justify-content-center">
        <a class="tab-link active" data-tab="players-content">
            <i class="fas fa-user-astronaut me-2"></i>Players
        </a>
        <a class="tab-link" data-tab="alliances-content">
            <i class="fas fa-users me-2"></i>Alliances
        </a>
    </div>

    <!-- ================= PLAYER TAB ================= -->
    <div id="players-content" class="tab-content active">
        
        <!-- Player Selection (Top Bar) -->
        <div class="card bg-black border-neon shadow-lg mb-4">
            <div class="card-body p-3">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <label class="form-label text-neon-blue text-uppercase fw-bold small">Select Commander</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-neon-blue"><i class="fas fa-search"></i></span>
                            <select id="player-select" class="form-select bg-dark text-light border-secondary">
                                <option value="" selected disabled>-- Choose a Pilot --</option>
                                <?php if (!empty($players)): ?>
                                    <?php foreach ($players as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['character_name']) ?></option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>No players found.</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Player Dossier Results (Hidden by default) -->
        <div id="player-dossier" class="d-none">
            
            <!-- Mobile-First Profile Header -->
            <div class="card bg-dark border-secondary mb-4">
                <div class="card-body d-flex flex-column flex-md-row align-items-center text-center text-md-start">
                    <div class="mb-3 mb-md-0 me-md-4 position-relative">
                        <div class="scanner-line"></div> 
                        <img id="player-avatar" src="" class="rounded-circle border border-2 border-neon-blue p-1" style="width: 120px; height: 120px; object-fit: cover;">
                    </div>
                    <div>
                        <h2 id="player-name" class="text-neon-blue display-6 fw-bold mb-1">Commander Name</h2>
                        <p id="player-bio" class="text-muted fst-italic mb-2 small">"No biography available."</p>
                        <span id="player-joined" class="badge bg-black border border-secondary text-light">Joined: YYYY-MM-DD</span>
                    </div>
                </div>
            </div>

            <!-- Stats Grid (Reusing Structures Grid Layout) -->
            <div class="structures-grid">
                
                <!-- Card 1: Max Plunder -->
                <div class="structure-card border-warning">
                    <div class="card-header-main justify-content-center text-center">
                        <div class="card-title-group">
                            <h3 class="card-title text-warning"><i class="fas fa-crown fa-2x mb-2 d-block"></i> Max Plunder</h3>
                        </div>
                    </div>
                    <div class="card-body-main text-center">
                         <div id="record-plunder" class="fs-2 fw-bold text-light">0</div>
                         <p class="card-description mt-2">Credits stolen in a single raid.</p>
                    </div>
                </div>

                <!-- Card 2: Deadliest Hit -->
                <div class="structure-card border-danger">
                    <div class="card-header-main justify-content-center text-center">
                        <div class="card-title-group">
                            <h3 class="card-title text-danger"><i class="fas fa-skull fa-2x mb-2 d-block"></i> Deadliest Hit</h3>
                        </div>
                    </div>
                    <div class="card-body-main text-center">
                         <div id="record-deadliest" class="fs-2 fw-bold text-light">0</div>
                         <p class="card-description mt-2">Enemy units killed in one strike.</p>
                    </div>
                </div>

                <!-- Card 3: Win Rate Chart -->
                <div class="structure-card">
                    <div class="card-header-main justify-content-center">
                        <h3 class="card-title">Win Rate</h3>
                    </div>
                    <div class="card-body-main p-0 position-relative" style="height: 200px;">
                        <canvas id="player-wl-chart"></canvas>
                    </div>
                </div>

                <!-- Card 4: K/D Ratio Chart -->
                <div class="structure-card">
                    <div class="card-header-main justify-content-center">
                        <h3 class="card-title">K/D Ratio</h3>
                    </div>
                    <div class="card-body-main p-0 position-relative" style="height: 200px;">
                        <canvas id="player-kd-chart"></canvas>
                    </div>
                </div>

                <!-- Card 5: Combat Log (Span 2 cols on desktop if possible, or just regular card) -->
                <div class="structure-card" style="grid-row: span 2;">
                    <div class="card-header-main">
                        <span class="card-icon"><i class="fas fa-list"></i></span>
                        <h3 class="card-title">Combat Log</h3>
                    </div>
                    <div class="card-body-main p-0">
                         <ul class="list-group list-group-flush bg-transparent small w-100">
                            <li class="list-group-item bg-transparent text-light d-flex justify-content-between border-secondary">
                                <span>Total Battles</span> <span id="stat-total-battles" class="fw-bold">0</span>
                            </li>
                            <li class="list-group-item bg-transparent text-light d-flex justify-content-between border-secondary">
                                <span>Victories</span> <span id="stat-wins" class="text-success fw-bold">0</span>
                            </li>
                            <li class="list-group-item bg-transparent text-light d-flex justify-content-between border-secondary">
                                <span>Defeats</span> <span id="stat-losses" class="text-danger fw-bold">0</span>
                            </li>
                            <li class="list-group-item bg-transparent text-light d-flex justify-content-between border-secondary">
                                <span>Kills</span> <span id="stat-killed" class="text-info fw-bold">0</span>
                            </li>
                            <li class="list-group-item bg-transparent text-light d-flex justify-content-between border-none">
                                <span>Deaths</span> <span id="stat-lost" class="text-warning fw-bold">0</span>
                            </li>
                        </ul>
                    </div>
                </div>

            </div> <!-- End Grid -->

        </div> <!-- End Player Dossier -->
    </div>

    <!-- ================= ALLIANCE TAB ================= -->
    <div id="alliances-content" class="tab-content">
         
         <!-- Alliance Selection -->
        <div class="card bg-black border-neon shadow-lg mb-4">
            <div class="card-body p-3">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <label class="form-label text-warning text-uppercase fw-bold small">Select Faction</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-warning"><i class="fas fa-flag"></i></span>
                            <select id="alliance-select" class="form-select bg-dark text-light border-secondary">
                                <option value="" selected disabled>-- Choose an Alliance --</option>
                                <?php if (!empty($alliances)): ?>
                                    <?php foreach ($alliances as $a): ?>
                                        <option value="<?= $a['id'] ?>">[<?= htmlspecialchars($a['tag']) ?>] <?= htmlspecialchars($a['name']) ?></option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>No alliances found.</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alliance Dossier Results -->
        <div id="alliance-dossier" class="d-none">

            <!-- Profile Header -->
            <div class="card bg-dark border-secondary mb-4">
                <div class="card-body d-flex flex-column flex-md-row align-items-center text-center text-md-start">
                    <div class="mb-3 mb-md-0 me-md-4">
                        <img id="alliance-avatar" src="" class="rounded border border-2 border-warning p-1" style="width: 140px; height: 140px; object-fit: cover;">
                    </div>
                    <div>
                        <h2 id="alliance-name" class="text-warning display-6 fw-bold mb-1">Alliance Name</h2>
                        <p id="alliance-desc" class="text-muted small mb-3">"Description..."</p>
                        <div class="d-flex gap-2 justify-content-center justify-content-md-start">
                            <span class="badge bg-black border border-secondary text-light"><i class="fas fa-users"></i> <span id="alliance-member-count">0</span> Members</span>
                            <span class="badge bg-danger bg-opacity-25 border border-danger text-danger"><i class="fas fa-fire"></i> <span id="alliance-wars">0</span> Wars</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="structures-grid">

                <!-- Card 1: Total Plunder -->
                <div class="structure-card border-warning">
                    <div class="card-header-main justify-content-center text-center">
                        <h3 class="card-title text-warning"><i class="fas fa-coins fa-2x mb-2 d-block"></i> Total Plunder</h3>
                    </div>
                    <div class="card-body-main text-center">
                         <div id="alliance-plunder" class="fs-2 fw-bold text-light">0</div>
                         <p class="card-description mt-2">Credits stolen by all members.</p>
                    </div>
                </div>

                <!-- Card 2: Victories -->
                <div class="structure-card border-success">
                    <div class="card-header-main justify-content-center text-center">
                        <h3 class="card-title text-success"><i class="fas fa-trophy fa-2x mb-2 d-block"></i> Victories</h3>
                    </div>
                    <div class="card-body-main text-center">
                         <div id="alliance-wins" class="fs-2 fw-bold text-light">0</div>
                         <p class="card-description mt-2">Member battles won.</p>
                    </div>
                </div>

                <!-- Card 3: Defeats -->
                <div class="structure-card border-danger">
                    <div class="card-header-main justify-content-center text-center">
                        <h3 class="card-title text-danger"><i class="fas fa-times-circle fa-2x mb-2 d-block"></i> Defeats</h3>
                    </div>
                    <div class="card-body-main text-center">
                         <div id="alliance-losses" class="fs-2 fw-bold text-light">0</div>
                         <p class="card-description mt-2">Member battles lost.</p>
                    </div>
                </div>

                 <!-- Card 4: Win Rate Chart -->
                <div class="structure-card">
                    <div class="card-header-main justify-content-center">
                        <h3 class="card-title">Win Distribution</h3>
                    </div>
                    <div class="card-body-main p-0 position-relative" style="height: 200px;">
                        <canvas id="alliance-wl-chart"></canvas>
                    </div>
                </div>

                <!-- Card 5: Roster (Full Width) -->
                 <div class="structure-card" style="grid-column: 1 / -1;">
                    <div class="card-header-main">
                        <span class="card-icon"><i class="fas fa-id-card"></i></span>
                        <h3 class="card-title">Active Roster</h3>
                    </div>
                    <div class="card-body-main p-0">
                         <div class="table-responsive w-100">
                            <table class="table table-dark table-hover mb-0 align-middle">
                                <thead class="small text-muted text-uppercase">
                                    <tr>
                                        <th class="ps-3">Name</th>
                                        <th>Role</th>
                                    </tr>
                                </thead>
                                <tbody id="alliance-roster">
                                    <!-- Populated via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div> <!-- End Grid -->

        </div> <!-- End Alliance Dossier -->
    </div>
</div>

<!-- Scripts (v5 for cache busting) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/js/almanac.js?v=5"></script>

<style>
/* --- Grid System (Borrowed from Structures) --- */
.structures-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
}

.structure-card {
    background: rgba(13, 17, 23, 0.85);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
    backdrop-filter: blur(10px);
    position: relative;
    overflow: hidden;
}

.structure-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
    border-color: rgba(255, 255, 255, 0.3);
}

.card-header-main {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.card-icon {
    font-size: 1.5rem;
    color: var(--accent);
    background: rgba(255, 255, 255, 0.05);
    padding: 0.5rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
}

.card-title-group {
    flex: 1;
}

.card-title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-color);
}

.card-description {
    font-size: 0.9rem;
    color: var(--muted);
    line-height: 1.5;
    margin-bottom: 1rem;
}

/* --- Tabs (Armory Style Match) --- */
.tabs-nav {
    display: flex;
    border-bottom: 2px solid rgba(255,255,255,0.1);
    margin-bottom: 1rem;
    gap: 1rem;
}
.tab-link {
    padding: 0.8rem 1.5rem;
    cursor: pointer;
    color: var(--muted);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 1px;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
    text-decoration: none;
    background: transparent;
    border-radius: 4px 4px 0 0;
}
.tab-link:hover {
    color: var(--text-color);
    background: rgba(255,255,255,0.05);
}
.tab-link.active {
    color: var(--accent);
    border-bottom-color: var(--accent);
    background: rgba(0, 243, 255, 0.05);
}
.tab-content {
    display: none;
    animation: fadeIn 0.3s ease;
}
.tab-content.active {
    display: block;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Neon Glitch Theme Overrides for Mobile */
.text-neon-blue { color: #00f3ff !important; text-shadow: 0 0 10px rgba(0, 243, 255, 0.7); }
.border-neon { border: 1px solid #00f3ff !important; box-shadow: 0 0 15px rgba(0, 243, 255, 0.2); }
.border-neon-blue { border-color: #00f3ff !important; }

/* Mobile Optimizations */
@media (max-width: 768px) {
    .display-4 { font-size: 2.5rem; }
    .structures-grid { grid-template-columns: 1fr; } 
    .tabs-nav { justify-content: space-around; } 
}
</style>
