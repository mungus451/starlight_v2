<?php

namespace App\Models\Services;

use App\Core\ServiceResponse;
use App\Models\Repositories\NotificationRepository;
use App\Models\Repositories\UserNotificationPreferencesRepository;

/**
 * Handles the creation and retrieval of user notifications.
 * Acts as the bridge between game events and the notification database.
 * * Refactored to centralize polling logic.
 * * Enhanced to check user preferences before sending notifications.
 */
class NotificationService
{
    private NotificationRepository $notificationRepo;
    private UserNotificationPreferencesRepository $preferencesRepo;

    /**
     * DI Constructor.
     *
     * @param NotificationRepository $notificationRepo
     * @param UserNotificationPreferencesRepository $preferencesRepo
     */
    public function __construct(
        NotificationRepository $notificationRepo,
        UserNotificationPreferencesRepository $preferencesRepo
    ) {
        $this->notificationRepo = $notificationRepo;
        $this->preferencesRepo = $preferencesRepo;
    }

    /**
     * Sends a notification to a user.
     * Used internally by other services (Attack, Spy, etc).
     * Checks user preferences before sending.
     *
     * @param int $userId The recipient's ID
     * @param string $type The category ('attack', 'spy', 'alliance', 'system')
     * @param string $title A short summary
     * @param string $message The detailed message
     * @param string|null $link An optional URL (e.g., '/battle/report/123')
     * @return int The ID of the created notification, or 0 if not sent due to preferences
     */
    public function sendNotification(int $userId, string $type, string $title, string $message, ?string $link = null): int
    {
        // Check user preferences
        $preferences = $this->preferencesRepo->getByUserId($userId);
        
        if (!$preferences->isTypeEnabled($type)) {
            // User has disabled this notification type
            return 0;
        }
        
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
     * Gets paginated notifications for a user.
     *
     * @param int $userId
     * @param int $page Current page number (1-indexed)
     * @param int $perPage Number of items per page
     * @return array ['notifications' => Notification[], 'pagination' => array]
     */
    public function getPaginatedNotifications(int $userId, int $page = 1, int $perPage = 20): array
    {
        $notifications = $this->notificationRepo->getPaginated($userId, $page, $perPage);
        $totalCount = $this->notificationRepo->getTotalCount($userId);
        $totalPages = (int)ceil($totalCount / $perPage);

        return [
            'notifications' => $notifications,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalCount,
                'total_pages' => $totalPages,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages
            ]
        ];
    }

    /**
     * Retrieves the specific data structure required by the frontend poller.
     * This logic was previously in the controller.
     *
     * @param int $userId
     * @return array ['unread' => int, 'latest' => array|null]
     */
    public function getPollingData(int $userId): array
    {
        $unreadCount = $this->notificationRepo->getUnreadCount($userId);
        $latest = null;

        // Only fetch details if there are unread items to save DB calls
        if ($unreadCount > 0) {
            $recent = $this->notificationRepo->getRecent($userId, 1);
            
            if (!empty($recent)) {
                $item = $recent[0];
                // Ensure the most recent item is actually unread before popping it up
                if (!$item->is_read) {
                    $latest = [
                        'title' => $item->title,
                        'message' => $item->message,
                        'type' => $item->type,
                        'created_at' => $item->created_at
                    ];
                }
            }
        }

        return [
            'unread' => $unreadCount,
            'latest' => $latest
        ];
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

    /**
     * Gets the notification preferences for a user.
     *
     * @param int $userId
     * @return \App\Models\Entities\UserNotificationPreferences
     */
    public function getPreferences(int $userId)
    {
        return $this->preferencesRepo->getByUserId($userId);
    }

    /**
     * Updates the notification preferences for a user.
     *
     * @param int $userId
     * @param bool $attackEnabled
     * @param bool $spyEnabled
     * @param bool $allianceEnabled
     * @param bool $systemEnabled
     * @param bool $pushEnabled
     * @return ServiceResponse
     */
    public function updatePreferences(
        int $userId,
        bool $attackEnabled,
        bool $spyEnabled,
        bool $allianceEnabled,
        bool $systemEnabled,
        bool $pushEnabled
    ): ServiceResponse {
        $success = $this->preferencesRepo->upsert(
            $userId,
            $attackEnabled,
            $spyEnabled,
            $allianceEnabled,
            $systemEnabled,
            $pushEnabled
        );
        
        if ($success) {
            return ServiceResponse::success('Notification preferences updated successfully.');
        } else {
            return ServiceResponse::error('Failed to update notification preferences.');
        }
    }
}