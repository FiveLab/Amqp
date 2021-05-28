<?php

declare(strict_types=1);

namespace FiveLab\Component\Amqp\Tests\Functional\Adapter\AmqpLibSockets\Queue;

use FiveLab\Component\Amqp\Adapter\AmqpLib\Channel\AmqpChannelFactory;
use FiveLab\Component\Amqp\Adapter\AmqpLib\Connection\AmqpSocketsConnectionFactory;
use FiveLab\Component\Amqp\Adapter\AmqpLib\Queue\AmqpQueueFactory;
use FiveLab\Component\Amqp\Channel\Definition\ChannelDefinition;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use FiveLab\Component\Amqp\Tests\Functional\Adapter\QueueFactoryTestCase;

class AmqpQueueFactoryTest extends QueueFactoryTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createQueueFactory(QueueDefinition $definition): QueueFactoryInterface
    {
        $connectionFactory = new AmqpSocketsConnectionFactory([
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
