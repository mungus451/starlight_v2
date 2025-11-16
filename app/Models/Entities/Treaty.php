<?php

namespace App\Models\Entities;

/**
 * Represents a single row from the 'treaties' table.
 */
class Treaty
{
    /**
     * @param int $id
     * @param int $alliance1_id (Proposing alliance)
     * @param int $alliance2_id (Target alliance)
     * @param string $treaty_type ('peace', 'non_aggression', 'mutual_defense')
     * @param string $status ('proposed', 'active', 'expired', 'broken', 'declined')
     * @param string|null $terms
     * @param string|null $expiration_date
     * @param string $created_at
     * @param string $updated_at
     * @param string|null $alliance1_name (From JOIN)
     * @param string|null $alliance2_name (From JOIN)
     */
    public function __construct(
        public readonly int $id,
        public readonly int $alliance1_id,
        public readonly int $alliance2_id,
        public readonly string $treaty_type,
        public readonly string $status,
        public readonly ?string $terms,
        public readonly ?string $expiration_date,
        public readonly string $created_at,
        public readonly string $updated_at,
        public readonly ?string $alliance1_name = null,
        public readonly ?string $alliance2_name = null
    ) {
    }
}