<div class="container">
    <div class="flex-between mb-1">
        <h1 style="margin: 0; text-align: left; font-size: 1.8rem;">
            <i class="fas fa-satellite-dish" style="margin-right: 0.5rem; color: var(--accent);"></i>
            Command Uplink
        </h1>
        <div style="display: flex; gap: 0.5rem;">
            <button id="enable-push-btn" class="btn-submit" onclick="NotificationSystem.requestPermission()" style="margin: 0; padding: 0.5rem 1rem; font-size: 0.9rem;">
                <i class="fas fa-bell"></i> Push
            </button>
            <button id="mark-all-read-btn" class="btn-submit btn-accent" style="margin: 0; padding: 0.5rem 1rem; font-size: 0.9rem;">
                <i class="fas fa-check-double"></i> Read All
            </button>
        </div>
    </div>

    <div class="item-card" style="padding: 0; background: transparent; border: none; box-shadow: none;">
        <?php if (empty($notifications)): ?>
            <div style="text-align: center; padding: 4rem; color: var(--muted); background: var(--bg-panel); border: 1px solid var(--border); border-radius: 12px;">
                <i class="fas fa-inbox fa-3x" style="margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                <p>No communications in log.</p>
            </div>
        <?php else: ?>
            <div id="notification-list">
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-item <?= $notif->is_read ? 'read' : 'unread' ?>" data-id="<?= $notif->id ?>">
                        
                        <?php
                        $iconClass = match($notif->type) {
                            'attack' => 'fa-crosshairs type-attack',
                            'spy' => 'fa-user-secret type-spy',
                            'alliance' => 'fa-users type-alliance',
                            default => 'fa-info type-system'
                        };
                        ?>
                        
                        <!-- Icon -->
                        <div class="notif-icon <?= explode(' ', $iconClass)[1] ?>">
                            <i class="fas <?= explode(' ', $iconClass)[0] ?>"></i>
                        </div>

                        <!-- Content -->
                        <div style="flex-grow: 1;">
                            <div class="flex-between" style="margin-bottom: 0.25rem;">
                                <h4 style="margin: 0; font-size: 1rem; color: #fff; border: none; padding: 0;">
                                    <?= htmlspecialchars($notif->title) ?>
                                </h4>
                                <small style="color: var(--muted); white-space: nowrap; margin-left: 1rem;">
                                    <?= date('M d, H:i', strtotime($notif->created_at)) ?>
                                </small>
                            </div>
                            
                            <p style="margin: 0 0 0.75rem 0; font-size: 0.95rem; color: #e0e0e0; line-height: 1.5;">
                                <?= nl2br(htmlspecialchars($notif->message)) ?>
                            </p>

                            <div class="item-actions">
                                <?php if ($notif->link): ?>
                                    <a href="<?= htmlspecialchars($notif->link) ?>" class="btn-submit btn-accent" style="margin: 0; padding: 0.3rem 0.8rem; font-size: 0.8rem; display: inline-block;">
                                        View Report
                                    </a>
                                <?php endif; ?>
                                
                                <?php if (!$notif->is_read): ?>
                                    <button class="btn-submit mark-read-btn" data-id="<?= $notif->id ?>" style="margin: 0; padding: 0.3rem 0.8rem; font-size: 0.8rem; background: rgba(255,255,255,0.1); border: 1px solid var(--border);">
                                        Mark Read
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    if (typeof NotificationSystem === 'undefined') {
        console.warn('NotificationSystem not loaded from layout.');
    }
</script>