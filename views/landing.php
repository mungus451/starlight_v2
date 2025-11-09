<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to StarlightDominion</title>
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
            text-align: center;
            background: #1e1e3f; /* Slightly lighter blue */
            padding: 2rem 3rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            border: 1px solid #3a3a5a;
        }
        h1 {
            color: #f9c74f; /* Gold */
            margin-top: 0;
            font-size: 2.5rem;
        }
        p {
            font-size: 1.1rem;
            color: #c0c0e0;
        }
        .actions a {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            margin: 0.5rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1rem;
            transition: all 0.2s ease;
        }
        .login {
            background: #4CAF50; /* Green */
            color: white;
        }
        .register {
            background: #5a67d8; /* Indigo */
            color: white;
        }
        .login:hover {
            background: #5cb85c;
        }
        .register:hover {
            background: #7683f5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>StarlightDominion</h1>
        <p>A new era is dawning. The MVC rewrite is in progress.</p>
        <div class="actions">
            <a href="/login" class="login">Login</a>
            <a href="/register" class="register">Register</a>
        </div>
    </div>
</body>
</html>