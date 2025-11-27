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

namespace FiveLab\Component\Amqp\Tests\Unit\Consumer\Strategy;

use FiveLab\Component\Amqp\AmqpEvents;
use FiveLab\Component\Amqp\Consumer\Strategy\EventableTickHandler;
use FiveLab\Component\Amqp\Event\ConsumerTickEvent;
use FiveLab\Component\Amqp\Queue\QueueInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventableTickHandlerTest extends TestCase
{
    #[Test]
    public function shouldSuccessTick(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(new ConsumerTickEvent($queue, 'bla'), AmqpEvents::CONSUMER_TICK);

        $handler = new EventableTickHandler($dispatcher);

        ($handler)($queue, 'bla');
    }
}
