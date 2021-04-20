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

namespace FiveLab\Component\Amqp\Tests\Functional\Adapter\AmqpLib;

use FiveLab\Component\Amqp\Adapter\AmqpLib\Channel\AmqpChannelFactory;
use FiveLab\Component\Amqp\Adapter\AmqpLib\Connection\AmqpConnectionFactory;
use FiveLab\Component\Amqp\Adapter\AmqpLib\Exchange\AmqpExchangeFactory;
use FiveLab\Component\Amqp\Adapter\AmqpLib\Queue\AmqpQueueFactory;
use FiveLab\Component\Amqp\Connection\ConnectionFactoryInterface;
use FiveLab\Component\Amqp\Tests\Functional\Adapter\SpoolConnectionTestCase;

class SpoolConnectionTest extends SpoolConnectionTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createConnectionFactory(): ConnectionFactoryInterface
    {
        return new AmqpConnectionFactory([
            'host'         => $this->getRabbitMqHost(),
            'port'         => $this->getRabbitMqPort(),
            'vhost'        => $this->getRabbitMqVhost(),
            'login'        => $this->getRabbitMqLogin(),
            'password'     => $this->getRabbitMqPassword(),
            'read_timeout' => 2,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getClasses(): array
    {
        return [
            self::CHANNEL_CLASS  => AmqpChannelFactory::class,
            self::EXCHANGE_CLASS => AmqpExchangeFactory::class,
            self::QUEUE_CLASS    => AmqpQueueFactory::class,
        ];
    }
}
