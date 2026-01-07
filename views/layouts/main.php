<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token ?? '') ?>">

    <title><?= $title ?? 'StarlightDominion' ?></title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Operation Starlight CSS -->
    <link rel="stylesheet" href="/css/starlight.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/css/starlight-advisor-v2.css?v=<?= time() ?>">
</head>
<body>

<?php if ($this->session->get('is_mobile')): ?>

    <!-- ======================= MOBILE NAVIGATION ======================= -->
    <header class="mobile-header">
        <a href="/dashboard" class="logo" style="text-decoration: none;">Starlight</a>
        <div class="hamburger-menu">
            <i class="fas fa-bars"></i>
        </div>
    </header>
    <nav class="mobile-nav">
        <div class="mobile-nav-header">
            <div class="logo">Navigation</div>
            <div class="close-btn">&times;</div>
        </div>
        <ul>
            <li><a href="/dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>

            <li class="has-submenu">
                <a href="#"><i class="fas fa-city"></i> Empire <i class="fas fa-chevron-down submenu-indicator"></i></a>
                <ul class="submenu">
                    <li><a href="/structures"><i class="fas fa-industry"></i> Structures</a></li>
                    <li><a href="/embassy"><i class="fas fa-landmark"></i> Embassy</a></li>
                    <li><a href="/level-up"><i class="fas fa-bolt"></i> Level Up</a></li>
                    <li><a href="/leaderboard"><i class="fas fa-trophy"></i> Leaderboard</a></li>
                    <li><a href="/almanac"><i class="fas fa-book"></i> Almanac</a></li>
                </ul>
            </li>

            <li class="has-submenu">
                <a href="#"><i class="fas fa-coins"></i> Economy <i class="fas fa-chevron-down submenu-indicator"></i></a>
                <ul class="submenu">
                    <li><a href="/bank"><i class="fas fa-university"></i> Bank</a></li>
                    <li><a href="/black-market/converter"><i class="fas fa-exchange-alt"></i> Black Market</a></li>
                </ul>
            </li>

            <li class="has-submenu">
                <a href="#"><i class="fas fa-crosshairs"></i> Military <i class="fas fa-chevron-down submenu-indicator"></i></a>
                <ul class="submenu">
                    <!-- <li><a href="/generals"><i class="fas fa-user-tie"></i> Elite Units</a></li> -->
                    <li><a href="/training"><i class="fas fa-users"></i> Training</a></li>
                    <li><a href="/armory"><i class="fas fa-shield-alt"></i> Armory</a></li>
                    <li><a href="/spy"><i class="fas fa-user-secret"></i> Spy Network</a></li>
                    <li><a href="/battle"><i class="fas fa-fighter-jet"></i> Battle Control</a></li>
                </ul>
            </li>

            <li class="has-submenu">
                <a href="#"><i class="fas fa-flag"></i> Alliance <i class="fas fa-chevron-down submenu-indicator"></i></a>
                <ul class="submenu">
                    <li><a href="/alliance/list"><i class="fas fa-list"></i> Alliance List</a></li>
                    <?php if ($currentUserAllianceId !== null): ?>
                        <li><a href="/alliance/profile/<?= $currentUserAllianceId ?>"><i class="fas fa-home"></i> My Alliance</a></li>
                        <li><a href="/alliance/forum"><i class="fas fa-comments"></i> Forum</a></li>
                        <li><a href="/alliance/structures"><i class="fas fa-building"></i> Structures</a></li>
                        <li><a href="/alliance/diplomacy"><i class="fas fa-handshake"></i> Diplomacy</a></li>
                        <li><a href="/alliance/war"><i class="fas fa-skull"></i> War Room</a></li>
                    <?php endif; ?>
                </ul>
            </li>
            
            <li><a href="/notifications"><i class="fas fa-bell"></i> Notifications <span id="nav-notification-badge-mobile" class="nav-badge" style="position: relative; top: 0; right: 0; margin-left: 5px;"></span></a></li>
            <li><a href="/glossary"><i class="fas fa-book-open"></i> Game Glossary</a></li>
            <li><a href="/settings"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="/logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

<?php else: ?>

    <!-- ======================= DESKTOP NAVIGATION ======================= -->
    <nav class="main-nav">
        <!-- Logo -->
        <a href="/" class="nav-brand">
            <i class="fas fa-meteor"></i> Starlight
        </a>

        <?php if ($isLoggedIn): ?>
            <!-- Left Side: Main Navigation -->
            <ul class="nav-list">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="/dashboard" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a>
                </li>

                <!-- Structures -->
                <li class="nav-item">
                    <a href="/structures" class="nav-link"><i class="fas fa-industry"></i> Structures <span class="nav-queue-badge" id="nav-queue-structures"></span></a>
                </li>
                
                <!-- Research -->
                <li class="nav-item">
                    <a href="/research" class="nav-link"><i class="fas fa-flask"></i> Research <span class="nav-queue-badge" id="nav-queue-research"></span></a>
                </li>

                <!-- Economy Dropdown -->
                <li class="nav-item">
                    <span class="nav-link"><i class="fas fa-coins"></i> Economy <i class="fas fa-caret-down" style="margin-left: 5px; font-size: 0.8em; opacity: 0.7;"></i></span>
                    <ul class="nav-submenu">
                        <li><a href="/bank"><i class="fas fa-university"></i> Bank</a></li>
                        <li><a href="/black-market/converter"><i class="fas fa-exchange-alt"></i> Black Market</a></li>
                    </ul>
                </li>

                <!-- Military Dropdown -->
                <li class="nav-item">
                    <span class="nav-link"><i class="fas fa-crosshairs"></i> Military <span class="nav-queue-badge" id="nav-queue-military"></span> <i class="fas fa-caret-down" style="margin-left: 5px; font-size: 0.8em; opacity: 0.7;"></i></span>
                    <ul class="nav-submenu">
                        <!-- <li><a href="/generals"><i class="fas fa-user-tie"></i> Elite Units</a></li> -->
                        <li><a href="/training"><i class="fas fa-users"></i> Training</a></li>
                        <li><a href="/armory"><i class="fas fa-shield-alt"></i> Armory</a></li>
                        <li><a href="/battle"><i class="fas fa-fighter-jet"></i> Battle Control</a></li>
                    </ul>
                </li>

                <!-- Alliance Dropdown -->
                <li class="nav-item">
                    <span class="nav-link"><i class="fas fa-flag"></i> Alliance <i class="fas fa-caret-down" style="margin-left: 5px; font-size: 0.8em; opacity: 0.7;"></i></span>
                    <ul class="nav-submenu">
                        <li><a href="/alliance/list"><i class="fas fa-list"></i> Alliance List</a></li>
                        <?php if ($currentUserAllianceId !== null): ?>
                            <li class="submenu-divider"></li>
                            <li><a href="/alliance/profile/<?= $currentUserAllianceId ?>"><i class="fas fa-home"></i> My Alliance</a></li>
                            <li><a href="/alliance/forum"><i class="fas fa-comments"></i> Forum</a></li>
                            <li><a href="/alliance/structures"><i class="fas fa-building"></i> Structures</a></li>
                            <li><a href="/alliance/diplomacy"><i class="fas fa-handshake"></i> Diplomacy</a></li>
                            <li><a href="/alliance/war"><i class="fas fa-skull"></i> War Room</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
            </ul>

            <!-- Right Side: Account & Notifs -->
            <ul class="nav-list" style="margin-left: auto;">
                <li class="nav-item">
                    <a href="/glossary" class="nav-icon-link" title="Game Glossary">
                        <i class="fas fa-book-open"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/notifications" class="nav-icon-link position-relative" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <span id="nav-notification-badge" class="nav-badge"></span>
                    </a>
                </li>
                <li class="nav-item">
                    <span class="nav-link"><i class="fas fa-user-astronaut"></i> Account</span>
                    <ul class="nav-submenu" style="left: auto; right: 0; border-radius: 12px 0 12px 12px;">
                        <li><a href="/settings"><i class="fas fa-cog"></i> Settings</a></li>
                        <li class="submenu-divider"></li>
                        <li><a href="/logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>

            <?php if (isset($global_xp_data) && isset($global_user_level)): ?>
                <div class="xp-bar-container">
                    <div class="xp-bar-fill" style="width: <?= $global_xp_data['percent'] ?>%;"></div>
                    <div class="xp-level-badge">
                        Lvl <?= $global_user_level ?>
                        <span class="xp-details">
                            <?= number_format($global_xp_data['current_xp']) ?> / <?= number_format($global_xp_data['next_level_xp']) ?> XP
                        </span>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- Guest View -->
            <ul class="nav-list" style="margin-left: auto;">
                <li class="nav-item"><a href="/" class="nav-link">Home</a></li>
                <li class="nav-item"><a href="/contact" class="nav-link">Contact</a></li>
                <li class="nav-item"><a href="https://discord.gg/sCKvuxHAqt" target="_blank" class="nav-link">Discord</a></li>
                <li class="nav-item"><a href="/login" class="nav-link">Login</a></li>
                <li class="nav-item"><a href="/register" class="nav-link" style="color: var(--accent);">Register</a></li>
            </ul>
        <?php endif; ?>
    </nav>
<?php endif; ?>

<div class="advisor-layout-grid">
    <div class="advisor-main-content">
        <?php 
        if (isset($layoutMode) && $layoutMode === 'full'): 
        ?>
            <div class="container-full">
        <?php else: ?>
            <div class="container">
        <?php endif; ?>
        
            <?php if ($flashError): ?>
                <div class="flash flash-error"><?= htmlspecialchars($flashError) ?></div>
            <?php endif; ?>
            
            <?php if ($flashSuccess): ?>
                <div class="flash flash-success"><?= htmlspecialchars($flashSuccess) ?></div>
            <?php endif; ?>

            <?= $content ?>
        </div>
    </div>

    <?php if (!$this->session->get('is_mobile') && $isLoggedIn && isset($advisorData)): ?>
        <aside class="advisor-panel">
            <!-- Advisor Header -->
            <div class="advisor-header">
                <?php if ($advisorData['user']->profile_picture_url): ?>
                    <img src="/serve/avatar/<?= htmlspecialchars($advisorData['user']->profile_picture_url) ?>" alt="Avatar" class="advisor-avatar">
                <?php else: ?>
                    <svg class="advisor-avatar" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                <?php endif; ?>
                <div class="advisor-player-info">
                    <h3><?= htmlspecialchars($advisorData['user']->characterName) ?></h3>
                    <span class="advisor-player-level">Level <?= $advisorData['stats']->level ?></span>
                </div>
            </div>

            <!-- Core Stats -->
            <div class="advisor-core-stats">
                <div class="advisor-stat">
                    <span class="advisor-stat-label">Credits</span>
                    <span class="advisor-stat-value"><?= number_format($advisorData['resources']->credits) ?></span>
                </div>
                <div class="advisor-stat">
                    <span class="advisor-stat-label">Attack Turns</span>
                    <span class="advisor-stat-value accent"><?= number_format($advisorData['stats']->attack_turns) ?></span>
                </div>
                <div class="advisor-stat">
                    <span class="advisor-stat-label">Server Time</span>
                    <span class="advisor-stat-value" id="advisor-clock">--:--:--</span>
                </div>
            </div>

            <!-- Pod: Resources -->
            <div class="advisor-pod">
                <div class="advisor-pod-header" data-pod-id="resources">
                    <h4><i class="fas fa-coins" style="margin-right: 8px;"></i> Resources</h4>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </div>
                <div class="advisor-pod-content">
                    <div class="advisor-stat">
                        <span class="advisor-stat-label">Citizens</span>
                        <span class="advisor-stat-value"><?= number_format($advisorData['resources']->untrained_citizens) ?></span>
                    </div>
                    <div class="advisor-stat">
                        <span class="advisor-stat-label">Workers</span>
                        <span class="advisor-stat-value"><?= number_format($advisorData['resources']->workers) ?></span>
                    </div>
                    <div class="advisor-stat">
                        <span class="advisor-stat-label">Research Data</span>
                        <span class="advisor-stat-value"><?= number_format($advisorData['resources']->research_data) ?></span>
                    </div>
                </div>
            </div>

            <!-- Pod: Military -->
            <div class="advisor-pod">
                <div class="advisor-pod-header" data-pod-id="military">
                    <h4><i class="fas fa-crosshairs" style="margin-right: 8px;"></i> Military</h4>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </div>
                <div class="advisor-pod-content">
                    <div class="advisor-stat">
                        <span class="advisor-stat-label">Offense</span>
                        <span class="advisor-stat-value"><?= number_format($advisorData['offenseBreakdown']['total']) ?></span>
                    </div>
                    <div class="advisor-stat">
                        <span class="advisor-stat-label">Defense</span>
                        <span class="advisor-stat-value"><?= number_format($advisorData['defenseBreakdown']['total']) ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Pod: Quick Links -->
            <div class="advisor-pod">
                <div class="advisor-pod-header" data-pod-id="quick-links">
                    <h4><i class="fas fa-link" style="margin-right: 8px;"></i> Quick Links</h4>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </div>
                <div class="advisor-pod-content advisor-quick-links">
                    <a href="/structures" class="advisor-quick-link" title="Structures"><i class="fas fa-industry"></i></a>
                    <a href="/training" class="advisor-quick-link" title="Training"><i class="fas fa-users"></i></a>
                    <a href="/armory" class="advisor-quick-link" title="Armory"><i class="fas fa-shield-alt"></i></a>
                    <a href="/bank" class="advisor-quick-link" title="Bank"><i class="fas fa-university"></i></a>
                </div>
            </div>

            <!-- Pod: Realm News -->
            <div class="advisor-pod">
                <div class="advisor-pod-header" data-pod-id="realm-news">
                    <h4><i class="fas fa-newspaper" style="margin-right: 8px;"></i> Realm News</h4>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </div>
                <div class="advisor-pod-content">
                    <?php if (isset($realmNews) && $realmNews): ?>
                        <h5 class="realm-news-title"><?= htmlspecialchars($realmNews->title) ?></h5>
                        <p class="realm-news-content"><?= nl2br(htmlspecialchars($realmNews->content)) ?></p>
                        <small class="realm-news-date">Posted: <?= date('M j, Y', strtotime($realmNews->created_at)) ?></small>
                    <?php endif; ?>

                    <?php if (isset($latestBattles) && !empty($latestBattles)): ?>
                        <h5 class="realm-news-title" style="margin-top: 1.5rem;"><i class="fas fa-skull-crossbones" style="margin-right: 8px;"></i> Recent Battles</h5>
                        <ul class="advisor-battle-list">
                            <?php foreach ($latestBattles as $battle): ?>
                                <li>
                                    <a href="/battle/report/<?= $battle->id ?>" class="advisor-battle-link">
                                        <span class="battle-attacker"><?= htmlspecialchars($battle->attacker_name) ?></span>
                                        <span class="battle-result <?= $battle->attack_result === 'win' ? 'text-success' : 'text-danger' ?>"><?= ucfirst($battle->attack_result) ?></span>
                                        <span class="battle-defender"><?= htmlspecialchars($battle->defender_name) ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p style="margin-top: 1.5rem;">No recent battles to display.</p>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    <?php endif; ?>
</div>

<script src="/js/utils.js"></script>

<?php if ($this->session->get('is_mobile') && $isLoggedIn): ?>
    
    <!-- Mobile-Specific Dashboard & Nav JS -->
    <script src="/js/notifications.js"></script>
    <script src="/js/mobile_dashboard.js?v=<?= time(); ?>"></script>

<?php elseif ($isLoggedIn): ?>

    <!-- Desktop-Specific JS -->
    <script src="/js/notifications.js"></script>
    <script src="/js/advisor.js?v=<?= time(); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
<?php endif; ?>

</body>
</html>
