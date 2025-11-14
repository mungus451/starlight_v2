<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'StarlightDominion' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400..900&display=swap" rel="stylesheet">
    <style>
        /* --- CSS FIX: Global box sizing and responsive padding --- */
        *, *::before, *::after {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            min-height: 100vh;
            background-image: url("/background.avif"); /* root-level background */
            background-size: cover;                    /* fill the viewport */
            background-position: center top;           /* center it nicely */
            background-repeat: no-repeat;              /* no tiling */
            background-attachment: fixed;              /* subtle parallax feel */
            color: #e0e0e0;
            margin: 0;
            padding-top: 80px; 
        }
        /* --- Navigation Styles --- */
        nav {
            background: #1e1e3f;
            border-bottom: 1px solid #3a3a5a;
            padding: 1rem;
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
            padding: 0 1rem;
            font-size: 1.1rem;
        }
        nav a:hover {
            color: #f9c74f;
        }
        /* --- End Nav Styles --- */
        
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

        /* --- CSS FIX: Reduce padding on mobile devices --- */
        @media (max-width: 768px) {
            .container, .container-full {
                padding: 1rem;
                margin: 1rem auto;
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
            <a href="/dashboard">Dashboard</a>
            <a href="/bank">Bank</a>
            <a href="/training">Training</a>
            <a href="/structures">Structures</a>
            <a href="/armory">Armory</a>
            <a href="/spy">Spy</a>
            <a href="/battle">Battle</a>
            <a href="/level-up">Level Up</a>
            <a href="/alliance/list">Alliance</a>
            <a href="/settings">Settings</a>
            <a href="/logout">Logout</a>
        <?php else: ?>
            <a href="/">Home</a>
            <a href="/contact">Contact</a>
            <a href="/login">Login</a>
            <a href="/register">Register</a>
        <?php endif; ?>
    </nav>
    
    <?php 
    // Check if a layoutMode is set and if it's 'full'.
    // If not set, it will default to the standard 'constrained' container.
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