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

use FiveLab\Component\Amqp\Queue\QueueInterface;

/**
 * Default consume strategy.
 * Use consume functionality for RabbitMQ.
 */
class DefaultConsumeStrategy implements ConsumeStrategyInterface
{
    private bool $stopConsuming = false;

    public function stopConsume(): void
    {
        $this->stopConsuming = true;
    }

    public function consume(QueueInterface $queue, \Closure $handler, string $tag = ''): void
    {
        $this->stopConsuming = false;

        $queue->consume(function () use ($handler): bool {
            \call_user_func_array($handler, \func_get_args());

            if ($this->stopConsuming) {
                return false;
            }

            return true;
        }, $tag);
    }
}
