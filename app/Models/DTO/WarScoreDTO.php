<?php

namespace App\Models\DTO;

use App\Models\Entities\Alliance;

readonly class WarScoreDTO
{
    public function __construct(
        public Alliance $allianceA,
        public Alliance $allianceB,
        public array $metricsA,
        public array $metricsB,
        public array $pointsA,
        public array $pointsB,
        public float $totalPointsA,
        public float $totalPointsB
    ) {
    }
}
