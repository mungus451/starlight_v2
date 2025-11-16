<style>
    :root {
        --card: radial-gradient(circle at 30% -10%, rgba(45, 209, 209, 0.07), rgba(13, 15, 27, 0.6));
        --border: rgba(255, 255, 255, 0.03);
        --accent: #2dd1d1;
        --accent-2: #f9c74f;
        --text: #eff1ff;
        --muted: #a8afd4;
        --radius: 18px;
        --shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
    }
    
    .auth-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.25rem 1.5rem;
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
        max-width: 500px; /* Constrain form width */
        margin: 2rem auto 0 auto; /* Center the card */
        text-align: left;
    }
    
    .auth-card h1 {
        text-align: center;
        margin-bottom: 1.5rem;
        font-size: 1.8rem;
    }
    
    .auth-card .form-link {
        text-align: center;
    }
</style>

<div class="auth-card">
    <h1>Register</h1>

    <form action="/register" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
            <label for="character_name">Character Name</label>
            <input type="text" id="character_name" name="character_name" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password (min 3 characters)</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        
        <button type="submit" class="btn-submit">Register</button>
    </form>

    <div class="form-link">
        Already have an account? <a href="/login">Login here</a>
    </div>
</div>