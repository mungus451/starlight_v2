<?php

namespace App\Models\Entities;

/**
 * Represents a single race row from the database.
 * This is a "dumb" data object.
 */
readonly class Race
{
    /**
     * @param int $id
     * @param string $name
     * @param string $exclusive_resource
     * @param string $lore
     * @param string $uses
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $exclusive_resource,
        public readonly string $lore,
        public readonly string $uses
    ) {
    }
}
