<?php

/*
 * This file is part of the FiveLab Amqp package
 *
 * (c) FiveLab
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

declare(strict_types = 1);

namespace FiveLab\Component\Amqp\Consumer;

/**
 * Trait for implement EventableConsumerInterface
 */
trait EventableConsumerTrait
{
    /**
     * @var \Closure(Event $event): void|null
     */
    private ?\Closure $eventHandler = null;

    /**
     * Set event handler
     *
     * @param \Closure|null $eventHandler
     */
    public function setEventHandler(?\Closure $eventHandler): void
    {
        $this->eventHandler = $eventHandler;
    }

    /**
     * Add event handler.
     *
     * @param \Closure $eventHandler
     */
    public function addEventHandler(\Closure $eventHandler): void
    {
        if ($this->eventHandler) {
            $eventHandler = (new EventHandlers($this->eventHandler, $eventHandler))(...);
        }

        $this->eventHandler = $eventHandler;
    }

    /**
     * Trigger event.
     *
     * @param Event $event
     * @param mixed ...$args
     */
    protected function triggerEvent(Event $event, mixed ...$args): void
    {
        if ($this->eventHandler) {
            ($this->eventHandler)($event, ...$args);
        }
    }
}
