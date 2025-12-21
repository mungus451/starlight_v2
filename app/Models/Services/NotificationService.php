<?php

namespace App\Models\Services;

use App\Core\ServiceResponse;
use App\Models\Repositories\NotificationRepository;
use App\Models\Repositories\UserRepository;

/**
 * Handles the creation and retrieval of user notifications.
 * Acts as the bridge between game events and the notification database.
 * * Refactored to centralize polling logic.
 */
class NotificationService
{
    private NotificationRepository $notificationRepo;
    private UserRepository $userRepo;

    /**
     * DI Constructor.
     *
     * @param NotificationRepository $notificationRepo
     * @param UserRepository $userRepo
     */
    public function __construct(
        NotificationRepository $notificationRepo,
        UserRepository $userRepo
    ) {
        $this->notificationRepo = $notificationRepo;
        $this->userRepo = $userRepo;
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
     * Sends a notification to all members of an alliance except the specified user.
     * Useful for alliance-wide announcements (forum posts, structure purchases, etc).
     * 
     * @param int $allianceId
     * @param int $excludeUserId The user who performed the action (won't receive notification)
     * @param string $title
     * @param string $message
     * @param string|null $link
     * @return void
     */
    public function notifyAllianceMembers(int $allianceId, int $excludeUserId, string $title, string $message, ?string $link = null): void
    {
        $members = $this->userRepo->findAllByAllianceId($allianceId);
        
        foreach ($members as $memberData) {
            $memberId = (int)$memberData['id'];
            // Don't notify the user who performed the action
            if ($memberId !== $excludeUserId) {
                $this->sendNotification(
                    $memberId,
                    'alliance',
                    $title,
                    $message,
                    $link
                );
            }
        }
    }
}