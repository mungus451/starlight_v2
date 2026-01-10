<?php

namespace App\Events;

use App\Core\Events\Event;
use App\Models\Entities\User;

/**
 * DTO Event fired immediately after a battle transaction commits.
 * Carries all context needed for logging, notifications, and achievements.
 */
class BattleConcludedEvent implements Event
{
    /**
     * @param int $reportId The ID of the generated battle report
     * @param User $attacker The attacking user entity
     * @param User $defender The defending user entity
     * @param string $result 'victory', 'defeat', or 'stalemate'
     * @param int $prestigeGained Amount of War Prestige gained (0 if none)
     * @param int $guardsKilled Number of defender guards killed
     * @param int $creditsPlundered Amount of credits stolen (0 if none)
     */
    public function __construct(
        public readonly int $reportId,
        public readonly User $attacker,
        public readonly User $defender,
        public readonly string $result,
        public readonly int $prestigeGained,
        public readonly int $guardsKilled,
        public readonly int $creditsPlundered,
        public readonly int $structureDamage = 0 // New field with default
    ) {
    }
}