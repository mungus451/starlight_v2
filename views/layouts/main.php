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
            min-height: 90vh;
            background: #1a1a2e; /* Dark space blue */
            color: #e0e0e0;
            margin: 0;
        }
        .container {
            width: 100%;
            max-width: 400px;
            text-align: center;
            background: #1e1e3f; /* Slightly lighter blue */
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            border: 1px solid #3a3a5a;
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
        .form-group input {
            padding: 0.75rem;
            border-radius: 5px;
            border: 1px solid #3a3a5a;
            background: #2a2a4a;
            color: #e0e0e0;
            font-size: 1rem;
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