<?php

namespace App\Models\Entities;

/**
 * Represents a single row from the 'user_stats' table.
 */
class UserStats
{
    /**
     * @param int $user_id
     * @param int $level
     * @param int $experience
     * @param int $net_worth
     * @param int $war_prestige
     * @param int $energy
     * @param int $attack_turns
     * @param int $level_up_points
     * @param int $strength_points
     * @param int $constitution_points
     * @param int $wealth_points
     * @param int $dexterity_points
     * @param int $charisma_points
     */
    public function __construct(
        public readonly int $user_id,
        public readonly int $level,
        public readonly int $experience,
        public readonly int $net_worth,
        public readonly int $war_prestige,
        public readonly int $energy,
        public readonly int $attack_turns,
        public readonly int $level_up_points,
        public readonly int $strength_points,
        public readonly int $constitution_points,
        public readonly int $wealth_points,
        public readonly int $dexterity_points,
        public readonly int $charisma_points
    ) {
    }
}