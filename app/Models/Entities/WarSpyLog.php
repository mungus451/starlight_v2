<?php

namespace App\Models\Entities;

/**
 * Represents a single row from the 'war_spy_logs' table.
 */
readonly class WarSpyLog
{
    public function __construct(
        public int $id,
        public int $war_id,
        public ?int $spy_report_id,
        public int $attacker_user_id,
        public int $attacker_alliance_id,
        public int $defender_user_id,
        public int $defender_alliance_id,
        public string $operation_type,
        public string $result,
        public string $created_at,
        public ?string $updated_at,
        public ?string $attacker_name = null,
        public ?string $defender_name = null
    ) {
    }
}
