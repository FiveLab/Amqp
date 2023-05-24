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

namespace FiveLab\Component\Amqp\Tests\Unit\Adapter\Amqp\Queue;

use FiveLab\Component\Amqp\Adapter\Amqp\Channel\AmqpChannel;
use FiveLab\Component\Amqp\Adapter\Amqp\Message\AmqpReceivedMessage;
use FiveLab\Component\Amqp\Adapter\Amqp\Queue\AmqpQueue;
use FiveLab\Component\Amqp\Tests\Unit\Adapter\Amqp\AmqpAdapterHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AmqpQueueTest extends TestCase
{
    /**
     * @var \AMQPQueue
     */
    private \AMQPQueue $originQueue;

    /**
     * @var AmqpChannel
     */
    private AmqpChannel $originChannel;

    /**
     * @var AmqpQueue
     */
    private AmqpQueue $queue;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->originQueue = $this->createMock(\AMQPQueue::class);
        $this->originChannel = $this->createMock(AmqpChannel::class);

        $this->queue = new AmqpQueue($this->originChannel, $this->originQueue);
    }

    #[Test]
    public function shouldSuccessGetName(): void
    {
        $this->originQueue->expects(self::once())
            ->method('getName')
            ->willReturn('some');

        self::assertEquals('some', $this->queue->getName());
    }

    #[Test]
    public function shouldSuccessPurge(): void
    {
        $this->originQueue->expects(self::once())
            ->method('purge');

        $this->queue->purge();
    }

    #[Test]
    public function shouldSuccessGetChannel(): void
    {
        $channel = $this->queue->getChannel();

        self::assertEquals($this->originChannel, $channel);
    }

    #[Test]
    public function shouldSuccessConsume(): void
    {
        $closure = static function () {
        };

        $this->originQueue->expects(self::once())
            ->method('consume')
            ->with($closure);

        $this->queue->consume($closure);
    }

    #[Test]
    public function shouldSuccessConsumeWithConsumerTag(): void
    {
        $closure = static function () {
        };

        $this->originQueue->expects(self::once())
            ->method('consume')
            ->with($closure, 0, 'some.foo.bar');

        $this->queue->consume($closure, 'some.foo.bar');
    }

    #[Test]
    public function shouldSuccessCancelConsumer(): void
    {
        $this->originQueue->expects(self::once())
            ->method('cancel')
            ->with('some-foo');

        $this->queue->cancelConsumer('some-foo');
    }

    #[Test]
    public function shouldSuccessGetMessage(): void
    {
        $envelope = AmqpAdapterHelper::makeEnvelope($this);

        $this->originQueue->expects(self::once())
            ->method('get')
            ->willReturn($envelope);

        $message = $this->queue->get();

        self::assertEquals(new AmqpReceivedMessage($this->originQueue, $envelope), $message);
    }

    #[Test]
    public function shouldSuccessGetMessageWithNull(): void
    {
        $this->originQueue->expects(self::once())
            ->method('get')
            ->willReturn(null);

        $message = $this->queue->get();

        self::assertNull($message);
    }

    #[Test]
    public function shouldSuccessGetCountMessages(): void
    {
        $this->originQueue->expects(self::once())
            ->method('declareQueue')
            ->willReturn(123);

        $messages = $this->queue->countMessages();

        self::assertEquals(123, $messages);
    }
}
