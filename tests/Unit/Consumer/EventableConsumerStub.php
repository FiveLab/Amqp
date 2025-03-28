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
use FiveLab\Component\Amqp\Consumer\Event;
use FiveLab\Component\Amqp\Consumer\EventableConsumerInterface;
use FiveLab\Component\Amqp\Consumer\EventableConsumerTrait;
use FiveLab\Component\Amqp\Queue\QueueInterface;

class EventableConsumerStub implements ConsumerInterface, EventableConsumerInterface
{
    use EventableConsumerTrait;

    public function __construct(private QueueInterface $queue)
    {
    }

    public function run(): void
    {
        $this->triggerEvent(Event::StopConsuming);
        $this->triggerEvent(Event::ConsumerTimeout);
        $this->triggerEvent(Event::ChangeConsumer, 'foo');
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
