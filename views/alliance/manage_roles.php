<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\AllianceRole[] $roles */
/* @var int $alliance_id */
?>

<style>
    :root {
        --card: radial-gradient(circle at 30% -10%, rgba(45, 209, 209, 0.07), rgba(13, 15, 27, 0.6));
        --border: rgba(255, 255, 255, 0.03);
        --accent: #2dd1d1;
        --accent-2: #f9c74f;
        --accent-red: #e53e3e;
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

    .alliance-container-full h1 {
        text-align: center;
        margin-bottom: 2rem;
        font-size: clamp(2.1rem, 3vw, 2.6rem);
        letter-spacing: -0.03em;
        color: #fff;
        padding-top: 1.5rem;
    }
    
    .alliance-container-full .btn-submit {
        display: block;
        width: 100%;
        max-width: 400px;
        margin: 0 auto 1.5rem auto;
        text-align: center;
    }

    /* --- Grid for Action Cards (from Bank) --- */
    .item-grid {
        display: grid;
        grid-template-columns: 1fr 1fr; /* 2 cards on desktop */
        gap: 1.5rem;
        max-width: 1000px; /* Constrain the forms */
        margin: 0 auto; /* Center the form grid */
    }
    
    @media (max-width: 980px) {
        .item-grid {
            grid-template-columns: 1fr; /* 1 card on mobile */
        }
    }

    /* --- Action Card (from Bank) --- */
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
        margin-top: 0.5rem;
    }
    .btn-reject {
        background: var(--accent-red);
    }
    
    /* --- Role List (from Battle Reports) --- */
    .roles-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .role-item {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        background: rgba(13, 15, 27, 0.7);
        padding: 1rem 1.25rem;
        border-radius: 12px;
        border: 1px solid var(--border);
    }
    .role-item-name {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text);
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

    /* --- Permission Grid --- */
    .permission-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    .permission-group label {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        cursor: pointer;
        font-size: 0.9rem;
        color: var(--muted);
    }
    .permission-group label:hover {
        color: var(--text);
    }
    .permission-group input {
        width: 1.1rem;
        height: 1.1rem;
    }
    @media (max-width: 600px) {
        .permission-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="alliance-container-full">
    <h1>Manage Alliance Roles</h1>
    
    <a href="/alliance/profile/<?= $alliance_id ?>" class="btn-submit">
        &laquo; Back to Alliance Profile
    </a>

    <div class="item-grid">
        <div class="item-card">
            <h4>Current Roles</h4>
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
        
        <div class="item-card">
            <h4>Create New Role</h4>
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
</div>