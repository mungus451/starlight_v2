<?php

namespace App\Models\Services;

use App\Models\Repositories\NotificationRepository;

/**
 * Handles the creation and retrieval of user notifications.
 * Acts as the bridge between game events and the notification database.
 */
class NotificationService
{
    private NotificationRepository $notificationRepo;

    /**
     * DI Constructor.
     *
     * @param NotificationRepository $notificationRepo
     */
    public function __construct(NotificationRepository $notificationRepo)
    {
        $this->notificationRepo = $notificationRepo;
    }

    /**
     * Sends a notification to a user.
     *
     * @param int $userId The recipient's ID
     * @param string $type The category ('attack', 'spy', 'alliance', 'system')
     * @param string $title A short summary
     * @param string $message The detailed message
     * @param string|null $link An optional URL (e.g., '/battle/report/123')
     * @return int The ID of the created notification
     */
    public function sendNotification(int $userId, string $type, string $title, string $message, ?string $link = null): int
    {
        // In the future, this method could also trigger real-time websockets or emails.
        // For now, it persists to the database.
        return $this->notificationRepo->create($userId, $type, $title, $message, $link);
    }

    /**
     * Gets the count of unread notifications for the navbar badge.
     *
     * @param int $userId
     * @return int
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->notificationRepo->getUnreadCount($userId);
    }

    /**
     * Gets the recent notification history for the inbox view.
     *
     * @param int $userId
     * @param int $limit
     * @return array Notification entities
     */
    public function getRecentNotifications(int $userId, int $limit = 50): array
    {
        return $this->notificationRepo->getRecent($userId, $limit);
    }

    /**
     * Marks a notification as read.
     *
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        return $this->notificationRepo->markAsRead($notificationId, $userId);
    }

    /**
     * Marks all notifications as read for a user.
     *
     * @param int $userId
     * @return bool
     */
    public function markAllRead(int $userId): bool
    {
        return $this->notificationRepo->markAllRead($userId);
    }
}