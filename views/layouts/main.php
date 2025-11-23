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
    <link rel="stylesheet" href="/css/starlight.css?v=1.0">
</head>
<body>

    <nav>
        <?php if ($isLoggedIn): ?>
        
            <a href="/notifications" class="position-relative">
                <i class="fas fa-bell"></i>
                <span id="nav-notification-badge" class="nav-badge"></span>
            </a>

            <a href="/dashboard">Dashboard</a>
            <a href="/bank">Bank</a>
            <a href="/black-market/converter">Black Market</a>
            <a href="/training">Training</a>
            <a href="/structures">Structures</a>
            <a href="/armory">Armory</a>
            <a href="/spy">Spy</a>
            <a href="/battle">Battle</a>
            <a href="/level-up">Level Up</a>
            
            <?php if ($currentUserAllianceId !== null): ?>
                <a href="/alliance/profile/<?= $currentUserAllianceId ?>">My Alliance</a>
                <a href="/alliance/forum">Forum</a>
                <a href="/alliance/structures">A. Structures</a>
                <a href="/alliance/diplomacy">Diplomacy</a>
                <a href="/alliance/war">War Room</a>
            <?php else: ?>
                <a href="/alliance/list">Alliances</a>
            <?php endif; ?>
            
            <a href="/settings">Settings</a>
            <a href="/logout">Logout</a>

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
            <a href="/">Home</a>
            <a href="/contact">Contact</a>
            <a href="https://discord.gg/sCKvuxHAqt" target="_blank">Discord</a>
            <a href="/login">Login</a>
            <a href="/register">Register</a>
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
</body>
</html>