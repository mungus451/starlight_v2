<style>
    :root {
        --bg: radial-gradient(circle at 10% 0%, #0c101e 0%, #050712 42%, #02030a 75%);
        --panel: rgba(12, 14, 25, 0.68);
        --card: radial-gradient(circle at 30% -10%, rgba(45, 209, 209, 0.08), rgba(13, 15, 27, 0.75));
        --border: rgba(255, 255, 255, 0.035);
        --accent: #2dd1d1;
        --text: #eff1ff;
        --muted: #a8afd4;
        --radius: 18px;
        --shadow: 0 14px 35px rgba(0, 0, 0, 0.4);
    }

    .dashboard-grid {
        text-align: left;
        width: 100%;
        max-width: 1200px;
        margin: 0 auto; /* hard-center */
        padding: 1.5rem 1.5rem 3.5rem;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
        position: relative;
        box-sizing: border-box;
    }

    /* center the overlay too so it doesn't "pull" to the right */
    .dashboard-grid::before {
        content: "";
        position: absolute;
        inset: -80px 0 0 0; /* no negative horizontal stretch */
        background-image:
            linear-gradient(90deg, rgba(255,255,255,0.018) 1px, transparent 0),
            linear-gradient(0deg, rgba(255,255,255,0.018) 1px, transparent 0);
        background-size: 120px 120px;
        pointer-events: none;
        z-index: -1;
    }

    @media (max-width: 1024px) {
        .dashboard-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
    }

    .grid-span-1 { grid-column: span 1; }
    .grid-span-2 { grid-column: span 2; }
    .grid-span-3 { grid-column: span 3; }

    @media (max-width: 1024px) {
        .md-grid-span-1 { grid-column: span 1; }
        .md-grid-span-2 { grid-column: span 2; }
    }

    @media (max-width: 768px) {
        .sm-grid-span-1 { grid-column: span 1; }
        .grid-span-1, .grid-span-2, .grid-span-3,
        .md-grid-span-1, .md-grid-span-2 {
            grid-column: span 1;
        }
    }

    .welcome-header {
        text-align: center;
        color: var(--muted);
        font-size: 1rem;
        margin-top: -0.25rem;
        margin-bottom: 0.35rem;
    }

    .data-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.05rem 1.25rem 1.15rem;
        margin-bottom: 0;
        display: flex;
        flex-direction: column;
        backdrop-filter: blur(6px);
        box-shadow: var(--shadow);
        transition: transform 0.1s ease-out, border 0.1s ease-out;
    }
    .data-card:hover {
        transform: translateY(-1px);
        border: 1px solid rgba(45, 209, 209, 0.25);
    }

    .data-card h1 {
        margin-bottom: 0.25rem;
        color: #fff;
        letter-spacing: -0.03em;
    }
    .data-card h3 {
        color: #fff;
        margin-top: 0;
        margin-bottom: 0.85rem;
        border-bottom: 1px solid rgba(233, 219, 255, 0.03);
        padding-bottom: 0.5rem;
        font-size: 0.9rem;
        letter-spacing: 0.02em;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .data-card h3::before {
        content: "";
        width: 4px;
        height: 16px;
        border-radius: 999px;
        background: linear-gradient(180deg, #2dd1d1, rgba(2, 3, 10, 0));
        box-shadow: 0 0 20px rgba(45, 209, 209, 0.35);
    }

    .data-card ul {
        list-style: none;
        padding-left: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        height: 100%;
        gap: 0.35rem;
    }
    .data-card li {
        font-size: 0.9rem;
        color: #e0e0e0;
        padding: 0.55rem 0.25rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(58, 58, 90, 0.08);
        gap: 1rem;
    }
    .data-card li:last-child {
        border-bottom: none;
        margin-top: auto;
    }

    .data-card li span:first-child {
        font-weight: 500;
        color: rgba(239, 241, 255, 0.7);
        font-size: 0.85rem;
    }
    .data-card li span:last-child {
        font-weight: 600;
        color: #fff;
        text-align: right;
        font-size: 0.85rem;
    }

    .data-card li a {
        color: #2dd1d1;
        font-weight: 600;
        text-decoration: none;
        font-size: 0.78rem;
    }
    .data-card li a:hover {
        text-decoration: underline;
    }

    .structure-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0 1.5rem;
    }
    .structure-grid li {
        border-bottom: none;
        padding: 0.3rem 0.25rem;
    }
    @media (max-width: 768px) {
        .structure-grid {
            grid-template-columns: 1fr;
            gap: 0;
        }
    }

    .logout-link {
        display: block;
        width: 100%;
        text-align: center;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.15s ease;
        background: linear-gradient(135deg, rgba(232, 65, 95, 0.9) 0%, rgba(196, 41, 62, 0.9) 100%);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.02);
        cursor: pointer;
        margin-top: 0;
        box-sizing: border-box;
        box-shadow: 0 10px 30px rgba(196, 41, 62, 0.3);
        backdrop-filter: blur(4px);
    }
    .logout-link:hover {
        filter: brightness(1.03);
        transform: translateY(-1px);
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

    <a href="/logout" class="logout-link grid-span-3 sm-grid-span-1">Logout</a>
</div>
