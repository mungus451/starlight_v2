<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?= htmlspecialchars($meta['title'] ?? ($title ?? 'Starlight Dominion')) ?></title>
    <meta name="title" content="<?= htmlspecialchars($meta['title'] ?? '') ?>">
    <meta name="description" content="<?= htmlspecialchars($meta['description'] ?? '') ?>">
    <meta name="keywords" content="<?= htmlspecialchars($meta['keywords'] ?? '') ?>">
    <link rel="canonical" href="<?= htmlspecialchars($meta['url'] ?? '') ?>">

    <meta property="og:type" content="<?= htmlspecialchars($meta['type'] ?? 'website') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($meta['url'] ?? '') ?>">
    <meta property="og:title" content="<?= htmlspecialchars($meta['title'] ?? '') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($meta['description'] ?? '') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($meta['image'] ?? '') ?>">
    <meta property="og:site_name" content="<?= htmlspecialchars($meta['site_name'] ?? 'Starlight Dominion') ?>">

    <meta property="twitter:card" content="<?= htmlspecialchars($meta['twitter']['card'] ?? 'summary_large_image') ?>">
    <meta property="twitter:url" content="<?= htmlspecialchars($meta['url'] ?? '') ?>">
    <meta property="twitter:title" content="<?= htmlspecialchars($meta['title'] ?? '') ?>">
    <meta property="twitter:description" content="<?= htmlspecialchars($meta['description'] ?? '') ?>">
    <meta property="twitter:image" content="<?= htmlspecialchars($meta['image'] ?? '') ?>">
    <meta property="twitter:site" content="<?= htmlspecialchars($meta['twitter']['site'] ?? '') ?>">

    <link rel="stylesheet" href="/css/fonts.css">
    
    <style>
        /* --- CSS FIX: Global box sizing and responsive padding --- */
        *, *::before, *::after {
            box-sizing: border-box;
        }

        body {
            font-family: "Orbitron", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            min-height: 100vh;
            background-image: url("/background.avif"); /* root-level background */
            background-size: cover;                    /* fill the viewport */
            background-position: center top;           /* center it nicely */
            background-repeat: no-repeat;              /* no tiling */
            background-attachment: fixed;              /* subtle parallax feel */
            color: #e0e0e0;
            margin: 0;
            padding-top: 90px; /* Increased padding for XP bar space */
        }
        /* --- Navigation Styles --- */
        nav {
            background: #1e1e3f;
            border-bottom: 1px solid #3a3a5a;
            padding: 1rem 1rem 1.5rem 1rem; /* Added bottom padding for bar */
            width: 100%;
            position: absolute;
            top: 0;
            left: 0;
            text-align: center;
            z-index: 10;
        }
        nav a {
            color: #e0e0e0;
            text-decoration: none;
            font-weight: bold;
            padding: 0 0.75rem;
            font-size: 1.0rem;
        }
        nav a:hover {
            color: #f9c74f;
        }
        
        /* --- XP Bar Styles --- */
        .xp-bar-container {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: rgba(0,0,0,0.3);
        }
        .xp-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50, #00E676);
            width: 0%; /* Set via inline style */
            transition: width 0.6s ease-out;
            box-shadow: 0 0 10px rgba(76, 175, 80, 0.5);
        }
        .xp-level-badge {
            position: absolute;
            bottom: -22px;
            left: 50%;
            transform: translateX(-50%);
            background: #1e1e3f;
            border: 1px solid #3a3a5a;
            border-top: none;
            padding: 2px 12px;
            border-radius: 0 0 8px 8px;
            font-size: 0.75rem;
            color: #f9c74f;
            font-weight: bold;
            font-family: "Orbitron", sans-serif;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
            white-space: nowrap;
        }
        .xp-details {
            font-weight: normal;
            color: #a8afd4;
            margin-left: 5px;
            font-size: 0.7rem;
        }
        /* --- End XP Bar Styles --- */
        
        .container {
            width: 100%;
            max-width: 800px;
            text-align: center;
            background: #1e1e3f;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            border: 1px solid #3a3a5a;
            margin: 2rem auto;
        }

        /* Full-width container */
        .container-full {
            width: 100%;
            max-width: 1600px;
            text-align: left;
            background: #1e1e3f;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            border: 1px solid #3a3a5a;
            margin: 2rem auto;
        }

        @media (max-width: 768px) {
            .container, .container-full {
                padding: 1rem;
                margin: 1rem auto;
            }
            nav a {
                padding: 0 0.5rem;
                font-size: 0.9rem;
            }
        }
        @media (max-width: 480px) {
            nav a {
                padding: 0 0.25rem;
                font-size: 0.8rem;
            }
        }

        h1 {
            color: #f9c74f; /* Gold */
            margin-top: 0;
            font-size: 2.5rem;
        }
        
        /* Form Styles */
        form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            text-align: left;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #c0c0e0;
        }
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea {
            padding: 0.75rem;
            border-radius: 5px;
            border: 1px solid #3a3a5a;
            background: #2a2a4a;
            color: #e0e0e0;
            font-size: 1rem;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .form-group textarea {
            min-height: 80px;
        }
        .btn-submit {
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: #5a67d8; /* Indigo */
            color: white;
            border: none;
            cursor: pointer;
            margin-top: 1rem;
        }
        .btn-submit:hover {
            background: #7683f5;
        }
        .btn-submit:disabled {
            background: #4a4a6a;
            cursor: not-allowed;
        }
        
        /* Flash Messages */
        .flash {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            font-weight: bold;
            text-align: center;
        }
        .flash-error {
            background: #e53e3e; /* Red */
            color: white;
        }
        .flash-success {
            background: #4CAF50; /* Green */
            color: white;
        }
        
        .form-link {
            margin-top: 1rem;
            color: #c0c0e0;
        }
        .form-link a {
            color: #7683f5;
            font-weight: bold;
            text-decoration: none;
        }
    </style>
</head>
<body>

    <nav>
        <?php if ($session->has('user_id')): ?>
            <?php $userAllianceId = $session->get('alliance_id'); ?>
        
            <a href="/dashboard">Dashboard</a>
            <a href="/bank">Bank</a>
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

            <?php 
            // XP Bar Render
            if (isset($global_xp_data) && isset($global_user_level)): 
            ?>
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
    // Check if a layoutMode is set and if it's 'full'.
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
</body>
</html>