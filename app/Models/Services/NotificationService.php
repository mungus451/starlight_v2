<?php

namespace App\Models\Services;

use App\Models\Repositories\NotificationRepository;

/**
 * Handles business logic for notifications.
 * Manages creation, retrieval, and state updates.
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
     * Gets a paginated list of notifications for a user.
     *
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getUserNotifications(int $userId, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        
        $notifications = $this->notificationRepo->findByUserId($userId, $perPage, $offset);
        $totalCount = $this->notificationRepo->getTotalCount($userId);
        $totalPages = (int)ceil($totalCount / $perPage);

        return [
            'notifications' => $notifications,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalItems' => $totalCount
            ]
        ];
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
     * Creates a "System" notification (generic).
     *
     * @param int $userId
     * @param string $message
     * @return int
     */
    public function createSystemNotification(int $userId, string $message): int
    {
        return $this->notificationRepo->create($userId, 'system', $message);
    }

    /**
     * Creates an "Attack Alert" notification.
     *
     * @param int $defenderId
     * @param string $attackerName
     * @param string $result (e.g., 'defeated', 'defended')
     * @return int
     */
    public function createAttackAlert(int $defenderId, string $attackerName, string $result): int
    {
        $message = "You were attacked by {$attackerName}. Result: {$result}. Check your Battle Logs for details.";
        return $this->notificationRepo->create($defenderId, 'attack_alert', $message);
    }

    /**
     * Creates an "Alliance Invite" notification.
     *
     * @param int $userId
     * @param string $allianceName
     * @param string $inviterName
     * @return int
     */
    public function createAllianceInviteAlert(int $userId, string $allianceName, string $inviterName): int
    {
        $message = "You have been invited to join the alliance [{$allianceName}] by {$inviterName}.";
        return $this->notificationRepo->create($userId, 'alliance_invite', $message);
    }

    /**
     * Marks a specific notification as read.
     *
     * @param int $userId
     * @param int $notificationId
     * @return bool
     */
    public function markAsRead(int $userId, int $notificationId): bool
    {
        return $this->notificationRepo->markAsRead($notificationId, $userId);
    }

    /**
     * Marks all notifications for a user as read.
     *
     * @param int $userId
     * @return bool
     */
    public function markAllRead(int $userId): bool
    {
        return $this->notificationRepo->markAllAsRead($userId);
    }
}