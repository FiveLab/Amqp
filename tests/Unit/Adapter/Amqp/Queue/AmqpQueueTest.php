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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AmqpQueueTest extends TestCase
{
    /**
     * @var \AMQPQueue|MockObject
     */
    private $originQueue;

    /**
     * @var \AMQPChannel|MockObject
     */
    private $originChannel;

    /**
     * @var AmqpQueue
     */
    private $queue;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->originQueue = $this->createMock(\AMQPQueue::class);
        $this->originChannel = $this->createMock(AmqpChannel::class);

        $this->queue = new AmqpQueue($this->originChannel, $this->originQueue);
    }

    /**
     * @test
     */
    public function shouldSuccessGetName(): void
    {
        $this->originQueue->expects(self::once())
            ->method('getName')
            ->willReturn('some');

        self::assertEquals('some', $this->queue->getName());
    }

    /**
     * @test
     */
    public function shouldSuccessPurge(): void
    {
        $this->originQueue->expects(self::once())
            ->method('purge');

        $this->queue->purge();
    }

    /**
     * @test
     */
    public function shouldSuccessGetChannel(): void
    {
        $channel = $this->queue->getChannel();

        self::assertEquals($this->originChannel, $channel);
    }

    /**
     * @test
     */
    public function shouldSuccessGetMessage(): void
    {
        $envelope = $this->createMock(\AMQPEnvelope::class);

        $this->originQueue->expects(self::once())
            ->method('get')
            ->willReturn($envelope);

        $message = $this->queue->get();

        self::assertEquals(new AmqpReceivedMessage($this->originQueue, $envelope), $message);
    }

    /**
     * @test
     */
    public function shouldSuccessGetMessageWithNull(): void
    {
        $this->originQueue->expects(self::once())
            ->method('get')
            ->willReturn(null);

        $message = $this->queue->get();

        self::assertNull($message);
    }
}
