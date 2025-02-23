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

namespace FiveLab\Component\Amqp\Tests\Unit\Connection;

use FiveLab\Component\Amqp\Adapter\Amqp\Connection\AmqpConnection;
use FiveLab\Component\Amqp\Connection\ConnectionInterface;
use FiveLab\Component\Amqp\Connection\SpoolConnection;
use FiveLab\Component\Amqp\Exception\BadCredentialsException;
use FiveLab\Component\Amqp\Exception\ConnectionException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SpoolConnectionTest extends TestCase
{
    #[Test]
    public function shouldThrowExceptionIfWeTryCreateSpoolWithoutConnections(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Connections must be more than zero.');

        new SpoolConnection();
    }

    #[Test]
    public function shouldSuccessConnectToFirst(): void
    {
        $connection1 = $this->createMock(ConnectionInterface::class);
        $connection2 = $this->createMock(ConnectionInterface::class);

        $connection1->expects(self::once())
            ->method('connect');

        $connection2->expects(self::never())
            ->method('connect');

        $spool = new SpoolConnection($connection1, $connection2);
        $spool->connect();
    }

    #[Test]
    public function shouldSuccessConnectToSecondIfFirstThrowException(): void
    {
        $connection1 = $this->createMock(ConnectionInterface::class);
        $connection2 = $this->createMock(ConnectionInterface::class);

        $connection1->expects(self::once())
            ->method('connect')
            ->willThrowException(new ConnectionException('some'));

        $connection2->expects(self::once())
            ->method('connect');

        $spool = new SpoolConnection($connection1, $connection2);
        $spool->connect();
    }

    #[Test]
    public function shouldThrowExceptionIfCannotConnectToAnyConnections(): void
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('some 1');

        $connection1 = $this->createMock(ConnectionInterface::class);
        $connection2 = $this->createMock(ConnectionInterface::class);

        $connection1->expects(self::once())
            ->method('connect')
            ->willThrowException(new ConnectionException('some 1'));

        $connection2->expects(self::once())
            ->method('connect')
            ->willThrowException(new ConnectionException('some 2'));

        $spool = new SpoolConnection($connection1, $connection2);
        $spool->connect();
    }

    #[Test]
    public function shouldNotConnectToSecondIfThrowBadCredentialsException(): void
    {
        $this->expectException(BadCredentialsException::class);

        $connection1 = $this->createMock(ConnectionInterface::class);
        $connection2 = $this->createMock(ConnectionInterface::class);

        $connection1->expects(self::once())
            ->method('connect')
            ->willThrowException(new BadCredentialsException());

        $connection2->expects(self::never())
            ->method('connect');

        $spool = new SpoolConnection($connection1, $connection2);
        $spool->connect();
    }

    #[Test]
    public function shouldNotConnectToSecondIfThrowAnyExceptions(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('some');

        $connection1 = $this->createMock(ConnectionInterface::class);
        $connection2 = $this->createMock(ConnectionInterface::class);

        $connection1->expects(self::once())
            ->method('connect')
            ->willThrowException(new \RuntimeException('some'));

        $connection2->expects(self::never())
            ->method('connect');

        $spool = new SpoolConnection($connection1, $connection2);
        $spool->connect();
    }

    #[Test]
    public function shouldSuccessGetConnection(): void
    {
        $originConnection = $this->createMock(\AMQPConnection::class);
        $connection = $this->makeAmqpConnection();

        $connection->expects(self::once())
            ->method('getConnection')
            ->willReturn($originConnection);

        $spool = new SpoolConnection($connection);
        $spool->connect();

        self::assertEquals($originConnection, $spool->getConnection());
    }

    #[Test]
    public function shouldFailGetConnectionIfWeNotConnected(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can\'t get origin connection. Please connect previously.');

        $connection = $this->makeAmqpConnection();

        $connection->expects(self::never())
            ->method('getConnection');

        $spool = new SpoolConnection($connection);
        $spool->getConnection();
    }

    #[Test]
    #[TestWith([true])]
    #[TestWith([false])]
    public function shouldSuccessCheckIsConnected(bool $connected): void
    {
        $connection = $this->makeAmqpConnection();

        $connection->expects(self::once())
            ->method('isConnected')
            ->willReturn($connected);

        $spool = new SpoolConnection($connection);
        $spool->connect();

        self::assertEquals($connected, $spool->isConnected());
    }

    #[Test]
    public function shouldNotConnectedIfWeDoNotConnectToAmqp(): void
    {
        $connection = $this->makeAmqpConnection();

        $connection->expects(self::never())
            ->method('isConnected');

        $spool = new SpoolConnection($connection);

        self::assertFalse($spool->isConnected());
    }

    private function makeAmqpConnection(): AmqpConnection&MockObject
    {
        $connection = $this->createMock(AmqpConnection::class);

        $connection->expects(self::any())
            ->method('connect');

        return $connection;
    }
}
