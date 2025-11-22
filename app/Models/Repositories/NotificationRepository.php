<?php

namespace App\Models\Repositories;

use App\Models\Entities\Notification;
use PDO;

/**
 * Handles all database operations for the 'notifications' table.
 */
class NotificationRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Creates a new notification.
     *
     * @param int $userId
     * @param string $type
     * @param string $message
     * @return int The ID of the new notification
     */
    public function create(int $userId, string $type, string $message): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO notifications (user_id, type, message, is_read) VALUES (?, ?, ?, 0)"
        );
        $stmt->execute([$userId, $type, $message]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Finds notifications for a specific user, with pagination.
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return Notification[]
     */
    public function findByUserId(int $userId, int $limit = 20, int $offset = 0): array
    {
        $sql = "
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->bindParam(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $notifications = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $notifications[] = $this->hydrate($row);
        }
        return $notifications;
    }

    /**
     * Gets the total count of unread notifications for a user.
     * Optimized query using the (user_id, is_read) index.
     *
     * @param int $userId
     * @return int
     */
    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(id) FROM notifications WHERE user_id = ? AND is_read = 0"
        );
        $stmt->execute([$userId]);
        
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Gets the total count of all notifications for a user (for pagination).
     *
     * @param int $userId
     * @return int
     */
    public function getTotalCount(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(id) FROM notifications WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * Marks a single notification as read.
     *
     * @param int $notificationId
     * @param int $userId (Security check: ensure user owns the notification)
     * @return bool
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?"
        );
        return $stmt->execute([$notificationId, $userId]);
    }

    /**
     * Marks ALL notifications for a user as read.
     *
     * @param int $userId
     * @return bool
     */
    public function markAllAsRead(int $userId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0"
        );
        return $stmt->execute([$userId]);
    }

    /**
     * Deletes old notifications (e.g., older than 30 days).
     * Intended for cleanup cron jobs.
     *
     * @param int $days
     * @return int Number of deleted rows
     */
    public function cleanupOldNotifications(int $days = 30): int
    {
        $stmt = $this->db->prepare(
            "DELETE FROM notifications WHERE created_at < NOW() - INTERVAL ? DAY"
        );
        $stmt->execute([$days]);
        return $stmt->rowCount();
    }

    /**
     * Helper method to convert a database row into a Notification entity.
     */
    private function hydrate(array $data): Notification
    {
        return new Notification(
            id: (int)$data['id'],
            user_id: (int)$data['user_id'],
            type: $data['type'],
            message: $data['message'],
            is_read: (bool)$data['is_read'],
            created_at: $data['created_at']
        );
    }
}