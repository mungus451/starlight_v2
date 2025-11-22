<?php

namespace App\Core\Events;

/**
 * Contract for Event Listeners.
 * Classes implementing this handle specific business logic side-effects.
 */
interface Listener
{
    /**
     * Handle the given event.
     *
     * @param Event $event
     * @return void
     */
    public function handle(Event $event): void;
}