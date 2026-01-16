<?php
// --- Mobile Settings View ---
/* @var \App\Models\Entities\User $user */
/* @var \App\Models\Entities\Security|null $security */
/* @var string $csrf_token */
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">Settings</h1>
        <p style="color: var(--mobile-text-secondary); font-size: 0.9rem; margin: 0.5rem 1rem 0 1rem; text-align: center;">Manage your profile and account security.</p>
    </div>

    <!-- WRAPPER FOR TABS LOGIC -->
    <div class="nested-tabs-container">
        
        <!-- Tab Navigation -->
        <div class="mobile-tabs nested-tabs">
            <a href="#" class="tab-link active" data-tab-target="tab-profile">Profile</a>
            <a href="#" class="tab-link" data-tab-target="tab-account">Account</a>
            <a href="#" class="tab-link" data-tab-target="tab-security">Security</a>
        </div>

        <!-- Tab Content -->
        <div id="tab-content">
            
            <!-- Profile Tab -->
            <div id="tab-profile" class="nested-tab-content active">
                <div class="mobile-card">
                    <div class="mobile-card-header"><h3><i class="fas fa-user-circle"></i> Public Profile</h3></div>
                    <div class="mobile-card-content" style="display: block;">
                        <form action="/settings/profile" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            
                            <div class="form-group" style="align-items: center; text-align: center;">
                                <img src="<?= htmlspecialchars($user->profile_picture_url ?? '/img/default_avatar.png') ?>" style="width: 100px; height: 100px; border-radius: 50%; border: 2px solid var(--mobile-accent-blue); margin-bottom: 1rem;">
                                <label for="profile_picture">Upload New Avatar</label>
                                <input type="file" name="profile_picture" id="profile_picture" class="mobile-input" style="width: 100%;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
                                    <input type="checkbox" name="remove_picture" id="remove_picture" value="1">
                                    <label for="remove_picture" style="margin-bottom: 0;">Remove Picture</label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="bio">Bio</label>
                                <textarea name="bio" id="bio" class="mobile-input" style="width: 100%; height: 100px;"><?= htmlspecialchars($user->bio ?? '') ?></textarea>
                            </div>

                            <button type="submit" class="btn" style="width: 100%;">Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Account Tab -->
            <div id="tab-account" class="nested-tab-content">
                <div class="mobile-card">
                    <div class="mobile-card-header"><h3><i class="fas fa-envelope"></i> Change Email</h3></div>
                    <div class="mobile-card-content" style="display: block;">
                        <form action="/settings/email" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <div class="form-group">
                                <label for="email">New Email</label>
                                <input type="email" name="email" id="email" class="mobile-input" value="<?= htmlspecialchars($user->email ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="current_password_email">Current Password</label>
                                <input type="password" name="current_password_email" id="current_password_email" class="mobile-input" required>
                            </div>
                            <button type="submit" class="btn" style="width: 100%;">Update Email</button>
                        </form>
                    </div>
                </div>

                <div class="mobile-card">
                    <div class="mobile-card-header"><h3><i class="fas fa-key"></i> Change Password</h3></div>
                    <div class="mobile-card-content" style="display: block;">
                        <form action="/settings/password" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <div class="form-group">
                                <label for="old_password">Old Password</label>
                                <input type="password" name="old_password" id="old_password" class="mobile-input" required>
                            </div>
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" name="new_password" id="new_password" class="mobile-input" required>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" name="confirm_password" id="confirm_password" class="mobile-input" required>
                            </div>
                            <button type="submit" class="btn" style="width: 100%;">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Security Tab -->
            <div id="tab-security" class="nested-tab-content">
                <div class="mobile-card">
                    <div class="mobile-card-header"><h3><i class="fas fa-user-shield"></i> Security Questions</h3></div>
                    <div class="mobile-card-content" style="display: block;">
                        <form action="/settings/security" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <div class="form-group">
                                <label for="question_1">Question 1</label>
                                <input type="text" name="question_1" id="question_1" class="mobile-input" value="<?= htmlspecialchars($security->question_1 ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="answer_1">Answer 1</label>
                                <input type="password" name="answer_1" id="answer_1" class="mobile-input" required>
                            </div>
                            <hr style="border-color: var(--mobile-border); margin: 1.5rem 0;">
                            <div class="form-group">
                                <label for="question_2">Question 2</label>
                                <input type="text" name="question_2" id="question_2" class="mobile-input" value="<?= htmlspecialchars($security->question_2 ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="answer_2">Answer 2</label>
                                <input type="password" name="answer_2" id="answer_2" class="mobile-input" required>
                            </div>
                            <hr style="border-color: var(--mobile-border); margin: 1.5rem 0;">
                            <div class="form-group">
                                <label for="current_password_security">Current Password</label>
                                <input type="password" name="current_password_security" id="current_password_security" class="mobile-input" required>
                            </div>
                            <button type="submit" class="btn" style="width: 100%;">Update Security</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div> <!-- End nested-tabs-container -->
</div>
