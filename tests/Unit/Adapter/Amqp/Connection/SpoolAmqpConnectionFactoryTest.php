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
use FiveLab\Component\Amqp\Adapter\Amqp\Connection\AmqpConnectionFactory;
use FiveLab\Component\Amqp\Adapter\Amqp\Connection\SpoolAmqpConnection;
use FiveLab\Component\Amqp\Adapter\Amqp\Connection\SpoolAmqpConnectionFactory;
use PHPUnit\Framework\TestCase;

class SpoolAmqpConnectionFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function shouldThrowExceptionIfWeTryCreateSpoolWithoutFactories(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Connection factories must be more than zero.');

        new SpoolAmqpConnectionFactory();
    }

    /**
     * @test
     */
    public function shouldSuccessCreateConnection(): void
    {
        $connection1 = $this->createMock(AmqpConnection::class);
        $connection2 = $this->createMock(AmqpConnection::class);

        $factory1 = $this->createMock(AmqpConnectionFactory::class);
        $factory2 = $this->createMock(AmqpConnectionFactory::class);

        $factory1->expects(self::once())
            ->method('create')
            ->willReturn($connection1);

        $factory2->expects(self::once())
            ->method('create')
            ->willReturn($connection2);

        $spool = new SpoolAmqpConnectionFactory($factory1, $factory2);

        $connection = $spool->create();

        self::assertEquals(new SpoolAmqpConnection($connection1, $connection2), $connection);
    }
}
