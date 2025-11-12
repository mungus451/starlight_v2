<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'StarlightDominion' ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            display: grid;
            place-items: center;
            min-height: calc(100vh - 80px); /* Adjusted for nav */
            background: #1a1a2e; /* Dark space blue */
            color: #e0e0e0;
            margin: 0;
            padding-top: 80px; /* Space for the nav */
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
            box-sizing: border-box;
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
            max-width: 800px; /* Increased width for dashboard/bank */
            text-align: center;
            background: #1e1e3f; /* Slightly lighter blue */
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            border: 1px solid #3a3a5a;
            margin-bottom: 2rem; /* Add some space at the bottom */
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
            <a href="/spy">Spy</a>
            <a href="/battle">Battle</a>
            <a href="/level-up">Level Up</a>
            <a href="/alliance/list">Alliance</a> <a href="/settings">Settings</a>
            <a href="/logout">Logout</a>
        <?php else: ?>
            <a href="/">Home</a>
            <a href="/contact">Contact</a>
            <a href="/login">Login</a>
            <a href="/register">Register</a>
        <?php endif; ?>
    </nav>

    <div class="container">
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