<?php

namespace App\Models\Entities;

/**
 * Represents a single row from the 'alliances' table.
 */
readonly class Alliance
{
    /**
     * @param int $id
     * @param string $name
     * @param string $tag
     * @param string|null $description
     * @param string|null $profile_picture_url
     * @param bool $is_joinable (NEW: 0=Application, 1=Open)
     * @param int $leader_id
     * @param int $net_worth
     * @param int $bank_credits (NEW: Alliance bank)
     * @param string|null $last_compound_at (NEW: For interest)
     * @param string $created_at
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $tag,
        public readonly ?string $description,
        public readonly ?string $profile_picture_url,
        public readonly bool $is_joinable,          
        public readonly int $leader_id,
        public readonly int $net_worth,
        public readonly float $bank_credits,       
        public readonly ?string $last_compound_at, 
        public readonly string $created_at
    ) {
    }
}