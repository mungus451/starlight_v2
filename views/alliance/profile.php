<?php
// --- Helper variables from AllianceProfilePresenter ---
/* @var array $alliance (Formatted Data) */
/* @var array $state (User State Flags) */
/* @var array $perms (User Permission Flags) */
/* @var array $members (Formatted Roster) */
/* @var array $logs (Formatted Bank Logs) */
/* @var array $loans (Categorized Loans) */
/* @var \App\Models\Entities\AllianceRole[] $roles */
/* @var \App\Models\Entities\AllianceApplication[] $applications */
/* @var string $csrf_token */
?>

<div class="container-full">

    <!-- Header Banner -->
    <div class="player-header" style="justify-content: center; flex-direction: column; text-align: center;">
        <?php if ($alliance['profile_picture_url']): ?>
            <!-- Updated to use the new file route -->
            <img src="/serve/alliance_avatar/<?= htmlspecialchars($alliance['profile_picture_url']) ?>" alt="Alliance Logo" class="player-avatar" style="width: 120px; height: 120px;">
        <?php else: ?>
            <svg class="player-avatar player-avatar-svg" style="width: 120px; height: 120px;" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
            </svg>
        <?php endif; ?>
        
        <h1 style="margin-bottom: 0.5rem;"><?= htmlspecialchars($alliance['name']) ?></h1>
        <div style="font-size: 1.2rem; font-weight: 700; color: var(--accent-2);">[<?= htmlspecialchars($alliance['tag']) ?>]</div>
    </div>

    <!-- Main Split Layout (1/3 Left, 2/3 Right) -->
    <div class="split-grid">
        
        <!-- Left Column: Actions, Charter, Treasury -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            
            <div class="item-card">
                <h4>Actions</h4>
                
                <?php /* Case 1: Member */ ?>
                <?php if ($state['is_member']): ?>
                    
                    <?php if ($state['is_leader']): ?>
                        <p class="form-note" style="text-align: center;">You are the leader of this alliance.</p>
                        <a href="/alliance/forum" class="btn-submit btn-accent">Alliance Forum</a>
                        <a href="/alliance/roles" class="btn-submit">Manage Roles</a>
                        <a href="/alliance/structures" class="btn-submit">Manage Structures</a>
                        <a href="/alliance/diplomacy" class="btn-submit">Diplomacy</a>
                        <a href="/alliance/war" class="btn-submit btn-reject">War Room</a>
                    <?php else: ?>
                        <form action="/alliance/leave" method="POST" onsubmit="return confirm('Are you sure you want to leave this alliance?');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                            <button type="submit" class="btn-submit btn-reject">Leave Alliance</button>
                        </form>
                        
                        <div class="divider"></div>
                        
                        <a href="/alliance/forum" class="btn-submit btn-accent">Alliance Forum</a>
                        
                        <?php if ($perms['can_manage_structures']): ?>
                             <a href="/alliance/structures" class="btn-submit">Manage Structures</a>
                        <?php endif; ?>
                        <?php if ($perms['can_manage_diplomacy']): ?>
                             <a href="/alliance/diplomacy" class="btn-submit">Diplomacy</a>
                        <?php endif; ?>
                        <?php if ($perms['can_declare_war']): ?>
                             <a href="/alliance/war" class="btn-submit btn-reject">War Room</a>
                        <?php endif; ?>
                    <?php endif; ?>

                <?php /* Case 2: Pending Application */ ?>
                <?php elseif ($state['has_applied']): ?>
                    <p class="form-note" style="text-align: center;">Your application is pending.</p>
                    <form action="/alliance/cancel-app/<?= $state['application_id'] ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                        <button type="submit" class="btn-submit btn-reject">Cancel Application</button>
                    </form>

                <?php /* Case 3: Non-Member */ ?>
                <?php else: ?>
                    <?php if ($state['viewer_id']): // Only show if logged in ?>
                        <form action="/alliance/apply/<?= $alliance['id'] ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                            <button type="submit" class="btn-submit <?= $alliance['is_joinable'] ? 'btn-accept' : '' ?>">
                                <?= $alliance['is_joinable'] ? 'Join Alliance (Open)' : 'Apply to Join' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <div class="item-card">
                <h4>Alliance Charter</h4>
                <div style="font-size: 0.95rem; color: var(--muted); line-height: 1.6; white-space: pre-wrap;">
                    <?= !empty($alliance['description']) ? htmlspecialchars($alliance['description']) : 'This alliance has not set a description.' ?>
                </div>
                
                <div class="divider"></div>
                
                <p class="form-note" style="text-align: center; margin-bottom: 0;">
                    Recruitment: 
                    <strong style="color: <?= $alliance['recruitment_status_color'] ?>">
                        <?= $alliance['recruitment_status_text'] ?>
                    </strong>
                </p>
            </div>

            <?php if ($state['is_member']): ?>
                <div class="item-card">
                    <h4>Treasury</h4>
                    <div class="info-box" style="margin-bottom: 1.5rem;">
                        <span style="display: block; font-size: 0.9rem; margin-bottom: 0.25rem;">Balance</span>
                        <strong style="font-size: 1.8rem; color: var(--accent-2);"><?= $alliance['bank_credits'] ?></strong> 
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

            <?php if ($perms['can_edit_profile']): ?>
                <div class="item-card">
                    <h4>Edit Profile</h4>
                    <!-- Added enctype -->
                    <form action="/alliance/profile/edit" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" style="min-height: 80px;"><?= htmlspecialchars($alliance['description'] ?? '') ?></textarea>
                        </div>
                        
                        <!-- NEW: File Upload Section -->
                        <div class="form-group">
                            <label>Alliance Logo (Max 2MB)</label>
                            <div class="pfp-preview-container">
                                <?php if ($alliance['profile_picture_url']): ?>
                                    <img src="/serve/alliance_avatar/<?= htmlspecialchars($alliance['profile_picture_url']) ?>" alt="Logo Preview" class="pfp-preview">
                                <?php else: ?>
                                    <svg class="pfp-preview" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" style="padding: 1.25rem; color: #a8afd4;">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                    </svg>
                                <?php endif; ?>
                                
                                <div class="pfp-upload-group">
                                    <input type="file" name="profile_picture" id="profile_picture" accept="image/jpeg,image/png,image/gif,image/webp,image/avif">
                                    
                                    <?php if ($alliance['profile_picture_url']): ?>
                                    <div class="remove-pfp-group">
                                        <input type="checkbox" name="remove_picture" id="remove_picture" value="1">
                                        <label for="remove_picture">Remove current logo</label>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="remove-pfp-group" style="margin-top: 1rem;">
                            <input type="hidden" name="is_joinable" value="0">
                            <input type="checkbox" name="is_joinable" id="is_joinable" value="1" <?= $alliance['is_joinable'] ? 'checked' : '' ?>>
                            <label for="is_joinable">Open Recruitment (Anyone can join instantly)</label>
                        </div>
                        
                        <button type="submit" class="btn-submit">Save Changes</button>
                    </form>
                </div>
            <?php endif; ?>
            
            <?php if ($state['is_member']): ?>
                <!-- Bank Logs -->
                <div class="item-card">
                    <h4>Bank Logs</h4>
                    <ul class="scrollable-list">
                        <?php if (empty($logs)): ?>
                            <li style="text-align: center; color: var(--muted); padding: 1rem;">No transactions yet.</li>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <li class="log-item">
                                    <div>
                                        <span style="font-size: 0.8rem; color: var(--muted); display: block; margin-bottom: 0.2rem;">
                                            <?= $log['date'] ?>
                                        </span>
                                        <?= htmlspecialchars($log['message']) ?>
                                    </div>
                                    <span class="log-amount <?= $log['css_class'] ?>">
                                        <?= $log['formatted_amount'] ?>
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

                    <!-- Pending Loans -->
                    <?php if (!empty($loans['pending'])): ?>
                        <h5 style="color: var(--muted); margin: 1rem 0 0.5rem;">Pending</h5>
                        <ul class="scrollable-list" style="max-height: 200px;">
                            <?php foreach ($loans['pending'] as $loan): ?>
                                <li class="log-item">
                                    <div>
                                        <strong><?= htmlspecialchars($loan['character_name']) ?></strong>
                                        <span style="display: block; font-size: 0.8rem; color: var(--muted);">Requested: <?= $loan['date'] ?></span>
                                    </div>
                                    <div style="text-align: right;">
                                        <span style="display: block; font-weight: 700;"><?= $loan['amount_requested'] ?></span>
                                        <?php if ($perms['can_manage_bank']): ?>
                                            <div class="item-actions" style="justify-content: flex-end; margin-top: 0.25rem;">
                                                <form action="/alliance/loan/approve/<?= $loan['id'] ?>" method="POST" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                                    <button class="btn-submit btn-accept" style="padding: 0.2rem 0.5rem; font-size: 0.8rem;">✓</button>
                                                </form>
                                                <form action="/alliance/loan/deny/<?= $loan['id'] ?>" method="POST" style="display:inline;">
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
                    
                    <!-- Active Loans -->
                    <?php if (!empty($loans['active'])): ?>
                        <h5 style="color: var(--accent-blue); margin: 1rem 0 0.5rem;">Active</h5>
                        <ul class="scrollable-list" style="max-height: 250px;">
                            <?php foreach ($loans['active'] as $loan): ?>
                                <li class="log-item" style="display: block;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                        <strong><?= htmlspecialchars($loan['character_name']) ?></strong>
                                        <strong style="color: var(--accent-blue);"><?= $loan['amount_to_repay'] ?> Owed</strong>
                                    </div>
                                    <?php if ($loan['is_my_loan']): ?>
                                        <form action="/alliance/loan/repay/<?= $loan['id'] ?>" method="POST" style="display: flex; gap: 0.5rem;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                            <div class="amount-input-group" style="flex-grow: 1;">
                                                <input type="text" id="repay-amount-display-<?= $loan['id'] ?>" class="formatted-amount repay-amount-display" placeholder="Repay" required>
                                                <input type="hidden" name="amount" id="repay-amount-hidden-<?= $loan['id'] ?>" class="repay-amount-hidden" value="0">
                                                <button type="button" class="btn-submit btn-accent btn-max-repay" data-loan-id="<?= $loan['id'] ?>" data-max-repay="<?= $loan['raw_amount_to_repay'] ?>">Max</button>
                                            </div>
                                            <button type="submit" class="btn-submit" style="width: auto; margin: 0;">Pay</button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($state['is_leader']): ?>
                                        <form action="/alliance/loan/forgive/<?= $loan['id'] ?>" method="POST" onsubmit="return confirm('Are you sure you want to forgive this loan? This action cannot be undone.');" style="margin-top: 0.5rem; text-align: right;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                            <button class="btn-submit btn-reject" style="padding: 0.2rem 0.5rem; font-size: 0.8rem; width: auto;">Forgive Loan</button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($perms['can_manage_apps'] && !empty($applications)): ?>
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
                                <span class="role"><?= htmlspecialchars($member['role_name']) ?></span>
                                <span class="name"><?= htmlspecialchars($member['character_name']) ?></span>
                            </div>
                            
                            <?php if ($member['can_be_managed']): ?>
                                <div class="item-actions">
                                    <?php if ($perms['can_manage_roles']): ?>
                                        <form action="/alliance/role/assign" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                            <input type="hidden" name="target_user_id" value="<?= $member['id'] ?>">
                                            <select name="role_id" onchange="this.form.submit()">
                                                <option value="">Role...</option>
                                                <?php foreach ($roles as $role): 
                                                    if ($role->name === 'Leader') continue; 
                                                ?>
                                                    <option value="<?= $role->id ?>" <?= $role->id == $member['role_id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($role->name) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($perms['can_kick']): ?>
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