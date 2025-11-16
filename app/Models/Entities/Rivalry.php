<?php

namespace App\Models\Entities;

/**
 * Represents a single row from the 'rivalries' table.
 */
class Rivalry
{
    /**
     * @param int $id
     * @param int $alliance_a_id (The alliance with the lower ID)
     * @param int $alliance_b_id (The alliance with the higher ID)
     * @param int $heat_level
     * @param string|null $last_attack_date
     * @param string $created_at
     * @param string|null $alliance_a_name (From JOIN)
     * @param string|null $alliance_b_name (From JOIN)
     */
    public function __construct(
        public readonly int $id,
        public readonly int $alliance_a_id,
        public readonly int $alliance_b_id,
        public readonly int $heat_level,
        public readonly ?string $last_attack_date,
        public readonly string $created_at,
        public readonly ?string $alliance_a_name = null,
        public readonly ?string $alliance_b_name = null
    ) {
    }
}