<div class="mobile-content">
    <div class="mobile-card">
        <div class="mobile-card-header">
            <h3><i class="fas fa-satellite-dish"></i> Command Uplink</h3>
            <div style="display: flex; gap: 0.5rem;">
                <!-- Push Button -->
                <button id="enable-push-btn" class="btn btn-accent" style="width: auto; padding: 0.5rem 0.8rem; margin: 0; font-size: 0.9rem;" onclick="NotificationSystem.requestPermission()">
                    <i class="fas fa-bell"></i>
                </button>
                <!-- Mark All Read -->
                <button id="mark-all-read-btn" class="btn" style="width: auto; padding: 0.5rem 0.8rem; margin: 0; font-size: 0.9rem;">
                    <i class="fas fa-check-double"></i>
                </button>
            </div>
        </div>

        <div class="mobile-card-content" id="notification-list" style="padding: 0;">
             <?php if (empty($notifications)): ?>
                <div style="text-align: center; padding: 3rem 1rem; color: var(--muted);">
                    <i class="fas fa-inbox fa-3x" style="opacity: 0.3; margin-bottom: 1rem;"></i>
                    <p>No communications.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                    <!-- Using notification-item style but tweaked for mobile context -->
                    <div class="notification-item <?= $notif->is_read ? 'read' : 'unread' ?>" data-id="<?= $notif->id ?>" style="border-radius: 0; border: none; border-bottom: 1px solid var(--mobile-border); margin: 0; background: transparent;">
                        
                        <?php
                        // Type logic
                         $typeClass = match($notif->type) {
                             'attack' => 'type-attack',
                             'spy' => 'type-spy',
                             'alliance' => 'type-alliance',
                             default => 'type-system'
                        };
                        $icon = match($notif->type) {
                            'attack' => 'fa-crosshairs',
                            'spy' => 'fa-user-secret',
                            'alliance' => 'fa-users',
                            default => 'fa-info'
                        };
                        ?>
                        
                        <div class="notif-icon <?= $typeClass ?>" style="width: 32px; height: 32px; font-size: 0.9rem;">
                            <i class="fas <?= $icon ?>"></i>
                        </div>

                        <div style="flex-grow: 1;">
                            <div class="flex-between mb-1">
                                <span style="font-weight: 700; color: var(--mobile-text-primary); font-size: 0.95rem;">
                                    <?= htmlspecialchars($notif->title) ?>
                                </span>
                                <small style="color: var(--muted); font-size: 0.75rem;">
                                    <?= date('M d, H:i', strtotime($notif->created_at)) ?>
                                </small>
                            </div>
                            
                            <p style="margin: 0 0 0.5rem 0; font-size: 0.85rem; color: #ccc; line-height: 1.4;">
                                <?= nl2br(htmlspecialchars($notif->message)) ?>
                            </p>

                            <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                                <?php if ($notif->link): ?>
                                    <a href="<?= htmlspecialchars($notif->link) ?>" class="btn" style="padding: 0.4rem; font-size: 0.8rem; margin: 0; width: auto; flex: 1;">
                                        View
                                    </a>
                                <?php endif; ?>
                                
                                <?php if (!$notif->is_read): ?>
                                    <button class="btn btn-accent mark-read-btn" data-id="<?= $notif->id ?>" style="padding: 0.4rem; font-size: 0.8rem; margin: 0; width: auto; flex: 1;">
                                        Dismiss
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pagination -->
    <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
        <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 1rem;">
             <?php if ($pagination['has_previous']): ?>
                <a href="/notifications?page=<?= htmlspecialchars($pagination['current_page'] - 1) ?>" class="btn" style="width: auto; padding: 0.5rem 1rem;">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php else: ?>
                <button class="btn" disabled style="width: auto; padding: 0.5rem 1rem; opacity: 0.5;">
                    <i class="fas fa-chevron-left"></i>
                </button>
            <?php endif; ?>

            <span style="display: flex; align-items: center; color: var(--muted);">
                <?= htmlspecialchars($pagination['current_page']) ?> / <?= htmlspecialchars($pagination['total_pages']) ?>
            </span>

            <?php if ($pagination['has_next']): ?>
                <a href="/notifications?page=<?= htmlspecialchars($pagination['current_page'] + 1) ?>" class="btn" style="width: auto; padding: 0.5rem 1rem;">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php else: ?>
                <button class="btn" disabled style="width: auto; padding: 0.5rem 1rem; opacity: 0.5;">
                    <i class="fas fa-chevron-right"></i>
                </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script src="/js/notifications.js"></script>