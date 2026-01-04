<?php

namespace App\Models\Repositories;

use App\Models\Entities\AllianceForumPost;
use PDO;

/**
 * Handles all database operations for the 'alliance_forum_posts' table.
 */
class AllianceForumPostRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Finds all posts for a given topic, with author details.
     *
     * @param int $topicId
     * @return AllianceForumPost[]
     */
    public function findAllByTopicId(int $topicId): array
    {
        $sql = "
            SELECT 
                afp.*,
                u.character_name as author_name,
                u.profile_picture_url as author_avatar
            FROM alliance_forum_posts afp
            JOIN users u ON afp.user_id = u.id
            WHERE afp.topic_id = ?
            ORDER BY afp.created_at ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$topicId]);
        
        $posts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $posts[] = $this->hydrate($row);
        }
        return $posts;
    }

    /**
     * Creates a new post in a topic.
     *
     * @param int $topicId
     * @param int $allianceId
     * @param int $userId
     * @param string $content
     * @return int The ID of the new post
     */
    public function createPost(int $topicId, int $allianceId, int $userId, string $content): int
    {
        $sql = "
            INSERT INTO alliance_forum_posts 
                (topic_id, alliance_id, user_id, content) 
            VALUES 
                (?, ?, ?, ?)
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$topicId, $allianceId, $userId, $content]);
        
        return (int)$this->db->lastInsertId();
    }

    /**
     * Deletes a specific post by its ID.
     *
     * @param int $postId
     * @return bool
     */
    public function deletePost(int $postId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM alliance_forum_posts WHERE id = ?");
        return $stmt->execute([$postId]);
    }

    /**
     * Gets the unique user IDs who have posted in a topic.
     * Used for notifying only participants of new posts.
     *
     * @param int $topicId
     * @return array Array of user IDs
     */
    public function getTopicParticipantIds(int $topicId): array
    {
        $sql = "
            SELECT DISTINCT user_id 
            FROM alliance_forum_posts 
            WHERE topic_id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$topicId]);
        
        $userIds = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $userIds[] = (int)$row['user_id'];
        }
        return $userIds;
    }

    /**
     * Helper method to convert a database row into an AllianceForumPost entity.
     *
     * @param array $data
     * @return AllianceForumPost
     */
    private function hydrate(array $data): AllianceForumPost
    {
        return new AllianceForumPost(
            id: (int)$data['id'],
            topic_id: (int)$data['topic_id'],
            alliance_id: (int)$data['alliance_id'],
            user_id: (int)$data['user_id'],
            content: $data['content'],
            created_at: $data['created_at'],
            author_name: $data['author_name'] ?? null,
            author_avatar: $data['author_avatar'] ?? null
        );
    }
}