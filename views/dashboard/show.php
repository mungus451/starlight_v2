<style>
    /* Add some dashboard-specific styles */
    .dashboard-container {
        text-align: left;
        width: 100%;
    }
    .dashboard-container h1 {
        text-align: center;
    }
    .welcome-header {
        text-align: center;
        color: #c0c0e0;
        font-size: 1.2rem;
        margin-top: -1rem;
        margin-bottom: 2rem;
    }
    .data-card {
        background: #2a2a4a;
        border: 1px solid #3a3a5a;
        border-radius: 8px;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
    }
    .data-card h3 {
        color: #f9c74f;
        margin-top: 0;
        border-bottom: 1px solid #3a3a5a;
        padding-bottom: 0.5rem;
    }
    .data-card ul {
        list-style: none;
        padding-left: 0;
        margin: 0;
    }
    .data-card li {
        font-size: 1rem;
        color: #e0e0e0;
        padding: 0.25rem 0;
        display: flex;
        justify-content: space-between;
    }
    .data-card li span:first-child {
        font-weight: bold;
        color: #c0c0e0;
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
        margin-top: 1rem;
        box-sizing: border-box; /* Ensures padding doesn't break width */
    }
    .logout-link:hover {
        background: #c53030;
    }
</style>

<div class="dashboard-container">
    <h1>Dashboard</h1>
    <p class="welcome-header">Welcome, <?= htmlspecialchars($user->characterName) ?></p>

    <div class="data-card">
        <h3>Resources</h3>
        <ul>
            <li><span>Credits:</span> <span><?= number_format($resources->credits) ?></span></li>
            <li><span>Gemstones:</span> <span><?= number_format($resources->gemstones) ?></span></li>
            <li><span>Citizens:</span> <span><?= number_format($resources->untrained_citizens) ?></span></li>
            <li><span>Workers:</span> <span><?= number_format($resources->workers) ?></span></li>
            <li><span>Soldiers:</span> <span><?= number_format($resources->soldiers) ?></span></li>
            <li><span>Guards:</span> <span><?= number_format($resources->guards) ?></span></li>
        </ul>
    </div>

    <div class="data-card">
        <h3>Stats</h3>
        <ul>
            <li><span>Level:</span> <span><?= $stats->level ?></span></li>
            <li><span>Net Worth:</span> <span><?= number_format($stats->net_worth) ?></span></li>
            <li><span>Attack Turns:</span> <span><?= $stats->attack_turns ?></span></li>
            <li><span>Level Up Points:</span> <span><?= $stats->level_up_points ?></span></li>
        </ul>
    </div>

    <div class="data-card">
        <h3>Structures</h3>
        <ul>
            <li><span>Fortification:</span> <span>Level <?= $structures->fortification_level ?></span></li>
            <li><span>Offense Upgrade:</span> <span>Level <?= $structures->offense_upgrade_level ?></span></li>
            <li><span>Defense Upgrade:</span> <span>Level <?= $structures->defense_upgrade_level ?></span></li>
            <li><span>Economy Upgrade:</span> <span>Level <?= $structures->economy_upgrade_level ?></span></li>
        </ul>
    </div>

    <a href="/logout" class="logout-link">Logout</a>
</div>