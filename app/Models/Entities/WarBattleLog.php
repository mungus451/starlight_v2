<?php

namespace App\Models\Entities;

/**
 * Represents a single row from the 'war_battle_logs' table.
 */
class WarBattleLog
{
    /**
     * @param int $id
     * @param int $war_id
     * @param int $battle_report_id
     * @param int $user_id
     * @param int $alliance_id
     * @param int $prestige_gained
     * @param int $units_killed
     * @param int $credits_plundered
     * @param int $structure_damage
     * @param string $created_at
     */
    public function __construct(
        public readonly int $id,
        public readonly int $war_id,
        public readonly int $battle_report_id,
        public readonly int $user_id,
        public readonly int $alliance_id,
        public readonly int $prestige_gained,
        public readonly int $units_killed,
        public readonly int $credits_plundered,
        public readonly int $structure_damage,
        public readonly string $created_at
    ) {
    }
}