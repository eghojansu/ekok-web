<?php

namespace Ekok\Web;

class EventDispatcher
{
    private $events = array();
    private $once = array();

    public function on(string $eventName, $callable, int $priority = 0): EventDispatcher
    {
        $this->events[$eventName][] = array($callable, $priority);

        return $this;
    }

    public function one(string $eventName, $callable, int $priority = 0): EventDispatcher
    {
        $this->once[$eventName] = true;

        return $this->on($eventName, $callable, $priority);
    }

    public function off(string $eventName): EventDispatcher
    {
        unset($this->events[$eventName], $this->once[$eventName]);

        return $this;
    }

    public function dispatch(string $eventName, Event $argument, bool $once = false): void
    {
        $events = $this->events[$eventName] ?? null;
        $one = $this->once[$eventName] ?? false;

        if (!$events) {
            return;
        }

        if ($once || $one) {
            $this->off($eventName);
        }

        usort($events, function(array $a, array $b) {
            return $b[1] <=> $a[1];
        });

        foreach ($events as list($dispatch)) {
            if ($argument->isPropagationStopped()) {
                return;
            }

            $dispatch($argument);
        }
    }
}
