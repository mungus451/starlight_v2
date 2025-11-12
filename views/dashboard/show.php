<style>
    /* Add some dashboard-specific styles */
    .dashboard-grid {
        text-align: left;
        width: 100%;
        max-width: 1200px; /* Widen container for multi-column */
        
        /* --- NEW: CSS Grid Layout --- */
        display: grid;
        grid-template-columns: repeat(3, 1fr); /* 3-column layout */
        gap: 1.5rem;
    }
    
    /* --- NEW: Responsive Grid --- */
    @media (max-width: 1024px) {
        .dashboard-grid {
            grid-template-columns: repeat(2, 1fr); /* 2-column layout for tablets */
        }
    }
    
    @media (max-width: 768px) {
        .dashboard-grid {
            grid-template-columns: 1fr; /* 1-column layout for mobile */
        }
    }

    /* --- NEW: Grid Span Utilities --- */
    .grid-span-1 { grid-column: span 1; }
    .grid-span-2 { grid-column: span 2; }
    .grid-span-3 { grid-column: span 3; }

    @media (max-width: 1024px) {
        .md-grid-span-1 { grid-column: span 1; }
        .md-grid-span-2 { grid-column: span 2; }
    }
    
    @media (max-width: 768px) {
        /* On mobile, force all to span 1 column */
        .sm-grid-span-1 { grid-column: span 1; }
        .grid-span-1, .grid-span-2, .grid-span-3,
        .md-grid-span-1, .md-grid-span-2 {
            grid-column: span 1;
        }
    }


    .welcome-header {
        text-align: center;
        color: #c0c0e0;
        font-size: 1.2rem;
        margin-top: -1rem;
        margin-bottom: 0.5rem; /* Reduced bottom margin */
    }
    
    .data-card {
        background: #2a2a4a;
        border: 1px solid #3a3a5a;
        border-radius: 8px;
        padding: 1rem 1.5rem;
        margin-bottom: 0; /* Grid gap now handles spacing */
        display: flex; /* Added for vertical flex */
        flex-direction: column; /* Added for vertical flex */
    }
    .data-card h1 {
        margin-bottom: 0.5rem;
    }
    .data-card h3 {
        color: #f9c74f;
        margin-top: 0;
        margin-bottom: 1rem;
        border-bottom: 1px solid #3a3a5a;
        padding-bottom: 0.75rem;
    }
    
    /* --- UPDATED: List Styles --- */
    .data-card ul {
        list-style: none;
        padding-left: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        height: 100%; /* Make list fill card */
    }
    .data-card li {
        font-size: 1rem;
        color: #e0e0e0;
        padding: 0.75rem 0.25rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #3a3a5a;
    }
    .data-card li:last-child {
        border-bottom: none;
        margin-top: auto; /* Push last item down in flex */
    }
    
    /* Key-Value Spans */
    .data-card li span:first-child {
        font-weight: bold;
        color: #c0c0e0;
    }
    .data-card li span:last-child {
        font-weight: bold;
        color: #e0e0e0;
        text-align: right;
    }

    /* --- NEW: Structure Grid (for inside the structures card) --- */
    .structure-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0 1.5rem; /* Only column gap */
    }
    
    @media (max-width: 768px) {
        .structure-grid {
            grid-template-columns: 1fr; /* Stack structures on mobile */
            gap: 0;
        }
    }

    /* --- NEW: Action Link in List --- */
    .data-card li a {
        color: #7683f5;
        font-weight: bold;
        text-decoration: none;
    }
    .data-card li a:hover {
        text-decoration: underline;
    }

    .logout-link {
        display: block;
        width: 100%;
        text-align: center;
        padding: 0.75rem 1.5rem;
        border-radius: 5px;
        text-decoration: none;
        font-weight: bold;
        font-size: 1rem;
        transition: all 0.2s ease;
        background: #e53e3e; /* Red */
        color: white;
        border: none;
        cursor: pointer;
        margin-top: 0; /* Handled by grid gap */
        box-sizing: border-box; /* Ensures padding doesn't break width */
    }
    .logout-link:hover {
        background: #c53030;
    }
</style>

<div class="dashboard-grid">
    <!-- Header Card -->
    <div class="data-card grid-span-3 sm-grid-span-1" style="text-align: center;">
        <h1>Dashboard</h1>
        <p class="welcome-header">Welcome, Commander <?= htmlspecialchars($user->characterName) ?></p>
    </div>

    <!-- Character Card -->
    <div class="data-card grid-span-1 sm-grid-span-1">
        <h3>Character</h3>
        <ul>
            <li><span>Level:</span> <span><?= $stats->level ?></span></li>
            <li><span>Net Worth:</span> <span><?= number_format($stats->net_worth) ?></span></li>
            <li><span>Experience:</span> <span><?= number_format($stats->experience) ?></span></li>
            <li><span>War Prestige:</span> <span><?= number_format($stats->war_prestige) ?></span></li>
        </ul>
    </div>

    <!-- Resources Card -->
    <div class="data-card grid-span-1 sm-grid-span-1">
        <h3>Resources</h3>
        <ul>
            <li><span>Credits:</span> <span><?= number_format($resources->credits) ?></span></li>
            <li><span>Gemstones:</span> <span><?= number_format($resources->gemstones) ?></span></li>
            <li><span>Citizens:</span> <span><?= number_format($resources->untrained_citizens) ?></span></li>
            <li><span>Workers:</span> <span><?= number_format($resources->workers) ?></span></li>
        </ul>
    </div>

    <!-- Military Card -->
    <div class="data-card grid-span-1 sm-grid-span-1">
        <h3>Military</h3>
        <ul>
            <li><span>Soldiers:</span> <span><?= number_format($resources->soldiers) ?></span></li>
            <li><span>Guards:</span> <span><?= number_format($resources->guards) ?></span></li>
            <li><span>Spies:</span> <span><?= number_format($resources->spies) ?></span></li>
            <li><span>Sentries:</span> <span><?= number_format($resources->sentries) ?></span></li>
        </ul>
    </div>
    
    <!-- Actions Card -->
    <div class="data-card grid-span-1 sm-grid-span-1">
        <h3>Actions</h3>
        <ul>
            <li><span>Attack Turns:</span> <span><?= number_format($stats->attack_turns) ?></span></li>
            <li>
                <span>Level Up Points:</span> 
                <span>
                    <?= number_format($stats->level_up_points) ?> 
                    <?php if ($stats->level_up_points > 0): ?>
                        <a href="/level-up">(Spend)</a>
                    <?php endif; ?>
                </span>
            </li>
        </ul>
    </div>

    <!-- Alliance Card -->
    <div class="data-card grid-span-2 md-grid-span-1 sm-grid-span-1">
        <h3>Alliance</h3>
        <ul>
            <?php if ($user->alliance_id !== null): ?>
                <li>
                    <span>Status:</span>
                    <span><a href="/alliance/profile/<?= $user->alliance_id ?>">View Alliance</a></span>
                </li>
            <?php else: ?>
                <li>
                    <span>Status:</span>
                    <span><a href="/alliance/list">Find an Alliance</a></span>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Structures Card -->
    <div class="data-card grid-span-3 sm-grid-span-1">
        <h3>Structures</h3>
        <ul class="structure-grid">
            <li><span>Fortification:</span> <span>Level <?= $structures->fortification_level ?></span></li>
            <li><span>Spy Upgrade:</span> <span>Level <?= $structures->spy_upgrade_level ?></span></li>
            <li><span>Offense Upgrade:</span> <span>Level <?= $structures->offense_upgrade_level ?></span></li>
            <li><span>Economy Upgrade:</span> <span>Level <?= $structures->economy_upgrade_level ?></span></li>
            <li><span>Defense Upgrade:</span> <span>Level <?= $structures->defense_upgrade_level ?></span></li>
            <li><span>Population:</span> <span>Level <?= $structures->population_level ?></span></li>
            <li class="grid-span-1"><span>Armory:</span> <span>Level <?= $structures->armory_level ?></span></li>
        </ul>
    </div>

    <!-- Logout Link -->
    <a href="/logout" class="logout-link grid-span-3 sm-grid-span-1">Logout</a>
</div>