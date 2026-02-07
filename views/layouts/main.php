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
    <link rel="stylesheet" href="/css/main.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/css/starlight.css?v=<?= time() ?>">
    <!-- Additional specific CSS files that were added later -->
    <link rel="stylesheet" href="/css/starlight-advisor-v2.css?v=<?= time() ?>">

    <link rel="stylesheet" href="/css/starlight-command-bridge.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/css/armory-command-bridge.css?v=<?= time() ?>">

</head>
<body class="">

    <!-- ======================= MOBILE TOP BAR (Hidden on Desktop) ======================= -->
    <header class="mobile-top-bar">
        <button class="menu-toggle-btn" id="mobile-menu-toggle">
            <i class="fas fa-bars"></i>
        </button>
        <a href="/dashboard" class="logo">Starlight</a>
        <button class="advisor-toggle-btn" id="mobile-advisor-toggle">
            <i class="fas fa-user-astronaut"></i>
        </button>
    </header>

    <!-- ======================= MAIN NAVIGATION (Responsive) ======================= -->
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

                <li class="nav-item">
                    <span class="nav-link"><i class="fas fa-coins"></i> Economy <i class="fas fa-caret-down submenu-indicator"></i></span>
                    <ul class="nav-submenu">
                        <li><a href="/structures"><i class="fas fa-industry"></i> Structures</a></li>
                        <li><a href="/bank"><i class="fas fa-university"></i> Bank</a></li>
                    </ul>
                </li>

                <!-- Military Dropdown -->
                <li class="nav-item">
                    <span class="nav-link"><i class="fas fa-crosshairs"></i> Military <span class="nav-queue-badge" id="nav-queue-military"></span> <i class="fas fa-caret-down submenu-indicator"></i></span>
                    <ul class="nav-submenu">
                        <!-- <li><a href="/generals"><i class="fas fa-user-tie"></i> Elite Units</a></li> -->
                        <li><a href="/training"><i class="fas fa-users"></i> Training</a></li>
                        <li><a href="/armory"><i class="fas fa-shield-alt"></i> Armory</a></li>
                        <li><a href="/battle"><i class="fas fa-fighter-jet"></i> Battle Control</a></li>
                    </ul>
                </li>

                <!-- Alliance Dropdown -->
                <li class="nav-item">
                    <span class="nav-link"><i class="fas fa-flag"></i> Alliance <i class="fas fa-caret-down submenu-indicator"></i></span>
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
                        <!-- Theme Switcher -->
                        <li>
                            <form action="/theme/switch" method="post" style="padding: 8px 12px;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                <select name="theme" class="form-select bg-dark text-light border-secondary" onchange="this.form.submit()">
                                    <option value="default" <?= ($this->session->get('theme', 'default') === 'default') ? 'selected' : '' ?>>Default Theme</option>
                                    <option value="classic" <?= ($this->session->get('theme', 'default') === 'classic') ? 'selected' : '' ?>>Classic Theme</option>
                                    <option value="simple" <?= ($this->session->get('theme', 'default') === 'simple') ? 'selected' : '' ?>>Simple Theme</option>
                                </select>
                            </form>
                        </li>
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

<div class="advisor-layout-grid">



    <?php if ($isLoggedIn && isset($advisorData)): ?>
        <aside class="lg:col-span-1 space-y-4">
        <?php include 'advisor.php'; ?>
    </aside>
    <?php endif; ?>

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
</div>

<script src="/js/utils.js?v=<?= time() ?>"></script>
<script src="/js/notifications.js"></script>
<script src="/js/advisor.js?v=<?= time(); ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/js/tabs.js?v=<?= time() ?>"></script>




    
</body>
</html>