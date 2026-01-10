<?php

namespace App\Models\Entities;

/**
 * Represents a single row from the 'wars' table.
 */
readonly class War
{
    /**
     * @param int $id
     * @param string $name
     * @param string $war_type ('skirmish', 'war')
     * @param int $declarer_alliance_id
     * @param int $declared_against_alliance_id
     * @param string|null $casus_belli
     * @param string $status ('active', 'concluded')
     * @param string $goal_key
     * @param int $goal_threshold
     * @param int $declarer_score
     * @param int $defender_score
     * @param int|null $winner_alliance_id
     * @param string $start_time
     * @param string|null $end_time
     * @param string|null $declarer_name (From JOIN)
     * @param string|null $defender_name (From JOIN)
     */
    public function __construct(
        public readonly int $id,
        public readonly string $war_type,
        public readonly int $declarer_alliance_id,
        public readonly int $declared_against_alliance_id,
        public readonly ?string $casus_belli,
        public readonly string $status,
        public readonly ?string $goal_key,
        public readonly ?int $goal_threshold,
        public readonly int $declarer_score,
        public readonly int $defender_score,
        public readonly ?int $winner_alliance_id,
        public readonly string $start_time,
        public readonly ?string $end_time,
        public readonly ?string $declarer_name = null,
        public readonly ?string $declarer_tag = null,
        public readonly ?string $defender_name = null,
        public readonly ?string $defender_tag = null
    ) {
    }
}