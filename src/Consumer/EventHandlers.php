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

    public function __construct(\Closure ...$handlers)
    {
        $this->handlers = $handlers;
    }

    public function __invoke(Event $event, mixed ...$args): void
    {
        foreach ($this->handlers as $handler) {
            ($handler)($event, ...$args);
        }
    }
}
