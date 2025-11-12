<style>
    .profile-container {
        width: 100%;
        max-width: 800px;
        text-align: left;
    }
    .profile-header {
        text-align: center;
    }
    .profile-header h1 {
        margin-bottom: 0.5rem;
    }
    .profile-header .tag {
        font-size: 1.5rem;
        font-weight: bold;
        color: #f9c74f;
    }
    .data-card {
        background: #2a2a4a;
        border: 1px solid #3a3a5a;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .data-card h3 {
        color: #f9c74f;
        margin-top: 0;
        border-bottom: 1px solid #3a3a5a;
        padding-bottom: 0.5rem;
    }
    .alliance-description {
        font-size: 1rem;
        color: #c0c0e0;
        white-space: pre-wrap; /* Respects newlines */
    }
    
    /* Member List */
    .member-list, .application-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .member-item, .app-item {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        background: #1e1e3f;
        padding: 0.75rem 1rem;
        border-radius: 5px;
        border: 1px solid #3a3a5a;
    }
    .member-item-info .role {
        font-size: 0.9rem;
        color: #f9c74f;
        font-weight: bold;
        display: block;
    }
    .member-item-info .name {
        font-size: 1.1rem;
        color: #e0e0e0;
    }
    .member-item-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .member-item-actions .btn-submit, .app-item-actions .btn-submit {
        margin-top: 0;
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
    }
    .btn-accept { background: #4CAF50; }
    .btn-reject { background: #e53e3e; }
    .btn-manage { background: #7683f5; }
    
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
</style>

<div class="profile-container">
    <div class="profile-header">
        <h1><?= htmlspecialchars($alliance->name) ?></h1>
        <div class="tag">[<?= htmlspecialchars($alliance->tag) ?>]</div>
    </div>

    <div class="data-card">
        <h3>Actions</h3>
        
        <?php // Case 1: Viewer is NOT in any alliance
        if ($viewer->alliance_id === null): ?>
        
            <?php // Case 1a: Viewer has NOT applied
            if ($userApplication === null): ?>
                <form action="/alliance/apply/<?= $alliance->id ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    <button type="submit" class="btn-submit">Apply to Join</button>
                </form>
                
            <?php // Case 1b: Viewer HAS applied
            else: ?>
                <p style="color: #c0c0e0; text-align: center;">Your application is pending.</p>
                <form action="/alliance/cancel-app/<?= $userApplication->id ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    <button type="submit" class="btn-submit btn-reject">Cancel Application</button>
                </form>
            <?php endif; ?>

        <?php // Case 2: Viewer IS a member of THIS alliance
        elseif ($viewer->alliance_id === $alliance->id): ?>
        
            <?php // Case 2a: Viewer is the Leader
            if ($viewerRole && $viewerRole->name === 'Leader'): ?>
                <p style="color: #c0c0e0; text-align: center;">You are the leader of this alliance.</p>
                <a href="/alliance/roles" class="btn-submit btn-manage" style="display:block; text-align:center;">Manage Alliance Roles</a>
                
            <?php // Case 2b: Viewer is a regular member
            else: ?>
                <form action="/alliance/leave" method="POST" onsubmit="return confirm('Are you sure you want to leave this alliance?');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    <button type="submit" class="btn-submit btn-reject">Leave Alliance</button>
                </form>
            <?php endif; ?>

        <?php // Case 3: Viewer is in a DIFFERENT alliance
        else: ?>
            <p style="color: #c0c0e0; text-align: center;">You must leave your current alliance before you can join another.</p>
        <?php endif; ?>
    </div>
    
    <?php if ($viewerRole && $viewerRole->can_edit_profile): ?>
        <div class="data-card">
            <h3>Edit Alliance Profile</h3>
            <form action="/alliance/profile/edit" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                
                <div class="form-group">
                    <label for="description">Alliance Description</label>
                    <textarea name="description" id="description"><?= htmlspecialchars($alliance->description ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="profile_picture_url">Profile Picture URL</label>
                    <input type="text" name="profile_picture_url" id="profile_picture_url" value="<?= htmlspecialchars($alliance->profile_picture_url ?? '') ?>" placeholder="https://your.image.host/img.png">
                </div>
                
                <button type="submit" class="btn-submit">Save Changes</button>
            </form>
        </div>
    <?php endif; ?>
    
    <?php if ($viewerRole && $viewerRole->can_manage_applications && !empty($applications)): ?>
        <div class="data-card">
            <h3>Pending Applications (<?= count($applications) ?>)</h3>
            <ul class="application-list">
                <?php foreach ($applications as $app): ?>
                    <li class="app-item">
                        <span><?= htmlspecialchars($app->character_name) ?></span>
                        <div class="app-item-actions">
                            <form action="/alliance/accept-app/<?= $app->id ?>" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                <button type="submit" class="btn-submit btn-accept">Accept</button>
                            </form>
                            <form action="/alliance/reject-app/<?= $app->id ?>" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                <button type="submit" class="btn-submit btn-reject">Reject</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="data-card">
        <h3>Alliance Charter</h3>
        <div class="alliance-description">
            <?= !empty($alliance->description) ? htmlspecialchars($alliance->description) : 'This alliance has not set a description.' ?>
        </div>
    </div>

    <div class="data-card">
        <h3>Member Roster (<?= count($members) ?>)</h3>
        <ul class="member-list">
            <?php foreach ($members as $member): ?>
                <li class="member-item">
                    <div class="member-item-info">
                        <span class="role"><?= htmlspecialchars($member['alliance_role_name'] ?? 'Role-less') ?></span>
                        <span class="name"><?= htmlspecialchars($member['character_name']) ?></span>
                    </div>
                    
                    <?php 
                    $canManage = $viewerRole && ($viewerRole->can_kick_members || $viewerRole->can_manage_roles);
                    $isNotSelf = $viewer->id !== $member['id'];
                    $isNotLeader = $member['alliance_role_name'] !== 'Leader';
                    
                    if ($canManage && $isNotSelf && $isNotLeader): 
                    ?>
                        <div class="member-item-actions">
                            <?php if ($viewerRole->can_manage_roles): ?>
                                <form action="/alliance/role/assign" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                    <input type="hidden" name="target_user_id" value="<?= $member['id'] ?>">
                                    <select name="role_id" onchange="this.form.submit()">
                                        <option value="">Assign Role...</option>
                                        <?php foreach ($roles as $role): 
                                            if ($role->name === 'Leader') continue; // Can't assign Leader
                                        ?>
                                            <option value="<?= $role->id ?>" <?= $role->id == $member['alliance_role_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($role->name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            <?php endif; ?>
                            
                            <?php if ($viewerRole->can_kick_members): ?>
                                <form action="/alliance/kick/<?= $member['id'] ?>" method="POST" onsubmit="return confirm('Are you sure you want to kick this member?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                    <button type="submit" class="btn-submit btn-reject">Kick</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>