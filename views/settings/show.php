<style>
    :root {
        --bg: radial-gradient(circle at 10% 0%, #0c101e 0%, #050712 42%, #02030a 75%);
        --bg-panel: rgba(12, 14, 25, 0.65);
        --card: radial-gradient(circle at 30% -10%, rgba(45, 209, 209, 0.07), rgba(13, 15, 27, 0.6));
        --border: rgba(255, 255, 255, 0.03);
        --accent: #2dd1d1; /* Main Accent (Teal) */
        --accent-soft: rgba(45, 209, 209, 0.12);
        --accent-2: #f9c74f; /* Secondary Accent (Gold) */
        --accent-red: #e53e3e;
        --text: #eff1ff;
        --muted: #a8afd4;
        --radius: 18px;
        --shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
    }

    /* --- Base Container --- */
    .settings-container-full {
        width: 100%;
        max-width: 1400px;
        margin-inline: auto;
        padding: 0;
        position: relative;
    }

    .settings-container-full h1 {
        text-align: center;
        margin-bottom: 2rem; /* More space for a title */
        font-size: clamp(2.1rem, 3vw, 2.6rem);
        letter-spacing: -0.03em;
        color: #fff;
        padding-top: 1.5rem;
    }
    
    /* --- Grid for Action Cards --- */
    .item-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr); /* 2 cards on desktop */
        gap: 1.5rem;
    }
    
    @media (max-width: 980px) {
        .item-grid {
            grid-template-columns: 1fr; /* 1 card on mobile */
        }
    }

    /* --- Action Card (from Armory/Structures) --- */
    .item-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
       /* padding: 1.25rem 1.5rem; */
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
        transition: transform 0.1s ease-out, border 0.1s ease-out;
    }
    .item-card:hover {
        transform: translateY(-2px);
        border: 1px solid rgba(45, 209, 209, 0.4);
    }
    .item-card h4 {
        color: #fff;
        margin: 0 0 1rem 0;
        font-size: 1.1rem;
        border-bottom: 1px solid var(--border);
        padding-bottom: 0.75rem;
    }
    
    .item-card .btn-submit {
        width: 100%;
        margin-top: 0.5rem; /* Give a bit of space above button */
        border: none;
        background: linear-gradient(120deg, var(--accent) 0%, #1f8ac5 100%);
        color: #fff;
        padding: 0.6rem 0.75rem; 
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: filter 0.1s ease-out, transform 0.1s ease-out;
    }
    .item-card .btn-submit:not([disabled]):hover {
        filter: brightness(1.02);
        transform: translateY(-1px);
    }
    .item-card .btn-submit[disabled] {
        background: rgba(187, 76, 76, 0.15);
        border: 1px solid rgba(187, 76, 76, 0.3);
        color: rgba(255, 255, 255, 0.35);
        cursor: not-allowed;
    }

    /* --- PRESERVED STYLES from old settings.php --- */
    .form-group textarea {
        padding: 0.75rem;
        border-radius: 5px;
        border: 1px solid #3a3a5a;
        background: #2a2a4a;
        color: #e0e0e0;
        font-size: 1rem;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        min-height: 80px;
    }
    .form-note {
        font-size: 0.9rem;
        color: var(--muted);
        margin-top: -0.5rem;
        margin-bottom: 1rem;
    }
    .divider {
        border-bottom: 1px solid var(--border); /* Use new border color */
        margin: 1.5rem 0;
    }
    .pfp-preview-container {
        display: flex;
        gap: 1rem;
        align-items: center;
        margin-bottom: 1rem;
    }
    .pfp-preview {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: #1e1e3f; /* Darker bg */
        border: 2px solid var(--accent-soft); /* Teal border */
        object-fit: cover;
    }
    .pfp-upload-group {
        flex-grow: 1;
    }
    .remove-pfp-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.75rem;
    }
    .remove-pfp-group label {
        margin-bottom: 0;
        font-size: 0.9rem;
        color: var(--muted);
    }
    .remove-pfp-group input {
        width: 1.1rem;
        height: 1.1rem;
    }
</style>

<div class="settings-container-full">
    <h1>Settings</h1>

    <div class="item-grid">
        
        <div class="item-card">
            <h4>Public Profile</h4>
            <form action="/settings/profile" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                
                <div class="form-group">
                    <label for="bio">Bio (max 500 characters)</label>
                    <textarea name="bio" id="bio"><?= htmlspecialchars($user->bio ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Profile Picture (Max 2MB)</label>
                    <div class="pfp-preview-container">
                        
                        <?php // --- THIS BLOCK IS CHANGED --- ?>
                        <?php if ($user->profile_picture_url): ?>
                            <img src="/serve/avatar/<?= htmlspecialchars($user->profile_picture_url) ?>" alt="Current Avatar" class="pfp-preview">
                        <?php else: ?>
                            <svg class="pfp-preview" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" style="padding: 1.25rem; color: #a8afd4;">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                            </svg>
                        <?php endif; ?>
                        
                        <div class="pfp-upload-group">
                            <input type="file" name="profile_picture" id="profile_picture" accept="image/jpeg,image/png,image/gif,image/webp,image/avif">
                            
                            <?php if ($user->profile_picture_url): ?>
                            <div class="remove-pfp-group">
                                <input type="checkbox" name="remove_picture" id="remove_picture" value="1">
                                <label for="remove_picture">Remove current picture</label>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone_number">Phone Number (optional)</label>
                    <input type="text" name="phone_number" id="phone_number" value="<?= htmlspecialchars($user->phone_number ?? '') ?>">
                </div>

                <button type="submit" class="btn-submit">Save Profile</button>
            </form>
        </div>

        <div class="item-card">
            <h4>Change Email</h4>
            <form action="/settings/email" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                
                <div class="form-group">
                    <label for="email">New Email</label>
                    <input type="email" name="email" id="email" value="<?= htmlspecialchars($user->email ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="current_password_email">Current Password (Required)</label>
                    <input type="password" name="current_password_email" id="current_password_email" required>
                </div>
                <p class="form-note">You must enter your current password to change your email.</p>

                <button type="submit" class="btn-submit">Update Email</button>
            </form>
        </div>

        <div class="item-card">
            <h4>Change Password</h4>
            <form action="/settings/password" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                
                <div class="form-group">
                    <label for="old_password">Current Password</label>
                    <input type="password" name="old_password" id="old_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password (min 3 characters)</label>
                    <input type="password" name="new_password" id="new_password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" required>
                </div>

                <button type="submit" class="btn-submit">Update Password</button>
            </form>
        </div>

        <div class="item-card">
            <h4>Security Questions</h4>
            <form action="/settings/security" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                
                <div class="form-group">
                    <label for="question_1">Question 1</label>
                    <input type="text" name="question_1" id="question_1" value="<?= htmlspecialchars($security->question_1 ?? '') ?>" placeholder="e.g., What city were you born in?" required>
                </div>
                <div class="form-group">
                    <label for="answer_1">Answer 1 (Case-sensitive)</label>
                    <input type="password" name="answer_1" id="answer_1" required>
                </div>

                <div class="divider"></div>

                <div class="form-group">
                    <label for="question_2">Question 2</label>
                    <input type="text" name="question_2" id="question_2" value="<?= htmlspecialchars($security->question_2 ?? '') ?>" placeholder="e.g., What was your first pet's name?" required>
                </div>
                <div class="form-group">
                    <label for="answer_2">Answer 2 (Case-sensitive)</label>
                    <input type="password" name="answer_2" id="answer_2" required>
                </div>

                <div class="divider"></div>

                <div class="form-group">
                    <label for="current_password_security">Current Password (Required)</label>
                    <input type="password" name="current_password_security" id="current_password_security" required>
                </div>
                <p class="form-note">You must enter your current password to update security questions. Your answers will be stored securely and are never displayed.</p>

                <button type="submit" class="btn-submit">Update Security</button>
            </form>
        </div>

    </div> 
</div>