<?php

declare(strict_types=1);

namespace FiveLab\Component\Amqp\Tests\Functional\Adapter\AmqpLibSockets\Exchange;

use FiveLab\Component\Amqp\Adapter\AmqpLib\Channel\AmqpChannelFactory;
use FiveLab\Component\Amqp\Adapter\AmqpLib\Connection\AmqpSocketsConnectionFactory;
use FiveLab\Component\Amqp\Adapter\AmqpLib\Exchange\AmqpExchangeFactory;
use FiveLab\Component\Amqp\Channel\Definition\ChannelDefinition;
use FiveLab\Component\Amqp\Exchange\Definition\ExchangeDefinition;
use FiveLab\Component\Amqp\Exchange\ExchangeFactoryInterface;
use FiveLab\Component\Amqp\Tests\Functional\Adapter\ExchangeTestCase;

class AmqpExchangeTest extends ExchangeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createExchangeFactory(ExchangeDefinition $definition): ExchangeFactoryInterface
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

        return new AmqpExchangeFactory($channelFactory, $definition);
    }
}
