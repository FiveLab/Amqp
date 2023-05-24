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

namespace FiveLab\Component\Amqp\Tests\Unit\Adapter\Amqp\Channel;

use FiveLab\Component\Amqp\Adapter\Amqp\Channel\AmqpChannel;
use FiveLab\Component\Amqp\Adapter\Amqp\Connection\AmqpConnection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AmqpChannelTest extends TestCase
{
    /**
     * @var AmqpConnection
     */
    private AmqpConnection $connection;

    /**
     * @var \AMQPChannel
     */
    private \AMQPChannel $amqpChannel;

    /**
     * @var AmqpChannel
     */
    private AmqpChannel $channel;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->connection = $this->createMock(AmqpConnection::class);
        $this->amqpChannel = $this->createMock(\AMQPChannel::class);
        $this->channel = new AmqpChannel($this->connection, $this->amqpChannel);
    }

    #[Test]
    public function shouldSuccessGetConnection(): void
    {
        $connection = $this->channel->getConnection();

        self::assertEquals($this->connection, $connection);
    }

    #[Test]
    public function shouldSuccessGetPrefetchCount(): void
    {
        $this->amqpChannel->expects(self::once())
            ->method('getPrefetchCount')
            ->willReturn(123);

        $prefetchCount = $this->channel->getPrefetchCount();

        self::assertEquals(123, $prefetchCount);
    }

    #[Test]
    public function shouldSuccessSetPrefetchCount(): void
    {
        $this->amqpChannel->expects(self::once())
            ->method('setPrefetchCount')
            ->with(321);

        $this->channel->setPrefetchCount(321);
    }

    #[Test]
    public function shouldSuccessStartTransaction(): void
    {
        $this->amqpChannel->expects(self::once())
            ->method('startTransaction');

        $this->channel->startTransaction();
    }

    #[Test]
    public function shouldSuccessCommitTransaction(): void
    {
        $this->amqpChannel->expects(self::once())
            ->method('commitTransaction');

        $this->channel->commitTransaction();
    }

    #[Test]
    public function shouldSuccessRollbackTransaction(): void
    {
        $this->amqpChannel->expects(self::once())
            ->method('rollbackTransaction');

        $this->channel->rollbackTransaction();
    }
}
