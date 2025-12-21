/**
 * Starlight Dominion - Command Uplink (Notification System)
 * Handles polling, UI updates, and Browser Push Notifications.
 */
const NotificationSystem = {
    pollInterval: 15000, // 15 Seconds
    pollTimer: null,
    lastUnreadCount: 0,
    preferences: null,

    init: function() {
        // 1. Load user preferences
        this.loadPreferences();

        // 2. Initial Check
        this.check();

        // 3. Start Polling Loop
        this.startPolling();

        // 4. Bind "Mark Read" clicks (Delegation)
        const list = document.getElementById('notification-list');
        if (list) {
            list.addEventListener('click', (e) => {
                if (e.target.matches('.mark-read-btn')) {
                    const id = e.target.dataset.id;
                    this.markAsRead(id, e.target.closest('.notification-item'));
                }
            });
        }

        // 5. Bind "Mark All Read"
        const markAllBtn = document.getElementById('mark-all-read-btn');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', () => this.markAllAsRead());
        }
    },

    loadPreferences: function() {
        fetch('/notifications/preferences')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to load preferences');
                }
                return response.json();
            })
            .then(data => {
                this.preferences = data;
            })
            .catch(err => console.error('Failed to load notification preferences:', err));
    },

    requestPermission: function() {
        if (!("Notification" in window)) return;
        Notification.requestPermission().then(permission => {
            if (permission === "granted") {
                console.log("Command Uplink: Online");
                new Notification("Command Uplink Established", {
                    body: "You will now receive alerts for attacks and espionage.",
                    icon: "/serve/avatar/default.png" // Placeholder or specific icon
                });
            }
        });
    },

    startPolling: function() {
        this.pollTimer = setInterval(() => this.check(), this.pollInterval);
    },

    check: function() {
        fetch('/notifications/check')
            .then(response => response.json())
            .then(data => {
                this.updateUI(data.unread);
                
                // Trigger Push if count increased and user has enabled push notifications
                if (data.unread > this.lastUnreadCount && data.latest) {
                    this.triggerPush(data.latest);
                }
                
                this.lastUnreadCount = data.unread;
            })
            .catch(err => console.error('Uplink Lost:', err));
    },

    updateUI: function(count) {
        // 1. Update Navbar Badge
        const badge = document.getElementById('nav-notification-badge');
        if (badge) {
            badge.textContent = count > 0 ? count : '';
            badge.style.display = count > 0 ? 'inline-block' : 'none';
            
            // Optional: Pulse animation class
            if (count > 0) badge.classList.add('pulse');
            else badge.classList.remove('pulse');
        }

        // 2. Update Tab Title (e.g., "(1) Starlight Dominion")
        if (count > 0) {
            document.title = `(${count}) Starlight Dominion`;
        } else {
            document.title = 'Starlight Dominion';
        }
    },

    triggerPush: function(notificationData) {
        if (!("Notification" in window)) return;

        // Check if user has push notifications enabled in preferences
        if (!this.preferences || !this.preferences.push_notifications_enabled) {
            return;
        }

        // Check if user has this specific notification type enabled for push
        const typeEnabled = this.isTypePushEnabled(notificationData.type);
        if (!typeEnabled) {
            return;
        }

        if (Notification.permission === "granted") {
            const notif = new Notification(notificationData.title, {
                body: notificationData.message,
                icon: '/serve/avatar/default.png', // Could be dynamic based on type
                tag: 'starlight-alert' // Prevents stacking too many
            });

            notif.onclick = function() {
                window.focus();
                window.location.href = '/notifications';
                this.close();
            };
        }
    },

    isTypePushEnabled: function(type) {
        if (!this.preferences) return false;
        
        switch(type) {
            case 'attack':
                return this.preferences.attack_enabled;
            case 'spy':
                return this.preferences.spy_enabled;
            case 'alliance':
                return this.preferences.alliance_enabled;
            case 'system':
                return this.preferences.system_enabled;
            default:
                return false;
        }
    },

    markAsRead: function(id, rowElement) {
        fetch(`/notifications/read/${id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                rowElement.classList.remove('unread');
                rowElement.classList.add('read');
                rowElement.style.opacity = '0.6';
                
                // Remove the "Mark Read" button
                const btn = rowElement.querySelector('.mark-read-btn');
                if (btn) btn.remove();

                // Decrement local count immediately for responsiveness
                this.lastUnreadCount = Math.max(0, this.lastUnreadCount - 1);
                this.updateUI(this.lastUnreadCount);
            }
        });
    },

    markAllAsRead: function() {
        if(!confirm('Mark all notifications as read?')) return;

        const formData = new FormData();
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

        fetch('/notifications/read-all', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        });
    }
};

// Auto-start on load
document.addEventListener('DOMContentLoaded', () => {
    NotificationSystem.init();
});