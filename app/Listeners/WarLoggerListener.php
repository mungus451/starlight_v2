<?php

namespace App\Listeners;

use App\Core\Events\Event;
use App\Core\Events\Listener;
use App\Events\BattleConcludedEvent;
use App\Models\Services\WarService;

/**
 * Listener responsible for logging battle results to the War system
 * if the combatants are part of opposing alliances in an active war.
 */
class WarLoggerListener implements Listener
{
    private WarService $warService;

    /**
     * @param WarService $warService
     */
    public function __construct(WarService $warService)
    {
        $this->warService = $warService;
    }

    /**
     * Handle the BattleConcludedEvent.
     *
     * @param Event $event
     */
    public function handle(Event $event): void
    {
        if (!$event instanceof BattleConcludedEvent) {
            return;
        }

        // Delegate the logic check (active war existence) to the WarService
        $this->warService->logBattle(
            $event->reportId,
            $event->attacker,
            $event->defender,
            $event->result,
            $event->prestigeGained,
            $event->guardsKilled,
            $event->creditsPlundered
        );
    }
}