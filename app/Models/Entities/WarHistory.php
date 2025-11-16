<?php

namespace App\Models\Entities;

/**
 * Represents a single row from the 'war_history' table.
 * This is a snapshot of a concluded war.
 */
class WarHistory
{
    /**
     * @param int $id
     * @param int $war_id
     * @param string $declarer_name
     * @param string $defender_name
     * @param string|null $casus_belli_text
     * @param string $goal_text
     * @param string $outcome
     * @param string|null $mvp_metadata_json
     * @param string|null $final_stats_json
     * @param string $start_time
     * @param string $end_time
     */
    public function __construct(
        public readonly int $id,
        public readonly int $war_id,
        public readonly string $declarer_name,
        public readonly string $defender_name,
        public readonly ?string $casus_belli_text,
        public readonly string $goal_text,
        public readonly string $outcome,
        public readonly ?string $mvp_metadata_json,
        public readonly ?string $final_stats_json,
        public readonly string $start_time,
        public readonly string $end_time
    ) {
    }
}