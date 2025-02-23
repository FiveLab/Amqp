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
use FiveLab\Component\Amqp\Connection\Driver;
use FiveLab\Component\Amqp\Tests\Functional\Adapter\AmqpLib\SpoolConnectionTest as SpoolSocketsConnectionTestCase;

class SpoolConnectionTest extends SpoolSocketsConnectionTestCase
{
    protected function createConnectionFactory(): ConnectionFactoryInterface
    {
        return new AmqpSocketsConnectionFactory($this->getRabbitMqDsn(Driver::AmqpSockets));
    }
}
