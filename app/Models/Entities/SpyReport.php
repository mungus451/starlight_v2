<?php

namespace App\Models\Entities;

/**
 * Represents a single row from the 'spy_reports' table.
 */
class SpyReport
{
    /**
     * @param int $id
     * @param int $attacker_id
     * @param int $defender_id
     * @param string $created_at
     * @param string $operation_result ('success' or 'failure')
     * @param int $spies_sent
     * @param int $spies_lost_attacker
     * @param int $sentries_lost_defender
     * @param int|null $credits_seen
     * @param int|null $gemstones_seen
     * @param int|null $workers_seen
     * @param int|null $soldiers_seen
     * @param int|null $guards_seen
     * @param int|null $spies_seen
     * @param int|null $sentries_seen
     * @param int|null $fortification_level_seen
     * @param int|null $offense_upgrade_level_seen
     * @param int|null $defense_upgrade_level_seen
     * @param int|null $spy_upgrade_level_seen
     * @param int|null $economy_upgrade_level_seen
     * @param int|null $population_level_seen
     * @param int|null $armory_level_seen
     * @param string|null $defender_name (From JOIN)
     * @param string|null $attacker_name (From JOIN)
     */
    public function __construct(
        public readonly int $id,
        public readonly int $attacker_id,
        public readonly int $defender_id,
        public readonly string $created_at,
        public readonly string $operation_result,
        public readonly int $spies_sent,
        public readonly int $spies_lost_attacker,
        public readonly int $sentries_lost_defender,
        public readonly ?int $credits_seen,
        public readonly ?int $gemstones_seen,
        public readonly ?int $workers_seen,
        public readonly ?int $soldiers_seen,
        public readonly ?int $guards_seen,
        public readonly ?int $spies_seen,
        public readonly ?int $sentries_seen,
        public readonly ?int $fortification_level_seen,
        public readonly ?int $offense_upgrade_level_seen,
        public readonly ?int $defense_upgrade_level_seen,
        public readonly ?int $spy_upgrade_level_seen,
        public readonly ?int $economy_upgrade_level_seen,
        public readonly ?int $population_level_seen,
        public readonly ?int $armory_level_seen,
        public readonly ?string $defender_name = null,
        public readonly ?string $attacker_name = null
    ) {
    }
}