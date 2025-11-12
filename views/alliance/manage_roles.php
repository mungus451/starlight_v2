<style>
    .roles-container {
        width: 100%;
        max-width: 800px;
        text-align: left;
    }
    .roles-container h1 {
        text-align: center;
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
    .roles-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .role-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #1e1e3f;
        padding: 1rem;
        border-radius: 5px;
    }
    .role-item-name {
        font-size: 1.2rem;
        font-weight: bold;
    }
    .role-item-actions {
        display: flex;
        gap: 0.5rem;
    }
    .role-item-actions .btn-submit {
        margin-top: 0;
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
    }
    .btn-reject { background: #e53e3e; }

    .permission-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    .permission-group label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
    }
    .permission-group input {
        width: 1.2rem;
        height: 1.2rem;
    }
</style>

<div class="roles-container">
    <h1>Manage Alliance Roles</h1>
    
    <a href="/alliance/profile/<?= $alliance_id ?>" class="btn-submit" style="text-align: center; display: block; margin-bottom: 1.5rem;">
        &laquo; Back to Alliance Profile
    </a>

    <div class="data-card">
        <h3>Current Roles</h3>
        <ul class="roles-list">
            <?php foreach ($roles as $role): ?>
                <li class="role-item">
                    <span class="role-item-name"><?= htmlspecialchars($role->name) ?></span>
                    <div class="role-item-actions">
                        <?php if ($role->name !== 'Leader' && $role->name !== 'Recruit' && $role->name !== 'Member'): ?>
                            <form action="/alliance/roles/delete/<?= $role->id ?>" method="POST" onsubmit="return confirm('Are you sure? Members in this role will be demoted to Recruit.');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                <button type="submit" class="btn-submit btn-reject">Delete</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <div class="data-card">
        <h3>Create New Role</h3>
        <form action="/alliance/roles/create" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            
            <div class="form-group">
                <label for="role_name">New Role Name</label>
                <input type="text" name="role_name" id="role_name" required>
            </div>

            <div class="form-group">
                <label>Permissions:</label>
                <div class="permission-grid">
                    <div class="permission-group">
                        <label>
                            <input type="checkbox" name="permissions[can_edit_profile]" value="1">
                            Can Edit Profile
                        </label>
                    </div>
                    <div class="permission-group">
                        <label>
                            <input type="checkbox" name="permissions[can_manage_applications]" value="1">
                            Can Manage Applications
                        </label>
                    </div>
                    <div class="permission-group">
                        <label>
                            <input type="checkbox" name="permissions[can_invite_members]" value="1">
                            Can Invite Members
                        </label>
                    </div>
                    <div class="permission-group">
                        <label>
                            <input type="checkbox" name="permissions[can_kick_members]" value="1">
                            Can Kick Members
                        </label>
                    </div>
                    <div class="permission-group">
                        <label>
                            <input type="checkbox" name="permissions[can_manage_roles]" value="1">
                            Can Manage Roles
                        </label>
                    </div>
                    <div class="permission-group">
                        <label>
                            <input type="checkbox" name="permissions[can_see_private_board]" value="1">
                            Can See Private Board
                        </label>
                    </div>
                    <div class="permission-group">
                        <label>
                            <input type="checkbox" name="permissions[can_manage_forum]" value="1">
                            Can Manage Forum
                        </label>
                    </div>
                    <div class="permission-group">
                        <label>
                            <input type="checkbox" name="permissions[can_manage_bank]" value="1">
                            Can Manage Bank
                        </label>
                    </div>
                    <div class="permission-group">
                        <label>
                            <input type="checkbox" name="permissions[can_manage_structures]" value="1">
                            Can Manage Structures
                        </label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit">Create Role</button>
        </form>
    </div>
</div>