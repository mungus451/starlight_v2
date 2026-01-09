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
     * @param float $bank_credits (NEW: Alliance bank)
     * @param string|null $last_compound_at (NEW: For interest)
     * @param string|null $directive_type
     * @param int $directive_target
     * @param int $directive_start_value
     * @param string|null $directive_started_at
     * @param array $completed_directives (JSON decoded)
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
        public readonly string $created_at,
        public readonly ?string $directive_type = null,
        public readonly int $directive_target = 0,
        public readonly int $directive_start_value = 0,
        public readonly ?string $directive_started_at = null,
        public readonly array $completed_directives = []
    ) {
    }
}