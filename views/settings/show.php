<div class="container-full">
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

        <div class="item-card">
            <h4><i class="fas fa-bell"></i> Push Notification Preferences</h4>
            <form action="/settings/notifications" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                
                <p class="form-note" style="margin-bottom: 1rem;">Control which types of alerts trigger browser push notifications. All notifications will still appear in your Command Uplink history.</p>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="attack_enabled" value="1" <?= $notification_prefs->attack_enabled ? 'checked' : '' ?>>
                        <span><i class="fas fa-crosshairs" style="color: #ff6b6b; margin-right: 0.5rem;"></i>Attack Push Notifications</span>
                    </label>
                    <p class="form-note" style="margin-left: 1.5rem; margin-top: 0.25rem;">Receive browser push when your empire is attacked</p>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="spy_enabled" value="1" <?= $notification_prefs->spy_enabled ? 'checked' : '' ?>>
                        <span><i class="fas fa-user-secret" style="color: #9b59b6; margin-right: 0.5rem;"></i>Espionage Push Notifications</span>
                    </label>
                    <p class="form-note" style="margin-left: 1.5rem; margin-top: 0.25rem;">Receive browser push when enemy spies target your empire</p>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="alliance_enabled" value="1" <?= $notification_prefs->alliance_enabled ? 'checked' : '' ?>>
                        <span><i class="fas fa-users" style="color: #3498db; margin-right: 0.5rem;"></i>Alliance Push Notifications</span>
                    </label>
                    <p class="form-note" style="margin-left: 1.5rem; margin-top: 0.25rem;">Receive browser push for alliance activities and updates</p>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="system_enabled" value="1" <?= $notification_prefs->system_enabled ? 'checked' : '' ?>>
                        <span><i class="fas fa-info-circle" style="color: #95a5a6; margin-right: 0.5rem;"></i>System Push Notifications</span>
                    </label>
                    <p class="form-note" style="margin-left: 1.5rem; margin-top: 0.25rem;">Receive browser push for game updates and important announcements</p>
                </div>

                <div class="divider"></div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="push_notifications_enabled" value="1" <?= $notification_prefs->push_notifications_enabled ? 'checked' : '' ?>>
                        <span><i class="fas fa-bell" style="color: #f39c12; margin-right: 0.5rem;"></i>Enable Browser Push Notifications</span>
                    </label>
                    <p class="form-note" style="margin-left: 1.5rem; margin-top: 0.25rem;">Master toggle: Enable/disable all browser push notifications (requires browser permission)</p>
                </div>

                <button type="submit" class="btn-submit">Save Preferences</button>
            </form>
        </div>

    </div> 
</div>