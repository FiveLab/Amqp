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

namespace FiveLab\Component\Amqp\Tests\Unit\Consumer;

use FiveLab\Component\Amqp\Consumer\ConsumerInterface;
use FiveLab\Component\Amqp\Consumer\ConsumerStoppedReason;
use FiveLab\Component\Amqp\Consumer\EventableConsumerInterface;
use FiveLab\Component\Amqp\Event\ConsumerStoppedEvent;
use FiveLab\Component\Amqp\Queue\QueueInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventableConsumerStub implements ConsumerInterface, EventableConsumerInterface
{
    private ?EventDispatcherInterface $eventDispatcher = null;

    public function __construct(private QueueInterface $queue)
    {
    }

    public function setEventDispatcher(?EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getEventDispatcher(): ?EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function run(): void
    {
        $this->getEventDispatcher()->dispatch(new ConsumerStoppedEvent($this, ConsumerStoppedReason::StopConsuming));
        $this->getEventDispatcher()->dispatch(new ConsumerStoppedEvent($this, ConsumerStoppedReason::Timeout));
        $this->getEventDispatcher()->dispatch(new ConsumerStoppedEvent($this, ConsumerStoppedReason::ChangeConsumer));
    }

    public function getQueue(): QueueInterface
    {
        return $this->queue;
    }

    public function stop(): void
    {
        // TODO: Implement stop() method.
    }
}
