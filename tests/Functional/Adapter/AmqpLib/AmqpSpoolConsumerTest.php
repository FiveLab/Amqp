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
use FiveLab\Component\Amqp\Adapter\AmqpLib\Queue\AmqpQueueFactory;
use FiveLab\Component\Amqp\Channel\Definition\ChannelDefinition;
use FiveLab\Component\Amqp\Connection\Driver;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use FiveLab\Component\Amqp\Tests\Functional\Adapter\SpoolConsumerTestCase;

class AmqpSpoolConsumerTest extends SpoolConsumerTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createQueueFactory(QueueDefinition $definition): QueueFactoryInterface
    {
        $connectionFactory = new AmqpConnectionFactory($this->getRabbitMqDsn(Driver::AmqpLib));

        $channelFactory = new AmqpChannelFactory($connectionFactory, new ChannelDefinition());

        return new AmqpQueueFactory($channelFactory, $definition);
    }
}
