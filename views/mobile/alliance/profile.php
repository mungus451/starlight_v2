<?php
// --- Mobile Alliance Profile View ---
/* @var array $alliance */
/* @var array $state */
/* @var array $perms */
/* @var array $members */
/* @var array $logs */
/* @var array $loans */
/* @var array $applications */
/* @var array $roles */
/* @var string $csrf_token */
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">
            <span style="color: var(--mobile-accent-blue);">[<?= htmlspecialchars($alliance['tag']) ?>]</span> 
            <?= htmlspecialchars($alliance['name']) ?>
        </h1>
    </div>

    <!-- Status Card -->
    <div class="mobile-card" style="text-align: center; border-color: var(--mobile-accent-blue);">
        <div class="mobile-card-content" style="display: block;">
            <div style="display: flex; justify-content: space-around; margin-bottom: 1rem;">
                <?php if ($alliance['profile_picture_url']): ?>
                    <img src="/serve/alliance_avatar/<?= htmlspecialchars($alliance['profile_picture_url']) ?>" style="width: 80px; height: 80px; border-radius: 50%; border: 2px solid var(--mobile-accent-blue);">
                <?php endif; ?>
                <div style="text-align: left; display: flex; flex-direction: column; justify-content: center;">
                    <div style="font-size: 0.9rem; color: var(--muted);">Bank</div>
                    <div style="font-size: 1.2rem; color: var(--mobile-text-primary); font-weight: bold;"><?= $alliance['bank_credits'] ?> ₡</div>
                    <div style="font-size: 0.9rem; color: var(--muted); margin-top: 0.5rem;">Recruitment</div>
                    <div style="font-size: 1rem; color: <?= $alliance['recruitment_status_color'] ?>; font-weight: bold;"><?= $alliance['recruitment_status_text'] ?></div>
                </div>
            </div>
            
            <?php if (!$state['is_member']): ?>
                <?php if ($state['has_applied']): ?>
                    <form action="/alliance/cancel-app/<?= $alliance['id'] ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <button type="submit" class="btn btn-outline" style="width: 100%;">Cancel Application</button>
                    </form>
                <?php elseif ($alliance['is_joinable']): ?>
                    <form action="/alliance/apply/<?= $alliance['id'] ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <button type="submit" class="btn btn-accent" style="width: 100%;">Apply to Join</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- WRAPPER FOR TABS LOGIC -->
    <div class="nested-tabs-container">
        
        <!-- Tab Navigation -->
        <div class="mobile-tabs nested-tabs">
            <a href="#" class="tab-link active" data-tab-target="tab-overview">Overview</a>
            <a href="#" class="tab-link" data-tab-target="tab-members">Roster</a>
            <?php if ($state['is_member']): ?>
                <a href="#" class="tab-link" data-tab-target="tab-bank">Bank</a>
                <?php if ($perms['can_manage_apps'] || $perms['can_edit_profile']): ?>
                    <a href="#" class="tab-link" data-tab-target="tab-admin">Admin</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Tab Content -->
        <div id="tab-content">
            
            <!-- Overview Tab -->
            <div id="tab-overview" class="nested-tab-content active">
                <div class="mobile-card">
                    <div class="mobile-card-header"><h3>Description</h3></div>
                    <div class="mobile-card-content" style="display: block; line-height: 1.6; color: #e0e0e0;">
                        <?= nl2br(htmlspecialchars($alliance['description'] ?? 'No description provided.')) ?>
                    </div>
                </div>
                <?php if ($state['is_member']): ?>
                    <form action="/alliance/leave" method="POST" onsubmit="return confirm('Are you sure you want to leave this alliance?');" style="margin-top: 2rem;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <button type="submit" class="btn btn-danger" style="width: 100%;">Leave Alliance</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Roster Tab -->
            <div id="tab-members" class="nested-tab-content">
                <div class="leaderboard-list">
                    <?php foreach ($members as $member): ?>
                        <div class="mobile-card" style="padding: 0.75rem; display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <strong style="color: var(--mobile-text-primary); font-size: 1.1rem;">
                                    <a href="/profile/<?= $member['id'] ?>" style="color: inherit; text-decoration: none;"><?= htmlspecialchars($member['character_name']) ?></a>
                                </strong>
                                <div style="font-size: 0.8rem; color: var(--muted);"><?= htmlspecialchars($member['role_name']) ?></div>
                            </div>
                            <?php if ($state['is_member'] && $member['can_be_managed']): ?>
                                <div style="display: flex; gap: 0.5rem;">
                                    <form action="/alliance/kick/<?= $member['id'] ?>" method="POST" onsubmit="return confirm('Kick this member?');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" style="padding: 0.5rem;">Kick</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if ($state['is_member']): ?>
            <!-- Bank Tab -->
            <div id="tab-bank" class="nested-tab-content">
                <div class="mobile-card">
                    <div class="mobile-card-header"><h3>Donate</h3></div>
                    <div class="mobile-card-content" style="display: block;">
                        <form action="/alliance/donate" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <div class="form-group">
                                <input type="number" name="amount" class="mobile-input" placeholder="Amount" min="1" style="width: 100%; margin-bottom: 0.5rem;">
                                <button type="submit" class="btn btn-accent" style="width: 100%;">Donate Credits</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="mobile-card">
                    <div class="mobile-card-header"><h3>Recent Logs</h3></div>
                    <div class="mobile-card-content" style="display: block; max-height: 300px; overflow-y: auto;">
                        <?php foreach ($logs as $log): ?>
                            <div style="border-bottom: 1px solid var(--mobile-border); padding: 0.5rem 0; font-size: 0.85rem;">
                                <div style="display: flex; justify-content: space-between;">
                                    <span class="<?= $log['css_class'] ?>"><?= $log['formatted_amount'] ?></span>
                                    <span style="color: var(--muted);"><?= $log['date'] ?></span>
                                </div>
                                <div style="color: #ccc;"><?= htmlspecialchars($log['message']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Admin Tab -->
            <?php if ($perms['can_manage_apps'] || $perms['can_edit_profile']): ?>
            <div id="tab-admin" class="nested-tab-content">
                <?php if ($perms['can_edit_profile']): ?>
                    <div class="mobile-card">
                        <div class="mobile-card-header"><h3>Settings</h3></div>
                        <div class="mobile-card-content" style="display: block;">
                            <form action="/alliance/profile/edit" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" class="mobile-input" style="width: 100%; height: 100px;"><?= htmlspecialchars($alliance['description']) ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Avatar</label>
                                    <input type="file" name="profile_picture" class="mobile-input" style="width: 100%;">
                                </div>
                                <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">Save Changes</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($perms['can_manage_apps'] && !empty($applications)): ?>
                    <div class="mobile-card">
                        <div class="mobile-card-header"><h3>Pending Applications</h3></div>
                        <div class="mobile-card-content" style="display: block;">
                            <?php foreach ($applications as $app): ?>
                                <div style="padding: 0.5rem 0; border-bottom: 1px solid var(--mobile-border);">
                                    <strong><?= htmlspecialchars($app->character_name) ?></strong>
                                    <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                                        <form action="/alliance/accept-app/<?= $app->id ?>" method="POST" style="flex: 1;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                            <button type="submit" class="btn btn-sm btn-accent" style="width: 100%;">Accept</button>
                                        </form>
                                        <form action="/alliance/reject-app/<?= $app->id ?>" method="POST" style="flex: 1;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" style="width: 100%;">Reject</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>

        </div>
    </div> <!-- End nested-tabs-container -->
</div>
