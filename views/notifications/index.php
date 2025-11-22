<style>
    .notification-item {
        border-left: 4px solid transparent;
        transition: all 0.2s;
    }
    .notification-item.unread {
        background-color: rgba(13, 110, 253, 0.1); /* Light Blue Tint */
        border-left-color: #0d6efd;
    }
    .notification-item.read {
        background-color: rgba(33, 37, 41, 0.5); /* Darker */
        border-left-color: #6c757d;
    }
    .notif-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 1.2rem;
    }
    .type-attack { background-color: #dc3545; color: white; }
    .type-spy { background-color: #ffc107; color: black; }
    .type-alliance { background-color: #0d6efd; color: white; }
    .type-system { background-color: #6c757d; color: white; }
</style>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="orbitron">
            <i class="fas fa-satellite-dish me-2"></i>Command Uplink
        </h2>
        <div>
            <button id="enable-push-btn" class="btn btn-outline-info btn-sm me-2" onclick="NotificationSystem.requestPermission()">
                <i class="fas fa-bell"></i> Enable Push
            </button>
            <button id="mark-all-read-btn" class="btn btn-outline-light btn-sm">
                <i class="fas fa-check-double"></i> Mark All Read
            </button>
        </div>
    </div>

    <div class="card bg-dark border-secondary">
        <div class="card-body p-0">
            <?php if (empty($notifications)): ?>
                <div class="p-5 text-center text-muted">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>No communications in log.</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush" id="notification-list">
                    <?php foreach ($notifications as $notif): ?>
                        <div class="list-group-item bg-transparent text-light notification-item <?= $notif->is_read ? 'read' : 'unread' ?>" data-id="<?= $notif->id ?>">
                            <div class="d-flex align-items-start">
                                <div class="me-3 mt-1">
                                    <?php
                                    $iconClass = match($notif->type) {
                                        'attack' => 'fa-crosshairs type-attack',
                                        'spy' => 'fa-user-secret type-spy',
                                        'alliance' => 'fa-users type-alliance',
                                        default => 'fa-info type-system'
                                    };
                                    ?>
                                    <div class="notif-icon <?= explode(' ', $iconClass)[1] ?>">
                                        <i class="fas <?= explode(' ', $iconClass)[0] ?>"></i>
                                    </div>
                                </div>

                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <h5 class="mb-1 orbitron text-white" style="font-size: 1rem;">
                                            <?= htmlspecialchars($notif->title) ?>
                                        </h5>
                                        <small class="text-muted">
                                            <?= date('M d, H:i', strtotime($notif->created_at)) ?>
                                        </small>
                                    </div>
                                    
                                    <p class="mb-1 text-secondary-emphasis">
                                        <?= nl2br(htmlspecialchars($notif->message)) ?>
                                    </p>

                                    <div class="mt-2">
                                        <?php if ($notif->link): ?>
                                            <a href="<?= htmlspecialchars($notif->link) ?>" class="btn btn-sm btn-primary py-0 px-2 me-2" style="font-size: 0.8rem;">
                                                View Report
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (!$notif->is_read): ?>
                                            <button class="btn btn-sm btn-outline-secondary py-0 px-2 mark-read-btn" data-id="<?= $notif->id ?>" style="font-size: 0.8rem;">
                                                Mark Read
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    if (typeof NotificationSystem === 'undefined') {
        console.warn('NotificationSystem not loaded from layout.');
    }
</script>