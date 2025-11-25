<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\Alliance $alliance */
/* @var array $members */
/* @var \App\Models\Entities\User $viewer */
/* @var \App\Models\Entities\AllianceRole|null $viewerRole */
/* @var \App\Models\Entities\AllianceApplication[] $applications */
/* @var \App\Models\Entities\AllianceApplication|null $userApplication */
/* @var \App\Models\Entities\AllianceRole[] $roles */
/* @var \App\Models\Entities\AllianceBankLog[] $bankLogs */
/* @var \App\Models\Entities\AllianceLoan[] $pendingLoans */
/* @var \App\Models\Entities\AllianceLoan[] $activeLoans */
/* @var \App\Models\Entities\AllianceLoan[] $historicalLoans */

// --- Checks ---
$isMember = ($viewer->alliance_id === $alliance->id);
$canManageBank = ($viewerRole && $viewerRole->can_manage_bank);
?>

<div class="container-full">

    <!-- Header Banner -->
    <div class="player-header" style="justify-content: center; flex-direction: column; text-align: center;">
        <?php if ($alliance->profile_picture_url): ?>
            <img src="<?= htmlspecialchars($alliance->profile_picture_url) ?>" alt="Avatar" class="player-avatar" style="width: 120px; height: 120px;">
        <?php else: ?>
            <svg class="player-avatar player-avatar-svg" style="width: 120px; height: 120px;" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
            </svg>
        <?php endif; ?>
        
        <h1 style="margin-bottom: 0.5rem;"><?= htmlspecialchars($alliance->name) ?></h1>
        <div style="font-size: 1.2rem; font-weight: 700; color: var(--accent-2);">[<?= htmlspecialchars($alliance->tag) ?>]</div>
    </div>

    <!-- Main Split Layout (1/3 Left, 2/3 Right) -->
    <div class="split-grid">
        
        <!-- Left Column: Actions, Charter, Treasury -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            
            <div class="item-card">
                <h4>Actions</h4>
                
                <?php // Case 1: Viewer is NOT in any alliance
                if ($viewer->alliance_id === null): ?>
                
                    <?php // Case 1a: Viewer has NOT applied
                    if ($userApplication === null): ?>
                        <form action="/alliance/apply/<?= $alliance->id ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                            <button type="submit" class="btn-submit <?= $alliance->is_joinable ? 'btn-accept' : '' ?>">
                                <?= $alliance->is_joinable ? 'Join Alliance (Open)' : 'Apply to Join' ?>
                            </button>
                        </form>
                        
                    <?php // Case 1b: Viewer HAS applied
                    else: ?>
                        <p class="form-note" style="text-align: center;">Your application is pending.</p>
                        <form action="/alliance/cancel-app/<?= $userApplication->id ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                            <button type="submit" class="btn-submit btn-reject">Cancel Application</button>
                        </form>
                    <?php endif; ?>

                <?php // Case 2: Viewer IS a member of THIS alliance
                elseif ($isMember): ?>
                
                    <?php // Case 2a: Viewer is the Leader
                    if ($viewerRole && $viewerRole->name === 'Leader'): ?>
                        <p class="form-note" style="text-align: center;">You are the leader of this alliance.</p>
                        <a href="/alliance/forum" class="btn-submit btn-accent">Alliance Forum</a>
                        <a href="/alliance/roles" class="btn-submit">Manage Roles</a>
                        <a href="/alliance/structures" class="btn-submit">Manage Structures</a>
                        <a href="/alliance/diplomacy" class="btn-submit">Diplomacy</a>
                        <a href="/alliance/war" class="btn-submit btn-reject">War Room</a>
                        
                    <?php // Case 2b: Viewer is a regular member
                    else: ?>
                        <form action="/alliance/leave" method="POST" onsubmit="return confirm('Are you sure you want to leave this alliance?');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                            <button type="submit" class="btn-submit btn-reject">Leave Alliance</button>
                        </form>
                        
                        <div class="divider"></div>
                        
                        <a href="/alliance/forum" class="btn-submit btn-accent">Alliance Forum</a>
                        
                        <?php if ($viewerRole && $viewerRole->can_manage_structures): ?>
                             <a href="/alliance/structures" class="btn-submit">Manage Structures</a>
                        <?php endif; ?>
                        <?php if ($viewerRole && $viewerRole->can_manage_diplomacy): ?>
                             <a href="/alliance/diplomacy" class="btn-submit">Diplomacy</a>
                        <?php endif; ?>
                        <?php if ($viewerRole && $viewerRole->can_declare_war): ?>
                             <a href="/alliance/war" class="btn-submit btn-reject">War Room</a>
                        <?php endif; ?>
                    <?php endif; ?>

                <?php // Case 3: Viewer is in a DIFFERENT alliance
                else: ?>
                    <p class="form-note" style="text-align: center;">You must leave your current alliance before you can join another.</p>
                <?php endif; ?>
            </div>
            
            <div class="item-card">
                <h4>Alliance Charter</h4>
                <div style="font-size: 0.95rem; color: var(--muted); line-height: 1.6; white-space: pre-wrap;">
                    <?= !empty($alliance->description) ? htmlspecialchars($alliance->description) : 'This alliance has not set a description.' ?>
                </div>
                
                <div class="divider"></div>
                
                <p class="form-note" style="text-align: center; margin-bottom: 0;">
                    Recruitment: 
                    <strong style="color: <?= $alliance->is_joinable ? 'var(--accent-green)' : 'var(--accent-blue)' ?>">
                        <?= $alliance->is_joinable ? 'Open' : 'Application Only' ?>
                    </strong>
                </p>
            </div>

            <?php if ($isMember): // Treasury Section ?>
                <div class="item-card">
                    <h4>Treasury</h4>
                    <div class="info-box" style="margin-bottom: 1.5rem;">
                        <span style="display: block; font-size: 0.9rem; margin-bottom: 0.25rem;">Balance</span>
                        <strong style="font-size: 1.8rem; color: var(--accent-2);"><?= number_format($alliance->bank_credits) ?></strong> 
                        <span style="color: var(--muted);">Credits</span>
                    </div>
                    
                    <form action="/alliance/donate" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                        <div class="form-group">
                            <div class="amount-input-group">
                                <input type="text" id="donate-amount-display" class="formatted-amount" placeholder="Amount" required>
                                <input type="hidden" name="amount" id="donate-amount-hidden" value="0">
                                <button type="button" class="btn-submit btn-accent" id="btn-max-donate">Max</button>
                            </div>
                        </div>
                        <button type="submit" class="btn-submit">Donate</button>
                    </form>
                </div>
            <?php endif; ?>

        </div>
        
        <!-- Right Column: Edits, Logs, Roster -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">

            <?php if ($viewerRole && $viewerRole->can_edit_profile): ?>
                <div class="item-card">
                    <h4>Edit Profile</h4>
                    <form action="/alliance/profile/edit" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" style="min-height: 80px;"><?= htmlspecialchars($alliance->description ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="profile_picture_url">Image URL</label>
                            <input type="text" name="profile_picture_url" id="profile_picture_url" value="<?= htmlspecialchars($alliance->profile_picture_url ?? '') ?>">
                        </div>
                        
                        <div class="remove-pfp-group">
                            <input type="hidden" name="is_joinable" value="0">
                            <input type="checkbox" name="is_joinable" id="is_joinable" value="1" <?= $alliance->is_joinable ? 'checked' : '' ?>>
                            <label for="is_joinable">Open Recruitment</label>
                        </div>
                        
                        <button type="submit" class="btn-submit">Save Changes</button>
                    </form>
                </div>
            <?php endif; ?>
            
            <?php if ($isMember): ?>
                <!-- Bank Logs -->
                <div class="item-card">
                    <h4>Bank Logs</h4>
                    <ul class="scrollable-list">
                        <?php if (empty($bankLogs)): ?>
                            <li style="text-align: center; color: var(--muted); padding: 1rem;">No transactions yet.</li>
                        <?php else: ?>
                            <?php foreach ($bankLogs as $log): ?>
                                <li class="log-item">
                                    <div>
                                        <span style="font-size: 0.8rem; color: var(--muted); display: block; margin-bottom: 0.2rem;">
                                            <?= (new DateTime($log->created_at))->format('M d - H:i') ?>
                                        </span>
                                        <?= htmlspecialchars($log->message) ?>
                                    </div>
                                    <span class="log-amount <?= $log->amount >= 0 ? 'positive' : 'negative' ?>">
                                        <?= $log->amount >= 0 ? '+' : '' ?><?= number_format($log->amount) ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Loans -->
                <div class="item-card">
                    <h4>Loans</h4>
                    
                    <!-- Request Form -->
                    <div style="background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 12px; border: 1px solid var(--border); margin-bottom: 1rem;">
                        <h5 style="margin: 0 0 0.5rem 0;">Request Loan</h5>
                        <form action="/alliance/loan/request" method="POST" style="flex-direction: row; align-items: flex-end;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                            <div class="form-group" style="flex-grow: 1;">
                                <input type="text" id="loan-request-display" class="formatted-amount" placeholder="Amount" required>
                                <input type="hidden" name="amount" id="loan-request-hidden" value="0">
                            </div>
                            <button type="submit" class="btn-submit" style="margin: 0; width: auto;">Request</button>
                        </form>
                    </div>

                    <!-- Pending Lists -->
                    <?php if (!empty($pendingLoans)): ?>
                        <h5 style="color: var(--muted); margin: 1rem 0 0.5rem;">Pending</h5>
                        <ul class="scrollable-list" style="max-height: 200px;">
                            <?php foreach ($pendingLoans as $loan): ?>
                                <li class="log-item">
                                    <div>
                                        <strong><?= htmlspecialchars($loan->character_name) ?></strong>
                                        <span style="display: block; font-size: 0.8rem; color: var(--muted);">Requested: <?= (new DateTime($loan->created_at))->format('M d') ?></span>
                                    </div>
                                    <div style="text-align: right;">
                                        <span style="display: block; font-weight: 700;"><?= number_format($loan->amount_requested) ?></span>
                                        <?php if ($canManageBank): ?>
                                            <div class="item-actions" style="justify-content: flex-end; margin-top: 0.25rem;">
                                                <form action="/alliance/loan/approve/<?= $loan->id ?>" method="POST" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                                    <button class="btn-submit btn-accept" style="padding: 0.2rem 0.5rem; font-size: 0.8rem;">✓</button>
                                                </form>
                                                <form action="/alliance/loan/deny/<?= $loan->id ?>" method="POST" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                                    <button class="btn-submit btn-reject" style="padding: 0.2rem 0.5rem; font-size: 0.8rem;">✕</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    
                    <!-- Active Lists -->
                    <?php if (!empty($activeLoans)): ?>
                        <h5 style="color: var(--accent-blue); margin: 1rem 0 0.5rem;">Active</h5>
                        <ul class="scrollable-list" style="max-height: 250px;">
                            <?php foreach ($activeLoans as $loan): ?>
                                <li class="log-item" style="display: block;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                        <strong><?= htmlspecialchars($loan->character_name) ?></strong>
                                        <strong style="color: var(--accent-blue);"><?= number_format($loan->amount_to_repay) ?> Owed</strong>
                                    </div>
                                    <?php if ($loan->user_id === $viewer->id): ?>
                                        <form action="/alliance/loan/repay/<?= $loan->id ?>" method="POST" style="display: flex; gap: 0.5rem;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                            <div class="amount-input-group" style="flex-grow: 1;">
                                                <input type="text" id="repay-amount-display-<?= $loan->id ?>" class="formatted-amount repay-amount-display" placeholder="Repay" required>
                                                <input type="hidden" name="amount" id="repay-amount-hidden-<?= $loan->id ?>" class="repay-amount-hidden" value="0">
                                                <button type="button" class="btn-submit btn-accent btn-max-repay" data-loan-id="<?= $loan->id ?>" data-max-repay="<?= $loan->amount_to_repay ?>">Max</button>
                                            </div>
                                            <button type="submit" class="btn-submit" style="width: auto; margin: 0;">Pay</button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($viewerRole && $viewerRole->can_manage_applications && !empty($applications)): ?>
                <div class="item-card">
                    <h4>Pending Applications</h4>
                    <ul class="data-list">
                        <?php foreach ($applications as $app): ?>
                            <li class="data-item">
                                <span class="name"><?= htmlspecialchars($app->character_name) ?></span>
                                <div class="item-actions">
                                    <form action="/alliance/accept-app/<?= $app->id ?>" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                        <button class="btn-submit btn-accept">Accept</button>
                                    </form>
                                    <form action="/alliance/reject-app/<?= $app->id ?>" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                        <button class="btn-submit btn-reject">Reject</button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Member Roster -->
            <div class="item-card">
                <h4>Roster (<?= count($members) ?>)</h4>
                <ul class="data-list">
                    <?php foreach ($members as $member): ?>
                        <li class="data-item">
                            <div class="item-info">
                                <span class="role"><?= htmlspecialchars($member['alliance_role_name'] ?? 'None') ?></span>
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
                                            <select name="role_id" onchange="this.form.submit()">
                                                <option value="">Role...</option>
                                                <?php foreach ($roles as $role): 
                                                    if ($role->name === 'Leader') continue; 
                                                ?>
                                                    <option value="<?= $role->id ?>" <?= $role->id == $member['alliance_role_id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($role->name) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($viewerRole->can_kick_members): ?>
                                        <form action="/alliance/kick/<?= $member['id'] ?>" method="POST" onsubmit="return confirm('Kick this member?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                            <button class="btn-submit btn-reject">Kick</button>
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
</div>

<script src="/js/alliance_profile.js"></script>