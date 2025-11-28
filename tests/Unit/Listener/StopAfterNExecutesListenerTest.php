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

namespace FiveLab\Component\Amqp\Tests\Unit\Listener;

use FiveLab\Component\Amqp\Consumer\ConsumerInterface;
use FiveLab\Component\Amqp\Event\ProcessedMessageEvent;
use FiveLab\Component\Amqp\Listener\StopAfterNExecutesListener;
use FiveLab\Component\Amqp\Message\ReceivedMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StopAfterNExecutesListenerTest extends TestCase
{
    #[Test]
    public function shouldSuccessGetListeners(): void
    {
        self::assertEquals([
            'amqp.processed_message' => ['onProcessedMessage', -1024],
        ], StopAfterNExecutesListener::getSubscribedEvents());
    }

    #[Test]
    public function shouldSuccessStopAfterReachedLimit(): void
    {
        $stopped = false;
        $listener = new StopAfterNExecutesListener(2);
        $consumer = $this->createMock(ConsumerInterface::class);

        $consumer->expects($this->any())
            ->method('stop')
            ->willReturnCallback(static function () use (&$stopped): void {
                $stopped = true;
            });

        $event = new ProcessedMessageEvent($this->createMock(ReceivedMessage::class), $consumer);

        $listener->onProcessedMessage($event);
        self::assertFalse($stopped);
        $listener->onProcessedMessage($event);
        self::assertTrue($stopped);
    }
}
