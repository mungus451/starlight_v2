<?php

namespace App\Models\Entities;

/**
 * Represents a single row from the 'alliance_bank_logs' table.
 */
readonly class AllianceBankLog
{
    /**
     * @param int $id
     * @param int $alliance_id
     * @param int|null $user_id The user associated with the log (e.g., donator)
     * @param string $log_type (e.g., 'donation', 'battle_tax', 'interest')
     * @param int $amount (Positive for income, negative for expense)
     * @param string $message
     * @param string $created_at
     * @param string|null $character_name (From JOIN)
     */
    public function __construct(
        public readonly int $id,
        public readonly int $alliance_id,
        public readonly ?int $user_id,
        public readonly string $log_type,
        public readonly float $amount,
        public readonly string $message,
        public readonly string $created_at,
        public readonly ?string $character_name = null
    ) {
    }
}