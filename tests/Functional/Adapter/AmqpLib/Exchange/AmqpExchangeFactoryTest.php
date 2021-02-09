<?php

declare(strict_types=1);

namespace FiveLab\Component\Amqp\Tests\Functional\Adapter\AmqpLib\Exchange;

use FiveLab\Component\Amqp\Adapter\AmqpLib\Channel\AmqpChannelFactory;
use FiveLab\Component\Amqp\Adapter\AmqpLib\Connection\AmqpConnectionFactory;
use FiveLab\Component\Amqp\Adapter\AmqpLib\Exchange\AmqpExchangeFactory;
use FiveLab\Component\Amqp\Channel\Definition\ChannelDefinition;
use FiveLab\Component\Amqp\Exchange\Definition\ExchangeDefinition;
use FiveLab\Component\Amqp\Exchange\ExchangeFactoryInterface;
use FiveLab\Component\Amqp\Tests\Functional\Adapter\ExchangeFactoryTestCase;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;

class AmqpExchangeFactoryTest extends ExchangeFactoryTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createExchangeFactory(ExchangeDefinition $definition): ExchangeFactoryInterface
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

        return new AmqpExchangeFactory($channelFactory, $definition);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionOnCreatePassiveIfExchangeNotFound(): void
    {
        $this->expectException(AMQPProtocolChannelException::class);
        $this->expectExceptionMessage(\sprintf(
            'NOT_FOUND - no exchange \'some\' in vhost \'%s\'',
            $this->getRabbitMqVhost()
        ));

        $definition = new ExchangeDefinition('some', AMQP_EX_TYPE_DIRECT, true, true);

        $factory = $this->createExchangeFactory($definition);
        $factory->create();
    }
}
