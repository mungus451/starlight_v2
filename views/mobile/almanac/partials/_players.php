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
    <div>
        <!-- Combat Stats -->
        <div class="mobile-card">
            <div class="mobile-card-header"><h3><i class="fas fa-fist-raised"></i> Combat Records</h3></div>
            <div class="mobile-card-content">
                <ul class="mobile-stats-list">
                    <li><span>Max Plunder</span> <strong id="record-plunder">0</strong></li>
                    <li><span>Deadliest Hit</span> <strong id="record-deadliest">0</strong></li>
                    <li><span>Total Battles</span> <strong id="stat-total-battles">0</strong></li>
                    <li><span>Victories</span> <strong id="stat-wins" class="value-green">0</strong></li>
                    <li><span>Defeats</span> <strong id="stat-losses" class="value-red">0</strong></li>
                </ul>
            </div>
        </div>
        <div class="mobile-card">
            <div class="mobile-card-header"><h3><i class="fas fa-chart-pie"></i> Win/Loss Ratio</h3></div>
            <div class="mobile-card-content" style="height: 250px;"><canvas id="player-wl-chart"></canvas></div>
        </div>
        <div class="mobile-card">
            <div class="mobile-card-header"><h3><i class="fas fa-skull-crossbones"></i> Casualty Records</h3></div>
            <div class="mobile-card-content">
                <ul class="mobile-stats-list">
                    <li><span>Units Killed</span> <strong id="stat-killed" class="value-green">0</strong></li>
                    <li><span>Units Lost</span> <strong id="stat-lost" class="value-red">0</strong></li>
                    <li><span>Units Lost (Def)</span> <strong id="stat-lost-defensive" class="value-red">0</strong></li>
                </ul>
            </div>
        </div>
        <div class="mobile-card">
            <div class="mobile-card-header"><h3><i class="fas fa-chart-pie"></i> K/D Ratio</h3></div>
            <div class="mobile-card-content" style="height: 250px;"><canvas id="player-kd-chart"></canvas></div>
        </div>
        <div class="mobile-card">
            <div class="mobile-card-header"><h3><i class="fas fa-chart-bar"></i> Casualty Analysis</h3></div>
            <div class="mobile-card-content" style="height: 250px;"><canvas id="player-casualty-chart"></canvas></div>
        </div>
        
        <!-- Espionage Stats -->
        <h3 class="mobile-category-header" style="margin-top: 2rem;">Intelligence Directorate</h3>
        <div class="mobile-card">
            <div class="mobile-card-header"><h3><i class="fas fa-file-contract"></i> Espionage Operations</h3></div>
            <div class="mobile-card-content">
                <ul class="mobile-stats-list">
                    <li><span>Missions Launched</span> <strong id="spy-missions-total">0</strong></li>
                    <li><span>Successful Infiltrations</span> <strong id="spy-missions-success" class="value-green">0</strong></li>
                    <li><span>Spies Lost (M.I.A.)</span> <strong id="spy-lost" class="value-red">0</strong></li>
                    <li><span>Enemy Sentries Neutralized</span> <strong id="sentry-killed" class="value-green">0</strong></li>
                    <li class="mt-2" style="border-top: 1px dotted rgba(255,255,255,0.1); padding-top: 1rem;"><span>Defensive Interceptions</span> <strong id="spy-intercepted" class="value-blue">0</strong></li>
                    <li><span>Enemy Spies Caught</span> <strong id="enemy-spy-caught" class="value-green">0</strong></li>
                    <li><span>Sentries Lost (K.I.A.)</span> <strong id="sentry-lost" class="value-red">0</strong></li>
                </ul>
            </div>
        </div>
        <div class="mobile-card">
            <div class="mobile-card-header"><h3><i class="fas fa-chart-pie"></i> Mission Success Rate</h3></div>
            <div class="mobile-card-content" style="height: 250px;"><canvas id="player-spy-chart"></canvas></div>
        </div>
        <div class="mobile-card">
            <div class="mobile-card-header"><h3><i class="fas fa-chart-pie"></i> Intel Efficiency (K/D)</h3></div>
            <div class="mobile-card-content" style="height: 250px;"><canvas id="player-spy-kd-chart"></canvas></div>
        </div>
    </div>
</div>
