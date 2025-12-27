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
     * @param int $userId Recipient ID
     * @param string $type 'attack', 'spy', 'alliance', 'system'
     * @param string $title Short headline
     * @param string $message Detailed body text
     * @param string|null $link Optional action URL
     * @return int The ID of the new notification
     */
    public function create(int $userId, string $type, string $title, string $message, ?string $link = null): int
    {
        $sql = "
            INSERT INTO notifications (user_id, type, title, message, link)
            VALUES (?, ?, ?, ?, ?)
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $type, $title, $message, $link]);
        
        return (int)$this->db->lastInsertId();
    }

    /**
     * Counts unread notifications for a user.
     * Used for the navbar badge.
     *
     * @param int $userId
     * @return int
     */
    public function getUnreadCount(int $userId): int
    {
        $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    /**
     * Gets the most recent notifications for a user.
     *
     * @param int $userId
     * @param int $limit
     * @return Notification[]
     */
    public function getRecent(int $userId, int $limit = 20): array
    {
        $sql = "
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        // Explicit parameter binding for LIMIT
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->hydrate($row);
        }
        return $results;
    }

    /**
     * Gets paginated notifications for a user.
     *
     * @param int $userId
     * @param int $page Current page (1-indexed)
     * @param int $perPage Number of items per page
     * @return Notification[]
     */
    public function getPaginated(int $userId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->bindParam(2, $perPage, PDO::PARAM_INT);
        $stmt->bindParam(3, $offset, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->hydrate($row);
        }
        return $results;
    }

    /**
     * Gets the total count of notifications for a user.
     *
     * @param int $userId
     * @return int
     */
    public function getTotalCount(int $userId): int
    {
        $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    /**
     * Marks a single notification as read.
     * Verifies ownership via userId to prevent unauthorized updates.
     *
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$notificationId, $userId]);
    }

    /**
     * Marks ALL unread notifications for a user as read.
     *
     * @param int $userId
     * @return bool
     */
    public function markAllRead(int $userId): bool
    {
        $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId]);
    }

    /**
     * Cleanup: Deletes notifications older than X days.
     * Useful for a cron job to keep the table light.
     *
     * @param int $days
     * @return int Number of deleted rows
     */
    public function deleteOld(int $days = 30): int
    {
        $sql = "DELETE FROM notifications WHERE created_at < NOW() - INTERVAL ? DAY";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->rowCount();
    }

    /**
     * Helper method to convert a database row into a Notification entity.
     *
     * @param array $data
     * @return Notification
     */
    private function hydrate(array $data): Notification
    {
        return new Notification(
            id: (int)$data['id'],
            user_id: (int)$data['user_id'],
            type: $data['type'],
            title: $data['title'],
            message: $data['message'],
            link: $data['link'] ?? null,
            is_read: (bool)$data['is_read'],
            created_at: $data['created_at']
        );
    }
}