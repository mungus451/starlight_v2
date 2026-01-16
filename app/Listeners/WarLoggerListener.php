<?php

namespace App\Listeners;

use App\Core\Events\Event;
use App\Core\Events\Listener;
use App\Events\BattleConcludedEvent;
use App\Models\Repositories\WarRepository;
use App\Models\Repositories\WarBattleLogRepository;

/**
 * Listener responsible for logging battle results to the war_battle_logs table
 * if the combatants are part of opposing alliances in an active war.
 */
class WarLoggerListener implements Listener
{
    private WarRepository $warRepo;
    private WarBattleLogRepository $warBattleLogRepo;

    public function __construct(
        WarRepository $warRepo,
        WarBattleLogRepository $warBattleLogRepo
    ) {
        $this->warRepo = $warRepo;
        $this->warBattleLogRepo = $warBattleLogRepo;
    }

    public function handle(Event $event): void
    {
        if (!$event instanceof BattleConcludedEvent) {
            return;
        }

        // Only log battles between players in alliances
        if ($event->attacker->alliance_id === null || $event->defender->alliance_id === null) {
            return;
        }
        
        // Check if there is an active war between these alliances
        $war = $this->warRepo->findActiveWarBetween($event->attacker->alliance_id, $event->defender->alliance_id);
        if ($war === null) {
            return;
        }

        // Log the battle details directly to the battle log repository
        $this->warBattleLogRepo->createLog(
            $war->id,
            $event->reportId,
            $event->attacker->id,
            $event->attacker->alliance_id,
            $event->prestigeGained,
            $event->guardsKilled,
            $event->creditsPlundered,
            $event->structureDamage // Assuming this is part of the event now
        );
    }
}