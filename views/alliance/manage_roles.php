<?php
use App\Core\Permissions;

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
                                <!-- Edit Role Form -->
                                <form action="/alliance/roles/delete/<?= $role->id ?>" method="POST" onsubmit="return confirm('Are you sure? Members in this role will be demoted to Recruit.');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                    <button type="submit" class="btn-submit btn-reject">Delete</button>
                                </form>
                            <?php else: ?>
                                <span style="color: var(--muted); font-size: 0.8rem; font-style: italic;">Default</span>
                            <?php endif; ?>
                        </div>
                    </li>
                    <li class="data-item">
                        <form action="/alliance/roles/update/<?= $role->id ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                            <div class="form-group">
                                <label for="role_name_<?= $role->id ?>">Role Name</label>
                                <input type="text" name="role_name" id="role_name_<?= $role->id ?>" value="<?= htmlspecialchars($role->name) ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Permissions:</label>
                                <div class="checkbox-grid">
                                    <?php foreach (Permissions::all() as $name => $bit): ?>
                                        <div class="checkbox-group">
                                            <label>
                                                <input type="checkbox" name="permissions[<?= $name ?>]" value="1" <?= $role->hasPermission($bit) ? 'checked' : '' ?>>
                                                <?= htmlspecialchars(ucwords(strtolower(str_replace('_', ' ', str_replace('CAN_', '', $name))))) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <button type="submit" class="btn-submit">Update Role</button>
                        </form>
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
                        <?php foreach (Permissions::all() as $name => $bit): ?>
                            <div class="checkbox-group">
                                <label>
                                    <input type="checkbox" name="permissions[<?= $name ?>]" value="1">
                                    <?= htmlspecialchars(ucwords(strtolower(str_replace('_', ' ', str_replace('CAN_', '', $name))))) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Create Role</button>
            </form>
        </div>
    </div>
</div>