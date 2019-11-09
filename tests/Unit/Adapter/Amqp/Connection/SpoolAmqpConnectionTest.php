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
use FiveLab\Component\Amqp\Adapter\Amqp\Connection\SpoolAmqpConnection;
use FiveLab\Component\Amqp\Exception\BadCredentialsException;
use FiveLab\Component\Amqp\Exception\ConnectionException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SpoolAmqpConnectionTest extends TestCase
{
    /**
     * @test
     */
    public function shouldThrowExceptionIfWeTryCreateSpoolWithoutConnections(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Connections must be more than zero.');

        new SpoolAmqpConnection();
    }

    /**
     * @test
     */
    public function shouldSuccessConnectToFirst(): void
    {
        $connection1 = $this->createMock(AmqpConnection::class);
        $connection2 = $this->createMock(AmqpConnection::class);

        $connection1->expects(self::once())
            ->method('connect');

        $connection2->expects(self::never())
            ->method('connect');

        $spool = new SpoolAmqpConnection($connection1, $connection2);
        $spool->connect();
    }

    /**
     * @test
     */
    public function shouldSuccessConnectToSecondIfFirstThrowException(): void
    {
        $connection1 = $this->createMock(AmqpConnection::class);
        $connection2 = $this->createMock(AmqpConnection::class);

        $connection1->expects(self::once())
            ->method('connect')
            ->willThrowException(new ConnectionException('some'));

        $connection2->expects(self::once())
            ->method('connect');

        $spool = new SpoolAmqpConnection($connection1, $connection2);
        $spool->connect();
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfCannotConnectToAnyConnections(): void
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('some 1');

        $connection1 = $this->createMock(AmqpConnection::class);
        $connection2 = $this->createMock(AmqpConnection::class);

        $connection1->expects(self::once())
            ->method('connect')
            ->willThrowException(new ConnectionException('some 1'));

        $connection2->expects(self::once())
            ->method('connect')
            ->willThrowException(new ConnectionException('some 2'));

        $spool = new SpoolAmqpConnection($connection1, $connection2);
        $spool->connect();
    }

    /**
     * @test
     */
    public function shouldNotConnectToSecondIfThrowBadCredentialsException(): void
    {
        $this->expectException(BadCredentialsException::class);

        $connection1 = $this->createMock(AmqpConnection::class);
        $connection2 = $this->createMock(AmqpConnection::class);

        $connection1->expects(self::once())
            ->method('connect')
            ->willThrowException(new BadCredentialsException());

        $connection2->expects(self::never())
            ->method('connect');

        $spool = new SpoolAmqpConnection($connection1, $connection2);
        $spool->connect();
    }

    /**
     * @test
     */
    public function shouldNotConnectToSecondIfThrowAnyExceptions(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('some');

        $connection1 = $this->createMock(AmqpConnection::class);
        $connection2 = $this->createMock(AmqpConnection::class);

        $connection1->expects(self::once())
            ->method('connect')
            ->willThrowException(new \RuntimeException('some'));

        $connection2->expects(self::never())
            ->method('connect');

        $spool = new SpoolAmqpConnection($connection1, $connection2);
        $spool->connect();
    }

    /**
     * @test
     */
    public function shouldSuccessGetConnection(): void
    {
        $originConnection = $this->createMock(\AMQPConnection::class);
        $connection = $this->makeAmqpConnection();

        $connection->expects(self::once())
            ->method('getConnection')
            ->willReturn($originConnection);

        $spool = new SpoolAmqpConnection($connection);
        $spool->connect();

        self::assertEquals($originConnection, $spool->getConnection());
    }

    /**
     * @test
     */
    public function shouldFailGetConnectionIfWeNotConnected(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can\'t get origin AMQP connection. Please connect previously.');

        $connection = $this->makeAmqpConnection();

        $connection->expects(self::never())
            ->method('getConnection');

        $spool = new SpoolAmqpConnection($connection);
        $spool->getConnection();
    }

    /**
     * @test
     *
     * @param bool $connected
     *
     * @dataProvider provideBoolValues
     */
    public function shouldSuccessCheckIsConnected(bool $connected): void
    {
        $connection = $this->makeAmqpConnection();

        $connection->expects(self::once())
            ->method('isConnected')
            ->willReturn($connected);

        $spool = new SpoolAmqpConnection($connection);
        $spool->connect();

        self::assertEquals($connected, $spool->isConnected());
    }

    /**
     * @test
     */
    public function shouldNotConnectedIfWeDoNotConnectToAmqp(): void
    {
        $connection = $this->makeAmqpConnection();

        $connection->expects(self::never())
            ->method('isConnected');

        $spool = new SpoolAmqpConnection($connection);

        self::assertFalse($spool->isConnected());
    }

    /**
     * Provide bool values (0, 1)
     *
     * @return array
     */
    public function provideBoolValues(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * Make default amqp connection
     *
     * @return AmqpConnection|MockObject
     */
    private function makeAmqpConnection(): AmqpConnection
    {
        $connection = $this->createMock(AmqpConnection::class);

        $connection->expects(self::any())
            ->method('connect');

        return $connection;
    }
}
