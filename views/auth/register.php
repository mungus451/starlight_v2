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