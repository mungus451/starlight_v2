<?php

namespace App\Models\Entities;

/**
 * Represents the single row from the 'house_finances' table.
 * This entity tracks the total fees collected by the house.
 */
class HouseFinance
{
    public function __construct(
        public readonly int $id,
        public readonly float $credits_taxed,
        public readonly float $crystals_taxed
    ) {
    }
}
