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

namespace FiveLab\Component\Amqp\Tests\Unit\Adapter\Amqp\Connection;

use FiveLab\Component\Amqp\Adapter\Amqp\Connection\AmqpConnection;
use FiveLab\Component\Amqp\Exception\BadCredentialsException;
use FiveLab\Component\Amqp\Exception\ConnectionException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AmqpConnectionTest extends TestCase
{
    private \AMQPConnection $realConnection;
    private AmqpConnection $connection;

    protected function setUp(): void
    {
        if (!\class_exists(\AMQPConnection::class)) {
            self::markTestSkipped('The ext-amqp not installed.');
        }

        $this->realConnection = $this->createMock(\AMQPConnection::class);
        $this->connection = new AmqpConnection($this->realConnection);
    }

    #[Test]
    public function shouldSuccessConnect(): void
    {
        $this->realConnection->expects(self::once())
            ->method('connect');

        $this->connection->connect();
    }

    #[Test]
    public function shouldSuccessThrowExceptionIfCredentialsInvalid(): void
    {
        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage('Bad credentials for connect to RabbitMQ.');

        $this->realConnection->expects(self::once())
            ->method('connect')
            ->willThrowException(new \AMQPConnectionException('Can\'t connect. ACCESS_REFUSED. Please check credentials.'));

        $this->connection->connect();
    }

    #[Test]
    public function shouldSuccessThrowExceptionIfCannotConnect(): void
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Cannot connect to RabbitMQ.');

        $this->realConnection->expects(self::once())
            ->method('connect')
            ->willThrowException(new \AMQPConnectionException('Can\'t connect.'));

        $this->connection->connect();
    }

    #[Test]
    public function shouldSuccessGetConnection(): void
    {
        $realConnection = $this->connection->getConnection();

        self::assertEquals($this->realConnection, $realConnection);
    }

    #[Test]
    public function shouldSuccessCheckConnected(): void
    {
        $this->realConnection->expects(self::once())
            ->method('isConnected')
            ->willReturn(true);

        $connected = $this->connection->isConnected();

        self::assertTrue($connected);
    }

    #[Test]
    public function shouldSuccessDisconnect(): void
    {
        $this->realConnection->expects(self::once())
            ->method('disconnect');

        $this->connection->disconnect();
    }

    #[Test]
    public function shouldSuccessSetReadTimeout(): void
    {
        $this->realConnection->expects(self::once())
            ->method('setReadTimeout')
            ->with(1.55);

        $this->connection->setReadTimeout(1.55);
    }

    #[Test]
    public function shouldSuccessGetReadTimeout(): void
    {
        $this->realConnection->expects(self::once())
            ->method('getReadTimeout')
            ->willReturn(2.22);

        $readTimeout = $this->connection->getReadTimeout();

        self::assertEquals(2.22, $readTimeout);
    }
}
