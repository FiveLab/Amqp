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

namespace FiveLab\Component\Amqp\Consumer\Strategy;

use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Queue\QueueInterface;

/**
 * Loop consume strategy.
 * Use "get" messages in infinity loop.
 */
class LoopConsumeStrategy implements ConsumeStrategyInterface
{
    private bool $stopConsuming = false;

    public function __construct(
        private readonly int   $idleDelay = 100000,
        private readonly mixed $tickHandler = null
    ) {
        if ($this->tickHandler && !\is_callable($this->tickHandler)) {
            throw new \InvalidArgumentException(\sprintf(
                'The tick handler must be a callable, %s given.',
                \get_debug_type($this->tickHandler)
            ));
        }
    }

    public function stopConsume(): void
    {
        $this->stopConsuming = true;
    }

    public function consume(QueueInterface $queue, \Closure $handler, string $tag = ''): void
    {
        $readTimeout = $queue->getChannel()->getConnection()->getReadTimeout();

        $tickTimer = 0;
        $idleReadTimer = 0;

        $this->stopConsuming = false;

        while (!$this->stopConsuming) { // @phpstan-ignore-line booleanNot.alwaysTrue
            if ($readTimeout && $readTimeout < $idleReadTimer) {
                throw new ConsumerTimeoutExceedException('Consumer timeout exceed.');
            }

            $startTime = \microtime(true);

            $message = $queue->get();

            if ($message) {
                $idleReadTimer = 0;
                $handler($message);
            } else {
                \usleep($this->idleDelay);

                $idleReadTimer += \microtime(true) - $startTime;
            }

            $tickTimer += \microtime(true) - $startTime;

            if ($this->tickHandler && $tickTimer >= 1) {
                $tickTimer = 0;

                ($this->tickHandler)($queue->getName(), $tag);
            }
        }
    }
}
