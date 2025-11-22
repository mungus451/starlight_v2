<?php

namespace App\Core\Events;

/**
 * Central hub for dispatching events.
 * Decouples the subject (Service) from the observers (Listeners).
 */
class EventDispatcher
{
    /**
     * @var array<string, Listener[]>
     * Mapping of EventClassName => Array of Listener instances
     */
    private array $listeners = [];

    /**
     * Register a listener for a specific event.
     *
     * @param string $eventClass The full class name of the Event (e.g., BattleConcludedEvent::class)
     * @param Listener $listener The listener instance to handle it
     */
    public function addListener(string $eventClass, Listener $listener): void
    {
        if (!isset($this->listeners[$eventClass])) {
            $this->listeners[$eventClass] = [];
        }
        $this->listeners[$eventClass][] = $listener;
    }

    /**
     * Dispatches an event to all registered listeners.
     *
     * @param Event $event
     */
    public function dispatch(Event $event): void
    {
        $eventClass = get_class($event);

        if (isset($this->listeners[$eventClass])) {
            foreach ($this->listeners[$eventClass] as $listener) {
                $listener->handle($event);
            }
        }
    }
}