<?php
// --- Almanac View (Advisor V2) ---
/* @var string $pageTitle */
/* @var array $players */
/* @var array $alliances */
?>

<div class="structures-page-content">
    
    <!-- 1. Page Header -->
    <div class="page-header-container">
        <h1 class="page-title-neon">Imperial Archives</h1>
        <p class="page-subtitle-tech">
            Historical Dossiers // Classified Records // Galactic History
        </p>
        <div class="flex-center gap-2 mt-2">
            <div class="badge bg-dark border-info">
                <i class="fas fa-user-astronaut"></i> <?= count($players) ?> Commanders
            </div>
            <div class="badge bg-dark border-warning">
                <i class="fas fa-flag"></i> <?= count($alliances) ?> Factions
            </div>
        </div>
    </div>

    <!-- 2. Navigation Deck -->
    <div class="structure-nav-container mb-4">
        <button class="structure-nav-btn active" data-tab-target="players-content">
            <i class="fas fa-id-card"></i> Commanders
        </button>
        <button class="structure-nav-btn" data-tab-target="alliances-content">
            <i class="fas fa-users"></i> Factions
        </button>
    </div>

    <!-- ================= PLAYER TAB ================= -->
    <div id="players-content" class="structure-category-container active">
        
        <!-- Search Console -->
        <div class="structure-card mb-4" style="border-color: var(--accent-blue); overflow: visible;">
            <div class="card-body-main p-3">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <label class="text-neon-blue text-uppercase fw-bold small mb-2 d-block">
                            <i class="fas fa-search me-1"></i> Access Personnel Records
                        </label>
                        
                        <!-- Live Search Console -->
                        <div class="search-console-wrapper position-relative" style="width: 100%; z-index: 100;">
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-neon-blue">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" id="player-search-input" class="form-control bg-dark border-secondary text-light" placeholder="Search by Name..." autocomplete="off">
                            </div>
                            <div id="player-search-results" class="autocomplete-results border-neon-blue"></div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Player Dossier Results -->
        <div id="player-dossier" class="d-none">
            
            <!-- Profile Header -->
            <div class="structure-card mb-4">
                <div class="card-body-main d-flex flex-column flex-md-row align-items-center text-center text-md-start p-4">
                    <div class="mb-3 mb-md-0 me-md-4 position-relative">
                        <div class="scanner-line"></div> 
                        <img id="player-avatar" src="" class="rounded-circle border border-2 border-neon-blue p-1" style="width: 120px; height: 120px; object-fit: cover;">
                    </div>
                    <div>
                        <h2 id="player-name" class="text-neon-blue display-6 fw-bold mb-1" style="font-family: 'Orbitron', sans-serif;">Commander Name</h2>
                        <p id="player-bio" class="text-muted fst-italic mb-2 small">"No biography available."</p>
                        <span id="player-joined" class="badge bg-black border border-secondary text-light">Joined: YYYY-MM-DD</span>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="structures-grid">
                
                <!-- Card: Max Plunder -->
                <div class="structure-card" style="border-color: var(--accent-2);">
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

                <!-- Card: Deadliest Hit -->
                <div class="structure-card" style="border-color: var(--accent-red);">
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

                <!-- Card: Win Rate -->
                <div class="structure-card">
                    <div class="card-header-main justify-content-center">
                        <h3 class="card-title">Win Rate</h3>
                    </div>
                    <div class="card-body-main p-0 position-relative" style="height: 200px;">
                        <canvas id="player-wl-chart"></canvas>
                    </div>
                </div>

                <!-- Card: K/D Ratio -->
                <div class="structure-card">
                    <div class="card-header-main justify-content-center">
                        <h3 class="card-title">K/D Ratio</h3>
                    </div>
                    <div class="card-body-main p-0 position-relative" style="height: 200px;">
                        <canvas id="player-kd-chart"></canvas>
                    </div>
                </div>

                <!-- Card: Combat Log -->
                <div class="structure-card" style="grid-column: span 1; grid-row: span 2;">
                    <div class="card-header-main">
                        <span class="card-icon"><i class="fas fa-list"></i></span>
                        <div class="card-title-group">
                            <h4>Combat Log</h4>
                        </div>
                    </div>
                    <div class="card-body-main p-0">
                         <ul class="list-group list-group-flush bg-transparent small w-100">
                            <li class="list-group-item bg-transparent text-light d-flex justify-content-between border-secondary p-3">
                                <span>Total Battles</span> <span id="stat-total-battles" class="fw-bold">0</span>
                            </li>
                            <li class="list-group-item bg-transparent text-light d-flex justify-content-between border-secondary p-3">
                                <span>Victories</span> <span id="stat-wins" class="text-success fw-bold">0</span>
                            </li>
                            <li class="list-group-item bg-transparent text-light d-flex justify-content-between border-secondary p-3">
                                <span>Defeats</span> <span id="stat-losses" class="text-danger fw-bold">0</span>
                            </li>
                            <li class="list-group-item bg-transparent text-light d-flex justify-content-between border-secondary p-3">
                                <span>Kills</span> <span id="stat-killed" class="text-info fw-bold">0</span>
                            </li>
                            <li class="list-group-item bg-transparent text-light d-flex justify-content-between border-secondary p-3">
                                <span>Deaths</span> <span id="stat-lost" class="text-warning fw-bold">0</span>
                            </li>
                            <li class="list-group-item bg-transparent text-light d-flex justify-content-between border-none p-3">
                                <span>Citizens Lost (Def)</span> <span id="stat-lost-defensive" class="text-danger fw-bold">0</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Card: Casualty Analysis -->
                <div class="structure-card">
                    <div class="card-header-main justify-content-center">
                        <h3 class="card-title">Casualty Analysis</h3>
                    </div>
                    <div class="card-body-main p-0 position-relative" style="height: 200px;">
                        <canvas id="player-casualty-chart"></canvas>
                    </div>
                </div>

                <!-- Espionage Header -->
                <div class="structure-card p-3 text-center" style="grid-column: 1 / -1; background: rgba(0,0,0,0.3); border-color: var(--accent-purple);">
                    <h2 class="m-0 text-purple" style="font-family: 'Orbitron', sans-serif;"><i class="fas fa-user-secret me-2"></i> Intelligence Directorate</h2>
                </div>

                <!-- Spy Success -->
                <div class="structure-card">
                    <div class="card-header-main justify-content-center">
                        <h3 class="card-title">Mission Success Rate</h3>
                    </div>
                    <div class="card-body-main p-0 position-relative" style="height: 200px;">
                        <canvas id="player-spy-chart"></canvas>
                    </div>
                </div>

                <!-- Spy Efficiency -->
                <div class="structure-card">
                    <div class="card-header-main justify-content-center">
                        <h3 class="card-title">Intel Efficiency (K/D)</h3>
                    </div>
                    <div class="card-body-main p-0 position-relative" style="height: 200px;">
                        <canvas id="player-spy-kd-chart"></canvas>
                    </div>
                </div>

                <!-- Espionage Log -->
                <div class="structure-card" style="grid-column: 1 / -1;">
                    <div class="card-header-main">
                        <span class="card-icon"><i class="fas fa-file-contract"></i></span>
                        <div class="card-title-group">
                            <h4>Espionage Operations</h4>
                        </div>
                    </div>
                    <div class="card-body-main p-0">
                         <div class="table-responsive w-100">
                            <table class="table table-dark table-hover mb-0 align-middle small" style="background: transparent;">
                                <tbody>
                                    <tr>
                                        <td class="text-muted ps-4 py-3">Missions Launched</td>
                                        <td class="text-end pe-4 fw-bold" id="spy-missions-total">0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted ps-4 py-3">Successful Infiltrations</td>
                                        <td class="text-end pe-4 fw-bold text-success" id="spy-missions-success">0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted ps-4 py-3">Spies Lost (M.I.A.)</td>
                                        <td class="text-end pe-4 fw-bold text-danger" id="spy-lost">0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted ps-4 py-3">Enemy Sentries Neutralized</td>
                                        <td class="text-end pe-4 fw-bold text-info" id="sentry-killed">0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted ps-4 py-3 border-top border-secondary">Defensive Interceptions</td>
                                        <td class="text-end pe-4 fw-bold border-top border-secondary text-warning" id="spy-intercepted">0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted ps-4 py-3">Enemy Spies Caught</td>
                                        <td class="text-end pe-4 fw-bold text-success" id="enemy-spy-caught">0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted ps-4 py-3">Sentries Lost (K.I.A.)</td>
                                        <td class="text-end pe-4 fw-bold text-danger" id="sentry-lost">0</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div> <!-- End Grid -->
        </div> <!-- End Player Dossier -->
    </div>

    <!-- ================= ALLIANCE TAB ================= -->
    <div id="alliances-content" class="structure-category-container">
         
         <!-- Alliance Search -->
        <div class="structure-card mb-4" style="border-color: var(--accent-2); overflow: visible;">
            <div class="card-body-main p-3">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <label class="text-warning text-uppercase fw-bold small mb-2 d-block">
                            <i class="fas fa-search me-1"></i> Access Faction Database
                        </label>
                        
                        <!-- Live Search Console -->
                        <div class="search-console-wrapper position-relative" style="width: 100%; z-index: 100;">
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-warning">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" id="alliance-search-input" class="form-control bg-dark border-secondary text-light" placeholder="Search by Name or Tag..." autocomplete="off">
                            </div>
                            <div id="alliance-search-results" class="autocomplete-results border-warning"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alliance Dossier Results -->
        <div id="alliance-dossier" class="d-none">

            <!-- Profile Header -->
            <div class="structure-card mb-4">
                <div class="card-body-main d-flex flex-column flex-md-row align-items-center text-center text-md-start p-4">
                    <div class="mb-3 mb-md-0 me-md-4">
                        <img id="alliance-avatar" src="" class="rounded border border-2 border-warning p-1" style="width: 140px; height: 140px; object-fit: cover;">
                    </div>
                    <div>
                        <h2 id="alliance-name" class="text-warning display-6 fw-bold mb-1" style="font-family: 'Orbitron', sans-serif;">Alliance Name</h2>
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

                <!-- Card: Total Plunder -->
                <div class="structure-card" style="border-color: var(--accent-2);">
                    <div class="card-header-main justify-content-center text-center">
                        <h3 class="card-title text-warning"><i class="fas fa-coins fa-2x mb-2 d-block"></i> Total Plunder</h3>
                    </div>
                    <div class="card-body-main text-center">
                         <div id="alliance-plunder" class="fs-2 fw-bold text-light">0</div>
                         <p class="card-description mt-2">Credits stolen by all members.</p>
                    </div>
                </div>

                <!-- Card: Victories -->
                <div class="structure-card" style="border-color: var(--accent-green);">
                    <div class="card-header-main justify-content-center text-center">
                        <h3 class="card-title text-success"><i class="fas fa-trophy fa-2x mb-2 d-block"></i> Victories</h3>
                    </div>
                    <div class="card-body-main text-center">
                         <div id="alliance-wins" class="fs-2 fw-bold text-light">0</div>
                         <p class="card-description mt-2">Member battles won.</p>
                    </div>
                </div>

                <!-- Card: Defeats -->
                <div class="structure-card" style="border-color: var(--accent-red);">
                    <div class="card-header-main justify-content-center text-center">
                        <h3 class="card-title text-danger"><i class="fas fa-times-circle fa-2x mb-2 d-block"></i> Defeats</h3>
                    </div>
                    <div class="card-body-main text-center">
                         <div id="alliance-losses" class="fs-2 fw-bold text-light">0</div>
                         <p class="card-description mt-2">Member battles lost.</p>
                    </div>
                </div>

                 <!-- Card: Win Rate Chart -->
                <div class="structure-card">
                    <div class="card-header-main justify-content-center">
                        <h3 class="card-title">Win Distribution</h3>
                    </div>
                    <div class="card-body-main p-0 position-relative" style="height: 200px;">
                        <canvas id="alliance-wl-chart"></canvas>
                    </div>
                </div>

                <!-- Card: Roster -->
                 <div class="structure-card" style="grid-column: 1 / -1;">
                    <div class="card-header-main">
                        <span class="card-icon"><i class="fas fa-id-card"></i></span>
                        <div class="card-title-group">
                            <h4>Active Roster</h4>
                        </div>
                    </div>
                    <div class="card-body-main p-0">
                         <div class="table-responsive w-100">
                            <table class="table table-dark table-hover mb-0 align-middle" style="background: transparent;">
                                <thead class="small text-muted text-uppercase">
                                    <tr>
                                        <th class="ps-4 py-3">Name</th>
                                        <th class="py-3">Role</th>
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

<!-- Scripts (v6 for cache busting) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/js/almanac.js?v=6"></script>

<style>
/* Autocomplete Results (Live Search) */
.autocomplete-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: rgba(13, 17, 23, 0.95);
    border: 1px solid var(--border);
    border-top: none;
    border-radius: 0 0 8px 8px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.5);
}

.autocomplete-results.active {
    display: block;
}

.autocomplete-results.border-neon-blue { border-color: #00f3ff; }
.autocomplete-results.border-warning { border-color: #ffc107; }

.result-item {
    padding: 12px 16px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: background 0.2s;
}

.result-item:last-child { border-bottom: none; }

.result-item:hover {
    background: rgba(255,255,255,0.1);
}

.result-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid var(--border);
}

.result-info {
    flex: 1;
}

.result-name {
    display: block;
    font-weight: 600;
    color: #fff;
    font-size: 0.95rem;
}

.result-meta {
    display: block;
    font-size: 0.75rem;
    color: var(--muted);
}

/* Spinner */
.spinner-sm {
    width: 1rem;
    height: 1rem;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* Scrollbar for options */
.autocomplete-results::-webkit-scrollbar { width: 6px; }
.autocomplete-results::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); }
.autocomplete-results::-webkit-scrollbar-thumb { background: #444; border-radius: 3px; }
.autocomplete-results.border-neon-blue::-webkit-scrollbar-thumb { background: #00f3ff; }
.autocomplete-results.border-warning::-webkit-scrollbar-thumb { background: #ffc107; }
</style>