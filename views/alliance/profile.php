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
</style>

<div class="profile-container">
    <div class="profile-header">
        <h1><?= htmlspecialchars($alliance->name) ?></h1>
        <div class="tag">[<?= htmlspecialchars($alliance->tag) ?>]</div>
    </div>

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