<?php

namespace App\Models\Repositories;

use App\Models\Entities\AllianceForumTopic;
use PDO;

/**
 * Handles all database operations for the 'alliance_forum_topics' table.
 */
class AllianceForumTopicRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Finds a topic by its ID.
     *
     * @param int $topicId
     * @return AllianceForumTopic|null
     */
    public function findById(int $topicId): ?AllianceForumTopic
    {
        $sql = "
            SELECT 
                aft.*,
                u.character_name as author_name
            FROM alliance_forum_topics aft
            JOIN users u ON aft.user_id = u.id
            WHERE aft.id = ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$topicId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Gets a paginated list of topics for an alliance.
     *
     * @param int $allianceId
     * @param int $limit
     * @param int $offset
     * @return AllianceForumTopic[]
     */
    public function findByAllianceId(int $allianceId, int $limit, int $offset): array
    {
        $sql = "
            SELECT 
                aft.*,
                u.character_name as author_name,
                lr_user.character_name as last_reply_user_name,
                (SELECT COUNT(id) FROM alliance_forum_posts afp WHERE afp.topic_id = aft.id) as post_count
            FROM alliance_forum_topics aft
            JOIN users u ON aft.user_id = u.id
            LEFT JOIN users lr_user ON aft.last_reply_user_id = lr_user.id
            WHERE aft.alliance_id = ?
            ORDER BY aft.is_pinned DESC, aft.last_reply_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $allianceId, PDO::PARAM_INT);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->bindParam(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $topics = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $topics[] = $this->hydrate($row);
        }
        return $topics;
    }

    /**
     * Gets the total count of topics in an alliance.
     *
     * @param int $allianceId
     * @return int
     */
    public function getCountByAllianceId(int $allianceId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(id) FROM alliance_forum_topics WHERE alliance_id = ?");
        $stmt->execute([$allianceId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Creates a new forum topic.
     *
     * @param int $allianceId
     * @param int $userId
     * @param string $title
     * @return int The ID of the new topic
     */
    public function createTopic(int $allianceId, int $userId, string $title): int
    {
        $sql = "
            INSERT INTO alliance_forum_topics 
                (alliance_id, user_id, title, last_reply_user_id) 
            VALUES 
                (?, ?, ?, ?)
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$allianceId, $userId, $title, $userId]);
        
        return (int)$this->db->lastInsertId();
    }

    /**
     * Updates the lock or pin status of a topic.
     *
     * @param int $topicId
     * @param string $field ('is_locked' or 'is_pinned')
     * @param bool $status
     * @return bool
     */
    public function updateTopicStatus(int $topicId, string $field, bool $status): bool
    {
        // Whitelist the field name to prevent SQL injection
        if (!in_array($field, ['is_locked', 'is_pinned'])) {
            return false;
        }
        
        $sql = "UPDATE alliance_forum_topics SET `{$field}` = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([(int)$status, $topicId]);
    }

    /**
     * Updates the last reply timestamp and user for a topic.
     *
     * @param int $topicId
     * @param int $lastReplyUserId
     * @return bool
     */
    public function updateLastReply(int $topicId, int $lastReplyUserId): bool
    {
        $sql = "
            UPDATE alliance_forum_topics 
            SET last_reply_at = NOW(), last_reply_user_id = ? 
            WHERE id = ?
        ";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$lastReplyUserId, $topicId]);
    }

    /**
     * Helper method to convert a database row into an AllianceForumTopic entity.
     *
     * @param array $data
     * @return AllianceForumTopic
     */
    private function hydrate(array $data): AllianceForumTopic
    {
        return new AllianceForumTopic(
            id: (int)$data['id'],
            alliance_id: (int)$data['alliance_id'],
            user_id: (int)$data['user_id'],
            title: $data['title'],
            is_locked: (bool)$data['is_locked'],
            is_pinned: (bool)$data['is_pinned'],
            created_at: $data['created_at'],
            last_reply_at: $data['last_reply_at'],
            last_reply_user_id: isset($data['last_reply_user_id']) ? (int)$data['last_reply_user_id'] : null,
            author_name: $data['author_name'] ?? null,
            last_reply_user_name: $data['last_reply_user_name'] ?? null,
            post_count: isset($data['post_count']) ? (int)$data['post_count'] : null
        );
    }
}