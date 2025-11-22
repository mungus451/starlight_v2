<?php

namespace App\Listeners;

use App\Core\Events\Event;
use App\Core\Events\Listener;
use App\Events\BattleConcludedEvent;
use App\Models\Services\NotificationService;

/**
 * Listener responsible for sending a system notification to the defender
 * after a battle has concluded.
 */
class BattleNotificationListener implements Listener
{
    private NotificationService $notificationService;

    /**
     * @param NotificationService $notificationService
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the BattleConcludedEvent.
     *
     * @param Event $event
     */
    public function handle(Event $event): void
    {
        // Ensure we are handling the correct event type
        if (!$event instanceof BattleConcludedEvent) {
            return;
        }

        // Attacker gets immediate feedback via UI Flash, so we notify the Defender.
        $defenderId = $event->defender->id;
        $attackerName = $event->attacker->characterName;
        
        // Determine title based on result (from Attacker's perspective)
        // If Attacker won ('victory'), Defender lost.
        // If Attacker lost ('defeat'), Defender won.
        $resultLabel = match ($event->result) {
            'victory' => 'Defeat',
            'defeat' => 'Victory',
            'stalemate' => 'Stalemate',
            default => 'Report'
        };

        $title = "You were attacked by {$attackerName}!";
        
        $message = "Defense Report: {$resultLabel}.";
        $message .= " You lost " . number_format($event->guardsKilled) . " guards.";
        
        if ($event->creditsPlundered > 0) {
            $message .= " {$attackerName} plundered " . number_format($event->creditsPlundered) . " credits.";
        }

        $link = "/battle/report/{$event->reportId}";

        // Send the notification via the service
        $this->notificationService->sendNotification(
            $defenderId,
            'attack',
            $title,
            $message,
            $link
        );
    }
}