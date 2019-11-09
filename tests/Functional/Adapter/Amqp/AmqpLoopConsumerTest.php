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
use FiveLab\Component\Amqp\Adapter\Amqp\Queue\AmqpQueueFactory;
use FiveLab\Component\Amqp\Channel\Definition\ChannelDefinition;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use FiveLab\Component\Amqp\Tests\Functional\Adapter\LoopConsumerTestCase;

class AmqpLoopConsumerTest extends LoopConsumerTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createQueueFactory(QueueDefinition $definition): QueueFactoryInterface
    {
        $connectionFactory = new AmqpConnectionFactory([
            'host'         => $this->getRabbitMqHost(),
            'port'         => $this->getRabbitMqPort(),
            'vhost'        => $this->getRabbitMqVhost(),
            'login'        => $this->getRabbitMqLogin(),
            'password'     => $this->getRabbitMqPassword(),
            'read_timeout' => 2,
        ]);

        $channelFactory = new AmqpChannelFactory($connectionFactory, new ChannelDefinition());

        return new AmqpQueueFactory($channelFactory, $definition);
    }
}
