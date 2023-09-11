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

readonly class EventHandlers
{
    /**
     * @var array<\Closure>
     */
    private array $handlers;

    /**
     * Constructor.
     *
     * @param \Closure ...$handlers
     */
    public function __construct(\Closure ...$handlers)
    {
        $this->handlers = $handlers;
    }

    /**
     * Handle specific event
     *
     * @param Event $event
     * @param mixed ...$args
     */
    public function __invoke(Event $event, mixed ...$args): void
    {
        foreach ($this->handlers as $handler) {
            ($handler)($event, ...$args);
        }
    }
}
