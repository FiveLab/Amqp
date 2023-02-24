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

namespace FiveLab\Component\Amqp\Tests\Functional\Adapter\AmqpLibSockets;

use FiveLab\Component\Amqp\Adapter\AmqpLib\Connection\AmqpSocketsConnectionFactory;
use FiveLab\Component\Amqp\Connection\ConnectionFactoryInterface;
use FiveLab\Component\Amqp\Tests\Functional\Adapter\AmqpLib\SpoolConnectionTest as SpoolSocketsConnectionTestCase;

class SpoolConnectionTest extends SpoolSocketsConnectionTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createConnectionFactory(): ConnectionFactoryInterface
    {
        return new AmqpSocketsConnectionFactory([
            'host'         => $this->getRabbitMqHost(),
            'port'         => $this->getRabbitMqPort(),
            'vhost'        => $this->getRabbitMqVhost(),
            'login'        => $this->getRabbitMqLogin(),
            'password'     => $this->getRabbitMqPassword(),
            'read_timeout' => 2,
        ]);
    }
}
