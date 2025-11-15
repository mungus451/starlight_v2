<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\Alliance $alliance */
/* @var array $members */
/* @var \App\Models\Entities\User $viewer */
/* @var \App\Models\Entities\AllianceRole|null $viewerRole */
/* @var \App\Models\Entities\AllianceApplication[] $applications */
/* @var \App\Models\Entities\AllianceApplication|null $userApplication */
/* @var \App\Models\Entities\AllianceRole[] $roles */
?>

<style>
    :root {
        --card: radial-gradient(circle at 30% -10%, rgba(45, 209, 209, 0.07), rgba(13, 15, 27, 0.6));
        --border: rgba(255, 255, 255, 0.03);
        --accent: #2dd1d1;
        --accent-2: #f9c74f;
        --accent-red: #e53e3e;
        --accent-green: #4CAF50;
        --accent-blue: #7683f5;
        --text: #eff1ff;
        --muted: #a8afd4;
        --radius: 18px;
        --shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
    }

    /* --- Base Container --- */
    .alliance-container-full {
        width: 100%;
        max-width: 1400px;
        margin-inline: auto;
        padding: 0;
        position: relative;
    }

    /* --- Profile Header (from profile/show) --- */
    .profile-header-card {
        background: radial-gradient(circle at top, rgba(45, 209, 209, 0.12), rgba(11, 13, 24, 0.9));
        border: 1px solid rgba(45, 209, 209, 0.4);
        border-radius: var(--radius);
        padding: 1.5rem 2rem;
        box-shadow: var(--shadow);
        backdrop-filter: blur(6px);
        margin-bottom: 1.5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }
    .profile-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--accent);
        box-shadow: 0 0 30px rgba(45, 209, 209, 0.25);
    }
    .profile-avatar-svg {
        padding: 1.5rem;
        background: #1e1e3f;
        color: var(--muted);
    }
    .profile-header-card h1 {
        margin: 0.5rem 0 0 0;
        color: #fff;
        font-size: 2.2rem;
    }
    .profile-header-card .alliance-tag {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--accent-2);
        text-decoration: none;
    }

    /* --- Grid for Cards --- */
    .item-grid {
        display: grid;
        grid-template-columns: 1fr 2fr; /* 1:2 ratio */
        gap: 1.5rem;
    }
    .grid-col-span-2 {
        grid-column: 1 / -1; /* Span full width */
    }
    @media (max-width: 980px) {
        .item-grid {
            grid-template-columns: 1fr; /* Stack on mobile */
        }
    }

    /* --- Base Card (from Bank) --- */
    .item-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.25rem 1.5rem;
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
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
        margin-top: 0;
    }
    .btn-accept { background: var(--accent-green); }
    .btn-reject { background: var(--accent-red); }
    .btn-manage { background: var(--accent-blue); }

    /* --- List Styles (from Battle Reports) --- */
    .data-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .data-item {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        background: rgba(13, 15, 27, 0.7);
        padding: 1rem 1.25rem;
        border-radius: 12px;
        border: 1px solid var(--border);
    }
    .item-info .role {
        font-size: 0.9rem;
        color: var(--accent-2);
        font-weight: 600;
        display: block;
    }
    .item-info .name {
        font-size: 1.1rem;
        color: var(--text);
        font-weight: 500;
    }
    .item-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .item-actions .btn-submit, .item-actions select {
        margin-top: 0;
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
    }
    .item-actions select {
        height: 34px; /* Match button height */
    }

    /* --- Description/Message Text --- */
    .alliance-description {
        font-size: 1rem;
        color: var(--muted);
        line-height: 1.6;
        white-space: pre-wrap; /* Respects newlines */
    }
    .action-message {
        font-size: 1rem;
        color: var(--muted);
        line-height: 1.6;
        text-align: center;
    }
</style>

<div class="alliance-container-full">

    <div class="profile-header-card">
        <?php if ($alliance->profile_picture_url): ?>
            <img src="<?= htmlspecialchars($alliance->profile_picture_url) ?>" alt="Avatar" class="profile-avatar">
        <?php else: ?>
            <svg class="profile-avatar profile-avatar-svg" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
            </svg>
        <?php endif; ?>
        
        <h1><?= htmlspecialchars($alliance->name) ?></h1>
        <div class="alliance-tag">[<?= htmlspecialchars($alliance->tag) ?>]</div>
    </div>

    <div class="item-grid">
        
        <div class="item-card">
            <h4>Actions</h4>
            
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
                    <p class="action-message">Your application is pending.</p>
                    <form action="/alliance/cancel-app/<?= $userApplication->id ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                        <button type="submit" class="btn-submit btn-reject">Cancel Application</button>
                    </form>
                <?php endif; ?>

            <?php // Case 2: Viewer IS a member of THIS alliance
            elseif ($viewer->alliance_id === $alliance->id): ?>
            
                <?php // Case 2a: Viewer is the Leader
                if ($viewerRole && $viewerRole->name === 'Leader'): ?>
                    <p class="action-message">You are the leader of this alliance.</p>
                    <a href="/alliance/roles" class="btn-submit btn-manage">Manage Alliance Roles</a>
                    
                <?php // Case 2b: Viewer is a regular member
                else: ?>
                    <form action="/alliance/leave" method="POST" onsubmit="return confirm('Are you sure you want to leave this alliance?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                        <button type="submit" class="btn-submit btn-reject">Leave Alliance</button>
                    </form>
                <?php endif; ?>

            <?php // Case 3: Viewer is in a DIFFERENT alliance
            else: ?>
                <p class="action-message">You must leave your current alliance before you can join another.</p>
            <?php endif; ?>
        </div>
        
        <div class="item-card">
            <h4>Alliance Charter</h4>
            <div class="alliance-description">
                <?= !empty($alliance->description) ? htmlspecialchars($alliance->description) : 'This alliance has not set a description.' ?>
            </div>
        </div>

        <?php if ($viewerRole && $viewerRole->can_edit_profile): ?>
            <div class="item-card grid-col-span-2">
                <h4>Edit Alliance Profile</h4>
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
            <div class="item-card grid-col-span-2">
                <h4>Pending Applications (<?= count($applications) ?>)</h4>
                <ul class="data-list">
                    <?php foreach ($applications as $app): ?>
                        <li class="data-item">
                            <span class="item-info">
                                <span class="name"><?= htmlspecialchars($app->character_name) ?></span>
                            </span>
                            <div class="item-actions">
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

        <div class="item-card grid-col-span-2">
            <h4>Member Roster (<?= count($members) ?>)</h4>
            <ul class="data-list">
                <?php foreach ($members as $member): ?>
                    <li class="data-item">
                        <div class="item-info">
                            <span class="role"><?= htmlspecialchars($member['alliance_role_name'] ?? 'Role-less') ?></span>
                            <span class="name"><?= htmlspecialchars($member['character_name']) ?></span>
                        </div>
                        
                        <?php 
                        $canManage = $viewerRole && ($viewerRole->can_kick_members || $viewerRole->can_manage_roles);
                        $isNotSelf = $viewer->id !== $member['id'];
                        $isNotLeader = $member['alliance_role_name'] !== 'Leader';
                        
                        if ($canManage && $isNotSelf && $isNotLeader): 
                        ?>
                            <div class="item-actions">
                                <?php if ($viewerRole->can_manage_roles): ?>
                                    <form action="/alliance/role/assign" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                        <input type="hidden" name="target_user_id" value="<?= $member['id'] ?>">
                                        <select name="role_id" onchange="this.form.submit()" class="form-group">
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
</div>