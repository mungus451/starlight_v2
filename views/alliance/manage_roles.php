<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\AllianceRole[] $roles */
/* @var int $alliance_id */
?>

<div class="container-full">
    <h1>Manage Alliance Roles</h1>
    
    <a href="/alliance/profile/<?= $alliance_id ?>" class="btn-submit" style="max-width: 400px; margin: 0 auto 1.5rem auto;">
        &laquo; Back to Alliance Profile
    </a>

    <div class="item-grid">
        <!-- Left Column: Existing Roles List -->
        <div class="item-card">
            <h4>Current Roles</h4>
            <ul class="data-list">
                <?php foreach ($roles as $role): ?>
                    <li class="data-item">
                        <span class="name" style="font-size: 1rem;"><?= htmlspecialchars($role->name) ?></span>
                        
                        <div class="item-actions">
                            <?php if ($role->name !== 'Leader' && $role->name !== 'Recruit' && $role->name !== 'Member'): ?>
                                <!-- Edit Role Form (Simplified: in a real app, this might be a modal or separate page) -->
                                <form action="/alliance/roles/delete/<?= $role->id ?>" method="POST" onsubmit="return confirm('Are you sure? Members in this role will be demoted to Recruit.');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                    <button type="submit" class="btn-submit btn-reject">Delete</button>
                                </form>
                            <?php else: ?>
                                <span style="color: var(--muted); font-size: 0.8rem; font-style: italic;">Default</span>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <!-- Right Column: Create Role Form -->
        <div class="item-card">
            <h4>Create New Role</h4>
            <form action="/alliance/roles/create" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                
                <div class="form-group">
                    <label for="role_name">New Role Name</label>
                    <input type="text" name="role_name" id="role_name" required placeholder="e.g., War General">
                </div>

                <div class="form-group">
                    <label>Permissions:</label>
                    <div class="checkbox-grid">
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" name="permissions[can_edit_profile]" value="1">
                                Edit Profile
                            </label>
                        </div>
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" name="permissions[can_manage_applications]" value="1">
                                Manage Apps
                            </label>
                        </div>
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" name="permissions[can_invite_members]" value="1">
                                Invite Members
                            </label>
                        </div>
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" name="permissions[can_kick_members]" value="1">
                                Kick Members
                            </label>
                        </div>
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" name="permissions[can_manage_roles]" value="1">
                                Manage Roles
                            </label>
                        </div>
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" name="permissions[can_see_private_board]" value="1">
                                View Private Board
                            </label>
                        </div>
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" name="permissions[can_manage_forum]" value="1">
                                Manage Forum
                            </label>
                        </div>
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" name="permissions[can_manage_bank]" value="1">
                                Manage Bank
                            </label>
                        </div>
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" name="permissions[can_manage_structures]" value="1">
                                Manage Structures
                            </label>
                        </div>
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" name="permissions[can_manage_diplomacy]" value="1">
                                Diplomacy
                            </label>
                        </div>
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" name="permissions[can_declare_war]" value="1">
                                Declare War
                            </label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Create Role</button>
            </form>
        </div>
    </div>
</div>