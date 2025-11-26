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

interface ConsumeStrategyInterface
{
    public function stopConsume(): void;

    /**
     * Consume messages
     *
     * @param QueueInterface $queue
     * @param \Closure       $handler
     * @param string         $tag
     *
     * @throws ConsumerTimeoutExceedException
     */
    public function consume(QueueInterface $queue, \Closure $handler, string $tag = ''): void;
}
