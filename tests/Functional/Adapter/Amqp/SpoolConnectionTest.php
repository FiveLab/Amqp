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

namespace FiveLab\Component\Amqp\Tests\Functional\Adapter\Amqp;

use FiveLab\Component\Amqp\Adapter\Amqp\Channel\AmqpChannelFactory;
use FiveLab\Component\Amqp\Adapter\Amqp\Connection\AmqpConnectionFactory;
use FiveLab\Component\Amqp\Adapter\Amqp\Exchange\AmqpExchangeFactory;
use FiveLab\Component\Amqp\Adapter\Amqp\Queue\AmqpQueueFactory;
use FiveLab\Component\Amqp\Connection\ConnectionFactoryInterface;
use FiveLab\Component\Amqp\Connection\Driver;
use FiveLab\Component\Amqp\Tests\Functional\Adapter\SpoolConnectionTestCase;

class SpoolConnectionTest extends SpoolConnectionTestCase
{
    protected function createConnectionFactory(): ConnectionFactoryInterface
    {
        return new AmqpConnectionFactory($this->getRabbitMqDsn(Driver::AmqpExt));
    }

    protected function getClasses(): array
    {
        return [
            self::CHANNEL_CLASS  => AmqpChannelFactory::class,
            self::EXCHANGE_CLASS => AmqpExchangeFactory::class,
            self::QUEUE_CLASS    => AmqpQueueFactory::class,
        ];
    }
}
