<?php

namespace App\Models\Services;

use App\Core\ServiceResponse; // --- NEW IMPORT ---
use App\Models\Repositories\NotificationRepository;

/**
 * Handles the creation and retrieval of user notifications.
 * Acts as the bridge between game events and the notification database.
 * * Refactored to return ServiceResponse for state-changing methods.
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
     * Used internally by other services (Attack, Spy, etc).
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
        // Kept as returning int (ID) for internal utility usage
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
     * @return ServiceResponse
     */
    public function markAsRead(int $notificationId, int $userId): ServiceResponse
    {
        $success = $this->notificationRepo->markAsRead($notificationId, $userId);
        
        if ($success) {
            return ServiceResponse::success('Notification marked as read.');
        } else {
            // Usually fails if ID not found or User doesn't own it
            return ServiceResponse::error('Failed to mark notification as read.');
        }
    }

    /**
     * Marks all notifications as read for a user.
     *
     * @param int $userId
     * @return ServiceResponse
     */
    public function markAllRead(int $userId): ServiceResponse
    {
        $success = $this->notificationRepo->markAllRead($userId);
        
        if ($success) {
            return ServiceResponse::success('All notifications marked as read.');
        } else {
            return ServiceResponse::error('Failed to update notifications.');
        }
    }
}