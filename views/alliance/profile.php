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
        white-space: pre-wrap; /* Respects newlines in the description */
    }
    
    /* Member List */
    .member-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
    }
    .member-item {
        font-size: 1.1rem;
        color: #e0e0e0;
        padding: 0.5rem;
        background: #1e1e3f;
        border-radius: 5px;
    }
    .member-item .role {
        font-size: 0.9rem;
        color: #f9c74f;
        font-weight: bold;
        display: block;
    }
    
    /* Application List */
    .application-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .app-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #1e1e3f;
        padding: 0.75rem;
        border-radius: 5px;
    }
    .app-item-actions {
        display: flex;
        gap: 0.5rem;
    }
    .app-item-actions .btn-submit {
        margin-top: 0;
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
    }
    .btn-accept {
        background: #4CAF50; /* Green */
    }
    .btn-reject {
        background: #e53e3e; /* Red */
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
            if ($viewer->alliance_role === 'Leader'): ?>
                <p style="color: #c0c0e0; text-align: center;">You are the leader of this alliance.</p>
                <?php // Case 2b: Viewer is a regular member
            else: ?>
                <form action="/alliance/leave" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    <button type="submit" class="btn-submit btn-reject">Leave Alliance</button>
                </form>
            <?php endif; ?>

        <?php // Case 3: Viewer is in a DIFFERENT alliance
        else: ?>
            <p style="color: #c0c0e0; text-align: center;">You must leave your current alliance before you can join another.</p>
        <?php endif; ?>
    </div>
    
    <?php if ($viewer->id === $alliance->leader_id && !empty($applications)): ?>
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
                    <span class="role"><?= htmlspecialchars($member->alliance_role ?? 'Member') ?></span>
                    <?= htmlspecialchars($member->characterName) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>