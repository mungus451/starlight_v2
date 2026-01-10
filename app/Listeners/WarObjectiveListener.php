<?php

namespace App\Listeners;

use App\Core\Events\Event;
use App\Core\Events\Listener;
use App\Events\StrategicTargetDestroyedEvent;
use App\Models\Services\WarService;
use Psr\Log\LoggerInterface;

class WarObjectiveListener implements Listener
{
    public function __construct(
        private WarService $warService,
        private LoggerInterface $logger
    ) {
    }

    public function handle(Event $event): void
    {
        if (!$event instanceof StrategicTargetDestroyedEvent) {
            return;
        }

        $this->logger->info("WarObjectiveListener: Handling StrategicTargetDestroyedEvent for War ID {$event->warId}");

        $this->warService->logObjectivePoints(
            $event->warId,
            $event->winningAllianceId
        );
    }
}
