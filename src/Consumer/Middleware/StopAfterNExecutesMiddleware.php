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
use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;

/**
 * Middleware for stop execution after N iteration.
 */
class StopAfterNExecutesMiddleware implements MiddlewareInterface
{
    /**
     * @var int
     */
    private $stopAfterExecutes;

    /**
     * @var int
     */
    private $executesCounter = 0;

    /**
     * Constructor.
     *
     * @param int $stopAfterExecutes
     */
    public function __construct(int $stopAfterExecutes)
    {
        $this->stopAfterExecutes = $stopAfterExecutes;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ReceivedMessageInterface $message, callable $next): void
    {
        if ($this->executesCounter >= $this->stopAfterExecutes) {
            $this->executesCounter = 0;

            throw new StopAfterNExecutesException(\sprintf(
                'Stop by middleware after %d executes.',
                $this->stopAfterExecutes
            ));
        }

        $next($message);

        $this->executesCounter++;
    }
}
