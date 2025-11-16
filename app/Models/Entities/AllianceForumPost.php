<?php

namespace App\Models\Entities;

/**
 * Represents a single row from the 'alliance_forum_posts' table.
 */
class AllianceForumPost
{
    /**
     * @param int $id
     * @param int $topic_id
     * @param int $alliance_id
     * @param int $user_id (Author)
     * @param string $content
     * @param string $created_at
     * @param string|null $author_name (From JOIN)
     * @param string|null $author_avatar (From JOIN)
     */
    public function __construct(
        public readonly int $id,
        public readonly int $topic_id,
        public readonly int $alliance_id,
        public readonly int $user_id,
        public readonly string $content,
        public readonly string $created_at,
        public readonly ?string $author_name = null,
        public readonly ?string $author_avatar = null
    ) {
    }
}