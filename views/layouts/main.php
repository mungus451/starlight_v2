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
    <link rel="stylesheet" href="/css/starlight.css?v=1.1">
</head>
<body>

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

                <!-- Empire Dropdown -->
                <li class="nav-item">
                    <span class="nav-link"><i class="fas fa-city"></i> Empire <i class="fas fa-caret-down" style="margin-left: 5px; font-size: 0.8em; opacity: 0.7;"></i></span>
                    <ul class="nav-submenu">
                        <li><a href="/structures"><i class="fas fa-industry"></i> Structures</a></li>
                        <!-- <li><a href="/embassy"><i class="fas fa-landmark"></i> Embassy</a></li> -->
                        <li><a href="/level-up"><i class="fas fa-bolt"></i> Level Up</a></li>
                        <li><a href="/leaderboard"><i class="fas fa-trophy"></i> Leaderboard</a></li>
                    </ul>
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
                    <span class="nav-link"><i class="fas fa-crosshairs"></i> Military <i class="fas fa-caret-down" style="margin-left: 5px; font-size: 0.8em; opacity: 0.7;"></i></span>
                    <ul class="nav-submenu">
                        <li><a href="/generals"><i class="fas fa-user-tie"></i> Elite Units</a></li>
                        <li><a href="/training"><i class="fas fa-users"></i> Training</a></li>
                        <li><a href="/armory"><i class="fas fa-shield-alt"></i> Armory</a></li>
                        <li><a href="/spy"><i class="fas fa-user-secret"></i> Spy Network</a></li>
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

    <script src="/js/utils.js"></script>
    
    <?php if ($isLoggedIn): ?>
        <script src="/js/notifications.js"></script>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Mobile Menu Toggle
            const nav = document.querySelector('.main-nav');
            // Create toggle button dynamically to avoid layout issues on desktop
            const toggleBtn = document.createElement('button');
            toggleBtn.className = 'mobile-menu-btn';
            toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
            toggleBtn.style.display = 'none'; // Hidden by default (desktop)
            
            const brand = nav.querySelector('.nav-brand');
            if (brand) {
                brand.parentNode.insertBefore(toggleBtn, brand.nextSibling);
            }

            toggleBtn.addEventListener('click', () => {
                nav.classList.toggle('mobile-open');
            });

            // Mobile Dropdown Toggles
            const dropdowns = document.querySelectorAll('.nav-item');
            dropdowns.forEach(item => {
                const link = item.querySelector('.nav-link');
                const submenu = item.querySelector('.nav-submenu');
                
                if (submenu && link) {
                    link.addEventListener('click', (e) => {
                        // Only prevent default if we are in mobile view
                        if (window.innerWidth <= 980) {
                            if (e.target.closest('.nav-submenu')) return; // Allow clicking links inside
                            e.preventDefault();
                            item.classList.toggle('active');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>