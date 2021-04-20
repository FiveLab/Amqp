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

use FiveLab\Component\Amqp\Connection\ConnectionFactoryInterface;
use FiveLab\Component\Amqp\Connection\ConnectionInterface;
use FiveLab\Component\Amqp\Connection\SpoolConnection;
use FiveLab\Component\Amqp\Connection\SpoolConnectionFactory;
use PHPUnit\Framework\TestCase;

class SpoolConnectionFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function shouldThrowExceptionIfWeTryCreateSpoolWithoutFactories(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Connection factories must be more than zero.');

        new SpoolConnectionFactory();
    }

    /**
     * @test
     */
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
}
