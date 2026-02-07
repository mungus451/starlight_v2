<?php

namespace App\Models\Entities;

/**
 * Represents a single row from the 'user_structures' table.
 */
readonly class UserStructure
{
    /**
     * @param int $user_id
     * @param int $fortification_level
     * @param int $offense_upgrade_level
     * @param int $defense_upgrade_level
     * @param int $spy_upgrade_level
     * @param int $economy_upgrade_level
     * @param int $population_level
     * @param int $armory_level
     */
    public function __construct(
        public readonly int $user_id,
        public readonly int $economy_upgrade_level,
        public readonly int $population_level,
        public readonly int $armory_level,
        public readonly int $planetary_shield_level = 0,
        public readonly int $mercenary_outpost_level = 0,
        public readonly int $neural_uplink_level = 0,
        public readonly int $subspace_scanner_level = 0,
    ) {
    }
}