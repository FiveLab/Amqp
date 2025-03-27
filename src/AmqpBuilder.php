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

namespace FiveLab\Component\Amqp;

use FiveLab\Component\Amqp\Adapter\Amqp\Channel\AmqpChannelFactory;
use FiveLab\Component\Amqp\Adapter\Amqp\Connection\AmqpConnectionFactory;
use FiveLab\Component\Amqp\Adapter\Amqp\Exchange\AmqpExchangeFactory;
use FiveLab\Component\Amqp\Adapter\Amqp\Queue\AmqpQueueFactory;
use FiveLab\Component\Amqp\Channel\ChannelFactoryInterface;
use FiveLab\Component\Amqp\Channel\Definition\ChannelDefinition;
use FiveLab\Component\Amqp\Connection\ConnectionFactoryInterface;
use FiveLab\Component\Amqp\Connection\Dsn;
use FiveLab\Component\Amqp\Exchange\Definition\ExchangeDefinition;
use FiveLab\Component\Amqp\Exchange\ExchangeFactoryInterface;
use FiveLab\Component\Amqp\Publisher\Middleware\PublisherMiddlewares;
use FiveLab\Component\Amqp\Publisher\Publisher;
use FiveLab\Component\Amqp\Publisher\PublisherInterface;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;

class AmqpBuilder
{
    private readonly Dsn $dsn;
    private ?ConnectionFactoryInterface $connectionFactory = null;
    private ?ChannelFactoryInterface $channelFactory = null;

    public function __construct(Dsn|string $dsn)
    {
        if (\is_string($dsn)) {
            $dsn = Dsn::fromDsn($dsn);
        }

        $this->dsn = $dsn;
    }

    public function createConnection(): ConnectionFactoryInterface
    {
        if (!$this->connectionFactory) {
            $this->connectionFactory = new AmqpConnectionFactory($this->dsn);
        }

        return $this->connectionFactory;
    }

    public function createChannel(): ChannelFactoryInterface
    {
        if (!$this->channelFactory) {
            $this->channelFactory = new AmqpChannelFactory($this->createConnection(), new ChannelDefinition());
        }

        return $this->channelFactory;
    }

    public function createExchange(ExchangeDefinition $definition): ExchangeFactoryInterface
    {
        return new AmqpExchangeFactory($this->createChannel(), $definition);
    }

    public function createPublisher(ExchangeDefinition $definition, ?PublisherMiddlewares $middlewares = null): PublisherInterface
    {
        $exchange = $this->createExchange($definition);

        return new Publisher($exchange, $middlewares ?: new PublisherMiddlewares());
    }

    public function createQueue(QueueDefinition $definition): QueueFactoryInterface
    {
        $channel = $this->createChannel();

        return new AmqpQueueFactory($channel, $definition);
    }
}
