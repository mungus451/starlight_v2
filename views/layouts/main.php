<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <?php 
        // Ensure CSRF service is available; falling back safely if not set in view vars yet
        $csrfToken = $_SESSION['csrf_token'] ?? ''; 
    ?>
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">

    <title><?= $title ?? 'StarlightDominion' ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400..900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* --- Global Reset & Touch Optimization --- */
        *, *::before, *::after {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            min-height: 100vh;
            background-image: url("/background.avif");
            background-size: cover;
            background-position: center top;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: #e0e0e0;
            margin: 0;
            /* Account for new larger nav wrapping */
            padding-top: 140px; 
            /* Disables double-tap to zoom, speeding up click events */
            touch-action: manipulation; 
        }

        /* --- Responsive Navigation --- */
        nav {
            background: #1e1e3f;
            border-bottom: 1px solid #3a3a5a;
            padding: 0.75rem;
            width: 100%;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.4);
        }

        nav a {
            color: #e0e0e0;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            /* Large touch target for mobile (min 44px height) */
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: background 0.2s ease, color 0.2s ease;
            display: inline-block;
            white-space: nowrap;
            position: relative; /* For badge positioning */
        }

        /* Mobile specific adjustments */
        @media (max-width: 768px) {
            body {
                padding-top: 160px; /* More space for wrapped menu */
            }
            nav {
                padding: 0.5rem;
                gap: 0.25rem;
            }
            nav a {
                flex: 1 1 auto; /* Grow to fill space */
                text-align: center;
                background: rgba(255, 255, 255, 0.05);
                border: 1px solid rgba(255, 255, 255, 0.05);
                font-size: 0.9rem;
                padding: 0.6rem 0.5rem;
            }
        }

        /* Only apply hover effects on devices that actually hover (Desktop) */
        @media (hover: hover) {
            nav a:hover {
                color: #f9c74f; /* Original Gold hover color */
                background: rgba(255, 255, 255, 0.05);
            }
        }

        /* Active/Focus state for touch feedback */
        nav a:active {
            background: rgba(249, 199, 79, 0.25);
            transform: scale(0.98);
        }
        
        /* --- Notification Badge --- */
        .nav-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 0.15rem 0.4rem;
            font-size: 0.7rem;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.5);
            display: none; /* Hidden by default until JS enables it */
            animation: none;
        }
        
        .nav-badge.pulse {
            animation: badgePulse 2s infinite;
        }

        @keyframes badgePulse {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
            70% { box-shadow: 0 0 0 6px rgba(220, 53, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }

        /* --- XP Bar Styles --- */
        .xp-bar-container {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: rgba(0,0,0,0.5);
        }
        .xp-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50, #00E676);
            width: 0%;
            transition: width 0.6s ease-out;
            box-shadow: 0 0 8px rgba(76, 175, 80, 0.6);
        }
        .xp-level-badge {
            position: absolute;
            bottom: -24px;
            left: 50%;
            transform: translateX(-50%);
            background: #1e1e3f; /* Matches Nav */
            border: 1px solid #3a3a5a;
            border-top: none;
            padding: 4px 16px;
            border-radius: 0 0 12px 12px;
            font-size: 0.75rem;
            color: #f9c74f;
            font-weight: bold;
            font-family: "Orbitron", sans-serif;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            white-space: nowrap;
            z-index: 101;
        }
        .xp-details {
            font-weight: normal;
            color: #a8afd4;
            margin-left: 6px;
            font-size: 0.7rem;
        }
        
        /* --- Layout Containers --- */
        .container {
            width: 100%;
            max-width: 800px;
            text-align: center;
            background: #1e1e3f;
            padding: 2rem;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            border: 1px solid #3a3a5a;
            margin: 1rem auto;
        }

        .container-full {
            width: 100%;
            max-width: 1600px;
            text-align: left;
            background: #1e1e3f;
            padding: 1rem; 
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            border: 1px solid #3a3a5a;
            margin: 1rem auto;
        }

        @media (max-width: 768px) {
            .container, .container-full {
                padding: 1rem;
                margin: 0.5rem auto;
                width: 100%;
                border-radius: 0;
                border-left: none;
                border-right: none;
            }
        }

        h1 {
            color: #f9c74f; /* Gold */
            margin-top: 0;
            font-size: 2.2rem;
            text-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }
        
        /* --- Form & Button Standards --- */
        form {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            text-align: left;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #c0c0e0;
            font-size: 0.9rem;
        }
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea {
            padding: 0.85rem;
            border-radius: 10px;
            border: 1px solid #3a3a5a;
            background: #2a2a4a;
            color: #e0e0e0;
            font-size: 1rem;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            width: 100%;
        }
        .form-group textarea {
            min-height: 100px;
        }
        .btn-submit {
            padding: 0.9rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1rem;
            transition: filter 0.2s ease, transform 0.1s ease;
            background: #5a67d8;
            color: white;
            border: none;
            cursor: pointer;
            margin-top: 0.5rem;
            text-align: center;
            display: inline-block;
        }
        .btn-submit:hover {
            background: #7683f5;
        }
        .btn-submit:active {
            transform: scale(0.98);
        }
        .btn-submit:disabled {
            background: #4a4a6a;
            cursor: not-allowed;
            filter: grayscale(1);
        }
        
        /* Flash Messages */
        .flash {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 600;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .flash-error {
            background: #e53e3e;
            color: white;
        }
        .flash-success {
            background: #4CAF50;
            color: white;
        }
        
        .form-link {
            margin-top: 1.5rem;
            color: #c0c0e0;
            text-align: center;
        }
        .form-link a {
            color: #7683f5;
            font-weight: bold;
            text-decoration: none;
            padding: 0.5rem;
        }
    </style>
</head>
<body>

    <nav>
        <?php if ($session->has('user_id')): ?>
            <?php $userAllianceId = $session->get('alliance_id'); ?>
        
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
            
            <?php if ($userAllianceId !== null): ?>
                <a href="/alliance/profile/<?= $userAllianceId ?>">My Alliance</a>
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
    
        <?php $error = $session->getFlash('error'); ?>
        <?php if ($error): ?>
            <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php $success = $session->getFlash('success'); ?>
        <?php if ($success): ?>
            <div class="flash flash-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?= $content ?>
    </div>

    <script src="/js/utils.js"></script>
    
    <?php if ($session->has('user_id')): ?>
        <script src="/js/notifications.js"></script>
    <?php endif; ?>
</body>
</html>