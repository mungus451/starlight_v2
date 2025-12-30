<?php

declare(strict_types=1);

namespace App\Models\Entities;

/**
 * Data Transfer Object for Alliance comparison statistics.
 */
readonly class AlmanacAllianceStats
{
    public function __construct(
        public int $alliance1Id,
        public int $alliance2Id,
        public int $warsWonByA1,
        public int $warsWonByA2,
        public int $activeConflicts,
        public int $totalDamageDealtByA1,
        public int $totalDamageDealtByA2
    ) {}
}
