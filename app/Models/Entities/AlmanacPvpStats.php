<?php

declare(strict_types=1);

namespace App\Models\Entities;

/**
 * Data Transfer Object for PvP comparison statistics.
 */
readonly class AlmanacPvpStats
{
    public function __construct(
        public int $player1Id,
        public int $player2Id,
        public int $battlesWonByP1,
        public int $battlesWonByP2,
        public int $totalBattles,
        public int $unitsKilledByP1,
        public int $unitsKilledByP2,
        public int $resourcesPlunderedByP1,
        public int $resourcesPlunderedByP2,
        public int $spyAttemptsByP1,
        public int $spySuccessesByP1,
        public int $spyAttemptsByP2,
        public int $spySuccessesByP2
    ) {}
}
