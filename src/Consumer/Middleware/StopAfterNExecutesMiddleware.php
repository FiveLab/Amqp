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

namespace FiveLab\Component\Amqp\Consumer\Middleware;

use FiveLab\Component\Amqp\Exception\StopAfterNExecutesException;
use FiveLab\Component\Amqp\Message\ReceivedMessage;

/**
 * Middleware for stop execution after N iteration.
 * Critical: consumer must confirm or not confirm received message.
 */
class StopAfterNExecutesMiddleware implements ConsumerMiddlewareInterface
{
    private int $executesCounter = 0;

    public function __construct(private readonly int $stopAfterExecutes)
    {
    }

    public function handle(ReceivedMessage $message, callable $next): void
    {
        $next($message);

        $this->executesCounter++;

        if ($this->executesCounter >= $this->stopAfterExecutes) {
            $this->executesCounter = 0;

            throw new StopAfterNExecutesException(\sprintf(
                'Stop by middleware after %d executes.',
                $this->stopAfterExecutes
            ));
        }
    }
}
