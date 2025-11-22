<h1>Your Notifications</h1>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <p style="margin: 0;">
        Stay updated on attacks, alliance invites, and system messages.
    </p>
    
    <form action="/notifications/read-all" method="POST" style="margin: 0;">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <button type="submit" class="btn-submit" style="padding: 0.5rem 1rem; font-size: 0.9rem;">Mark All Read</button>
    </form>
</div>

<?php if (empty($notifications)): ?>
    <div style="text-align: center; padding: 2rem; color: #a0a0a0;">
        <p>You have no notifications.</p>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="game-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Type</th>
                    <th style="width: 50%;">Message</th>
                    <th style="width: 20%;">Date</th>
                    <th style="width: 15%;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notifications as $notif): ?>
                    <tr class="<?= $notif->is_read ? 'read-row' : 'unread-row' ?>">
                        <td>
                            <span class="badge badge-<?= htmlspecialchars($notif->type) ?>">
                                <?php 
                                switch($notif->type) {
                                    case 'attack_alert': echo 'COMBAT'; break;
                                    case 'alliance_invite': echo 'ALLIANCE'; break;
                                    case 'system': echo 'SYSTEM'; break;
                                    default: echo 'INFO';
                                }
                                ?>
                            </span>
                        </td>
                        <td>
                            <?= htmlspecialchars($notif->message) ?>
                        </td>
                        <td style="color: #a0a0a0; font-size: 0.9rem;">
                            <?= date('M j, H:i', strtotime($notif->created_at)) ?>
                        </td>
                        <td>
                            <?php if (!$notif->is_read): ?>
                                <form action="/notifications/read" method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                    <input type="hidden" name="notification_id" value="<?= $notif->id ?>">
                                    <button type="submit" class="btn-action btn-small">Mark Read</button>
                                </form>
                            <?php else: ?>
                                <span style="color: #4CAF50; font-size: 0.85rem;">Read</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pagination['totalPages'] > 1): ?>
        <div class="pagination">
            <?php if ($pagination['currentPage'] > 1): ?>
                <a href="/notifications/page/<?= $pagination['currentPage'] - 1 ?>" class="page-link">&laquo; Prev</a>
            <?php endif; ?>
            
            <span class="page-info">Page <?= $pagination['currentPage'] ?> of <?= $pagination['totalPages'] ?></span>
            
            <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                <a href="/notifications/page/<?= $pagination['currentPage'] + 1 ?>" class="page-link">Next &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<style>
    /* Local Styles for Notifications */
    .unread-row {
        background-color: rgba(90, 103, 216, 0.15); /* Slight tint for unread */
    }
    .read-row {
        opacity: 0.7;
    }
    .game-table {
        width: 100%;
        border-collapse: collapse;
    }
    .game-table th, .game-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #3a3a5a;
    }
    .badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: bold;
        text-transform: uppercase;
    }
    .badge-attack_alert { background: #e53e3e; color: white; }
    .badge-alliance_invite { background: #5a67d8; color: white; }
    .badge-system { background: #718096; color: white; }
    
    .btn-action.btn-small {
        padding: 0.3rem 0.6rem;
        font-size: 0.8rem;
        background: #4a5568;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    .btn-action.btn-small:hover {
        background: #2d3748;
    }
    
    .pagination {
        margin-top: 1.5rem;
        display: flex;
        justify-content: center;
        gap: 1rem;
    }
    .page-link {
        color: #f9c74f;
        text-decoration: none;
        font-weight: bold;
    }
</style>