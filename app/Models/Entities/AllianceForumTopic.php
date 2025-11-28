<?php

namespace App\Models\Entities;

/**
 * Represents a single row from the 'alliance_forum_topics' table.
 */
readonly class AllianceForumTopic
{
    /**
     * @param int $id
     * @param int $alliance_id
     * @param int $user_id (Author)
     * @param string $title
     * @param bool $is_locked
     * @param bool $is_pinned
     * @param string $created_at
     * @param string $last_reply_at
     * @param int|null $last_reply_user_id
     * @param string|null $author_name (From JOIN)
     * @param string|null $last_reply_user_name (From JOIN)
     * @param int|null $post_count (From JOIN)
     */
    public function __construct(
        public readonly int $id,
        public readonly int $alliance_id,
        public readonly int $user_id,
        public readonly string $title,
        public readonly bool $is_locked,
        public readonly bool $is_pinned,
        public readonly string $created_at,
        public readonly string $last_reply_at,
        public readonly ?int $last_reply_user_id,
        public readonly ?string $author_name = null,
        public readonly ?string $last_reply_user_name = null,
        public readonly ?int $post_count = null
    ) {
    }
}