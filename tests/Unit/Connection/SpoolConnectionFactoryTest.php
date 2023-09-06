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

use FiveLab\Component\Amqp\Adapter\Amqp\Connection\AmqpConnectionFactory as AmqpExtConnectionFactory;
use FiveLab\Component\Amqp\Adapter\AmqpLib\Connection\AmqpConnectionFactory as AmqpLibConnectionFactory;
use FiveLab\Component\Amqp\Adapter\AmqpLib\Connection\AmqpSocketsConnectionFactory;
use FiveLab\Component\Amqp\Connection\ConnectionFactoryInterface;
use FiveLab\Component\Amqp\Connection\ConnectionInterface;
use FiveLab\Component\Amqp\Connection\Driver;
use FiveLab\Component\Amqp\Connection\Dsn;
use FiveLab\Component\Amqp\Connection\SpoolConnection;
use FiveLab\Component\Amqp\Connection\SpoolConnectionFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SpoolConnectionFactoryTest extends TestCase
{
    #[Test]
    public function shouldThrowExceptionIfWeTryCreateSpoolWithoutFactories(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Connection factories must be more than zero.');

        new SpoolConnectionFactory();
    }

    #[Test]
    public function shouldSuccessCreateConnection(): void
    {
        $connection1 = $this->createMock(ConnectionInterface::class);
        $connection2 = $this->createMock(ConnectionInterface::class);

        $factory1 = $this->createMock(ConnectionFactoryInterface::class);
        $factory2 = $this->createMock(ConnectionFactoryInterface::class);

        $factory1->expects(self::once())
            ->method('create')
            ->willReturn($connection1);

        $factory2->expects(self::once())
            ->method('create')
            ->willReturn($connection2);

        $spool = new SpoolConnectionFactory($factory1, $factory2);

        $connection = $spool->create();

        self::assertEquals(new SpoolConnection($connection1, $connection2), $connection);
    }

    #[Test]
    public function shouldSuccessCreateConnectionFromDsnForAmqpExt(): void
    {
        $spool = SpoolConnectionFactory::fromDsn(Dsn::fromDsn('amqp://host.net,host.com:5673/%2fsome?read_timeout=44&shuffle=0'));

        $factories = (new \ReflectionProperty($spool, 'factories'))->getValue($spool);
        $shuffleBeforeConnect = (new \ReflectionProperty($spool, 'shuffleBeforeConnect'))->getValue($spool);

        self::assertFalse($shuffleBeforeConnect);

        self::assertCount(2, $factories);

        self::assertEquals(new AmqpExtConnectionFactory(new Dsn(
            Driver::AmqpExt,
            'host.net',
            5673,
            '/some',
            options: ['read_timeout' => 44.0]
        )), $factories[0]);

        self::assertEquals(new AmqpExtConnectionFactory(new Dsn(
            Driver::AmqpExt,
            'host.com',
            5673,
            '/some',
            options: ['read_timeout' => 44.0]
        )), $factories[1]);
    }

    #[Test]
    public function shouldSuccessCreateConnectionFromDsnWithShuffle(): void
    {
        $spool = SpoolConnectionFactory::fromDsn(Dsn::fromDsn('amqp://host.net,host.com:5673/%2fsome?read_timeout=42&shuffle=1'));

        $factories = (new \ReflectionProperty($spool, 'factories'))->getValue($spool);
        $shuffleBeforeConnect = (new \ReflectionProperty($spool, 'shuffleBeforeConnect'))->getValue($spool);

        self::assertTrue($shuffleBeforeConnect);

        self::assertCount(2, $factories);

        self::assertEquals(new AmqpExtConnectionFactory(new Dsn(
            Driver::AmqpExt,
            'host.net',
            5673,
            '/some',
            options: ['read_timeout' => 42.0]
        )), $factories[0]);

        self::assertEquals(new AmqpExtConnectionFactory(new Dsn(
            Driver::AmqpExt,
            'host.com',
            5673,
            '/some',
            options: ['read_timeout' => 42.0]
        )), $factories[1]);
    }

    #[Test]
    public function shouldSuccessCreateConnectionFromDsnForAmqpLib(): void
    {
        $spool = SpoolConnectionFactory::fromDsn(Dsn::fromDsn('amqp-lib://some:pass@host.net,host.com'));

        $factories = (new \ReflectionProperty($spool, 'factories'))->getValue($spool);

        self::assertCount(2, $factories);

        self::assertEquals(new AmqpLibConnectionFactory(new Dsn(
            Driver::AmqpLib,
            'host.net',
            username: 'some',
            password: 'pass'
        )), $factories[0]);

        self::assertEquals(new AmqpLibConnectionFactory(new Dsn(
            Driver::AmqpLib,
            'host.com',
            username: 'some',
            password: 'pass'
        )), $factories[1]);
    }

    #[Test]
    public function shouldSuccessCreateConnectionFromDsnForAmqpLibSocket(): void
    {
        $spool = SpoolConnectionFactory::fromDsn(Dsn::fromDsn('amqp-sockets://host.net,host.com'));

        $factories = (new \ReflectionProperty($spool, 'factories'))->getValue($spool);

        self::assertCount(2, $factories);

        self::assertEquals(new AmqpSocketsConnectionFactory(new Dsn(
            Driver::AmqpSockets,
            'host.net',
        )), $factories[0]);

        self::assertEquals(new AmqpSocketsConnectionFactory(new Dsn(
            Driver::AmqpSockets,
            'host.com',
        )), $factories[1]);
    }
}
