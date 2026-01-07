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
        
        <!-- Search Console (Trigger) -->
        <div class="structure-card mb-4" style="border-color: var(--accent-blue);">
            <div class="card-body-main p-3">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <label class="text-neon-blue text-uppercase fw-bold small mb-2 d-block">
                            <i class="fas fa-search me-1"></i> Access Personnel Records
                        </label>
                        
                        <div class="input-group" style="cursor: pointer;" onclick="openSearchModal('players')">
                            <span class="input-group-text bg-dark border-secondary text-neon-blue">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control bg-dark border-secondary text-light" placeholder="Tap to search commanders..." readonly style="pointer-events: none;">
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
         
         <!-- Alliance Search (Trigger) -->
        <div class="structure-card mb-4" style="border-color: var(--accent-2);">
            <div class="card-body-main p-3">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <label class="text-warning text-uppercase fw-bold small mb-2 d-block">
                            <i class="fas fa-search me-1"></i> Access Faction Database
                        </label>
                        
                        <div class="input-group" style="cursor: pointer;" onclick="openSearchModal('alliances')">
                            <span class="input-group-text bg-dark border-secondary text-warning">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control bg-dark border-secondary text-light" placeholder="Tap to search factions..." readonly style="pointer-events: none;">
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

<!-- SEARCH MODAL -->
<div id="almanac-search-modal" class="search-modal-overlay" style="display: none;">
    <div class="search-modal-container">
        <div class="search-modal-header">
            <h3 id="search-modal-title">Search Database</h3>
            <button class="btn-close-modal" onclick="closeSearchModal()">&times;</button>
        </div>
        <div class="search-modal-body">
            <div class="input-group mb-3">
                <span class="input-group-text bg-black border-secondary text-muted">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" id="modal-search-input" class="form-control bg-black border-secondary text-light fs-5" placeholder="Type to search..." autocomplete="off">
            </div>
            
            <div id="modal-search-results" class="modal-results-list">
                <!-- Results injected here -->
            </div>
        </div>
    </div>
</div>

<!-- Scripts (v7 for cache busting) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/js/almanac.js?v=7"></script>

<style>
/* Search Modal Styles */
.search-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(8px);
    z-index: 9999;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding-top: 100px;
    animation: fadeIn 0.2s ease-out;
}

.search-modal-container {
    background: rgba(13, 17, 23, 0.95);
    border: 1px solid var(--accent);
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 0 30px rgba(0, 243, 255, 0.2);
    display: flex;
    flex-direction: column;
    max-height: 80vh;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from { transform: translateY(-20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.search-modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.search-modal-header h3 {
    margin: 0;
    font-family: 'Orbitron', sans-serif;
    color: var(--accent);
    text-transform: uppercase;
}

.btn-close-modal {
    background: none;
    border: none;
    color: var(--muted);
    font-size: 2rem;
    cursor: pointer;
    line-height: 1;
}
.btn-close-modal:hover { color: #fff; }

.search-modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.modal-results-list {
    flex: 1;
    overflow-y: auto;
    min-height: 100px;
}

/* Reusing Result Item Styles */
.result-item {
    padding: 15px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: background 0.2s;
    border-radius: 8px;
}
.result-item:hover { background: rgba(255,255,255,0.1); }

.result-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid var(--border);
}

.result-name { font-size: 1.1rem; }
.result-meta { font-size: 0.85rem; }

/* Scrollbar */
.modal-results-list::-webkit-scrollbar { width: 6px; }
.modal-results-list::-webkit-scrollbar-track { background: transparent; }
.modal-results-list::-webkit-scrollbar-thumb { background: #444; border-radius: 3px; }
</style>
