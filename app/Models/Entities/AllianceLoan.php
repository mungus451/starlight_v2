<?php

namespace App\Models\Entities;

/**
 * Represents a single row from the 'alliance_loans' table.
 */
readonly class AllianceLoan
{
    /**
     * @param int $id
     * @param int $alliance_id
     * @param int $user_id (The borrower)
     * @param int $amount_requested
     * @param int $amount_to_repay (The outstanding amount owed)
     * @param string $status ('pending', 'active', 'paid', 'denied')
     * @param string $created_at
     * @param string $updated_at
     * @param string|null $character_name (From JOIN)
     */
    public function __construct(
        public readonly int $id,
        public readonly int $alliance_id,
        public readonly int $user_id,
        public readonly int $amount_requested,
        public readonly int $amount_to_repay,
        public readonly string $status,
        public readonly string $created_at,
        public readonly string $updated_at,
        public readonly ?string $character_name = null
    ) {
    }
}