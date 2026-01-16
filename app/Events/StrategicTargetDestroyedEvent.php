<?php

namespace App\Events;

use App\Core\Events\Event;

class StrategicTargetDestroyedEvent implements Event
{
    public function __construct(
        public readonly int $warId,
        public readonly int $winningAllianceId,
        public readonly int $losingAllianceId
    ) {
    }
}
