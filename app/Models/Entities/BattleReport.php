<?php

namespace App\Models\Entities;

/**
 * Represents a single row from the 'battle_reports' table.
 */
readonly class BattleReport
{
    /**
     * @param int $id
     * @param int $attacker_id
     * @param int $defender_id
     * @param string $created_at
     * @param string $attack_type ('plunder', 'conquer')
     * @param string $attack_result ('victory', 'defeat', 'stalemate')
     * @param int $soldiers_sent
     * @param int $attacker_soldiers_lost
     * @param int $defender_guards_lost
     * @param int $credits_plundered
     * @param int $experience_gained
     * @param int $war_prestige_gained
     * @param int $net_worth_stolen
     * @param int $attacker_offense_power
     * @param int $defender_defense_power
     * @param int $defender_total_guards (NEW: Snapshot for narrative)
     * @param string|null $defender_name (From JOIN)
     * @param string|null $attacker_name (From JOIN)
     * @param bool $is_hidden (If true, attacker is anonymous)
     */
    public function __construct(
        public readonly int $id,
        public readonly int $attacker_id,
        public readonly int $defender_id,
        public readonly string $created_at,
        public readonly string $attack_type,
        public readonly string $attack_result,
        public readonly int $soldiers_sent,
        public readonly int $attacker_soldiers_lost,
        public readonly int $defender_guards_lost,
        public readonly int $credits_plundered,
        public readonly int $experience_gained,
        public readonly int $war_prestige_gained,
        public readonly int $net_worth_stolen,
        public readonly int $attacker_offense_power,
        public readonly int $defender_defense_power,
        public readonly int $defender_total_guards, // NEW
        public readonly int $defender_shield_hp = 0, // NEW
        public readonly int $shield_damage_dealt = 0, // NEW
        public readonly ?string $defender_name = null,
        public readonly ?string $attacker_name = null,
        public readonly bool $is_hidden = false
    ) {
    }
}