<?php

namespace App\Models\Entities;

/**
 * Represents a single row from the 'alliance_structures' table.
 * This is a "dumb" data object for a structure an alliance owns.
 */
readonly class AllianceStructure
{
    /**
     * @param int $id
     * @param int $alliance_id
     * @param string $structure_key
     * @param int $level
     * @param string $created_at
     * @param string $updated_at
     */
    public function __construct(
        public readonly int $id,
        public readonly int $alliance_id,
        public readonly string $structure_key,
        public readonly int $level,
        public readonly ?string $created_at,
        public readonly ?string $updated_at
    ) {
    }
}