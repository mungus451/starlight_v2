<?php

namespace App\Models\Entities;

/**
 * Represents a single row from the 'alliances' table.
 */
class Alliance
{
    /**
     * @param int $id
     * @param string $name
     * @param string $tag
     * @param string|null $description
     * @param string|null $profile_picture_url
     * @param int $leader_id
     * @param int $net_worth
     * @param string $created_at
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $tag,
        public readonly ?string $description,
        public readonly ?string $profile_picture_url,
        public readonly int $leader_id,
        public readonly int $net_worth,
        public readonly string $created_at
    ) {
    }
}