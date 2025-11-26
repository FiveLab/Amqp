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

use FiveLab\Component\Amqp\Event\ConsumerTickEvent;
use FiveLab\Component\Amqp\Queue\QueueInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class EventableTickHandler
{
    public function __construct(private EventDispatcherInterface $eventDispatcher)
    {
    }

    public function __invoke(QueueInterface $queue, string $consumerTag): void
    {
        $this->eventDispatcher->dispatch(new ConsumerTickEvent($queue, $consumerTag));
    }
}
