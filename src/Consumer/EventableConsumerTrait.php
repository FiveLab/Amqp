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

trait EventableConsumerTrait
{
    /**
     * @var \Closure(Event $event): void|null
     */
    private ?\Closure $eventHandler = null;

    public function setEventHandler(?\Closure $eventHandler): void
    {
        $this->eventHandler = $eventHandler;
    }

    public function addEventHandler(\Closure $eventHandler, bool $isLazyFactory = false): void
    {
        if ($isLazyFactory) {
            $eventHandler = ($eventHandler)(); // @phpstan-ignore-line
            $eventHandler = ($eventHandler)(...); // @phpstan-ignore-line
        }

        if ($this->eventHandler) {
            $eventHandler = (new EventHandlers($this->eventHandler, $eventHandler))(...);
        }

        $this->eventHandler = $eventHandler;
    }

    protected function triggerEvent(Event $event, mixed ...$args): void
    {
        if ($this->eventHandler) {
            ($this->eventHandler)($event, ...$args);
        }
    }
}
