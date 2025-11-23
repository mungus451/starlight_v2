<?php

namespace App\Models\Entities;

/**
 * Represents a single row from the 'user_resources' table.
 */
class UserResource
{
    /**
     * @param int $user_id
     * @param int $credits
     * @param int $banked_credits (BIGINT UNSIGNED in DB, int in PHP is fine on 64-bit)
     * @param int $gemstones (BIGINT UNSIGNED in DB, int in PHP is fine on 64-bit)
     * @param float $naquadah_crystals (DECIMAL(19,4) in DB)
     * @param int $untrained_citizens
     * @param int $workers
     * @param int $soldiers
     * @param int $guards
     * @param int $spies
     * @param int $sentries
     */
    public function __construct(
        public readonly int $user_id,
        public readonly int $credits,
        public readonly int $banked_credits,
        public readonly int $gemstones,
        public readonly float $naquadah_crystals,
        public readonly int $untrained_citizens,
        public readonly int $workers,
        public readonly int $soldiers,
        public readonly int $guards,
        public readonly int $spies,
        public readonly int $sentries
    ) {
    }
}