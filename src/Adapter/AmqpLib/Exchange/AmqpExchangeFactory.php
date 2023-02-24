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

namespace FiveLab\Component\Amqp\Adapter\AmqpLib\Exchange;

use FiveLab\Component\Amqp\Adapter\AmqpLib\Channel\AmqpChannel;
use FiveLab\Component\Amqp\Adapter\AmqpLib\Connection\AmqpConnection;
use FiveLab\Component\Amqp\Channel\ChannelFactoryInterface;
use FiveLab\Component\Amqp\Exchange\Definition\ExchangeDefinition;
use FiveLab\Component\Amqp\Exchange\ExchangeFactoryInterface;
use FiveLab\Component\Amqp\Exchange\ExchangeInterface;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * The factory for create exchanges provided via php-amqplib library.
 */
class AmqpExchangeFactory implements ExchangeFactoryInterface, \SplObserver
{
    /**
     * @var ChannelFactoryInterface
     */
    private ChannelFactoryInterface $channelFactory;

    /**
     * @var ExchangeDefinition
     */
    private ExchangeDefinition $definition;

    /**
     * @var AmqpExchange|null
     */
    private ?AmqpExchange $exchange = null;

    /**
     * Constructor.
     *
     * @param ChannelFactoryInterface $channelFactory
     * @param ExchangeDefinition      $definition
     */
    public function __construct(ChannelFactoryInterface $channelFactory, ExchangeDefinition $definition)
    {
        $this->channelFactory = $channelFactory;
        $this->definition = $definition;
    }

    /**
     * {@inheritdoc}
     */
    public function create(): ExchangeInterface
    {
        if ($this->exchange) {
            return $this->exchange;
        }

        $channel = $this->channelFactory->create();

        if (!$channel instanceof AmqpChannel) {
            throw new \InvalidArgumentException(\sprintf(
                'The channel "%s" does not support for create exchange.',
                \get_class($channel)
            ));
        }

        /** @var AmqpConnection $connection */
        $connection = $channel->getConnection();

        $connection->attach($this);

        $exchange = new AmqpExchange($channel, $this->definition);
        $this->declare();

        foreach ($this->definition->getBindings() as $binding) {
            $this->bind($binding->getExchangeName(), $binding->getRoutingKey());
        }

        foreach ($this->definition->getUnBindings() as $unbinding) {
            $this->unbind($unbinding->getExchangeName(), $unbinding->getRoutingKey());
        }

        $this->exchange = $exchange;

        return $this->exchange;
    }

    /**
     * {@inheritdoc}
     */
    public function update(\SplSubject $subject): void
    {
        $this->exchange = null;
    }

    /**
     * Declares exchange (creates or validates existing one)
     */
    private function declare(): void
    {
        $name = $this->definition->getName();

        // Default exchange always exists and "declare" call is not permitted
        if (!$name) {
            return;
        }

        $arguments = new AMQPTable();

        foreach ($this->definition->getArguments() as $argument) {
            $arguments->set($argument->getName(), $argument->getValue());
        }

        /** @var AmqpChannel $channel */
        $channel = $this->channelFactory->create();

        $channel->getChannel()->exchange_declare(
            $name,
            $this->definition->getType(),
            $this->definition->isPassive(),
            $this->definition->isDurable(),
            false,
            false,
            false,
            $arguments
        );
    }

    /**
     * Bind to route key.
     *
     * @param string $exchangeName
     * @param string $routingKey
     */
    private function bind(string $exchangeName, string $routingKey): void
    {
        /** @var AmqpChannel $channel */
        $channel = $this->channelFactory->create();

        $channel->getChannel()->exchange_bind($this->definition->getName(), $exchangeName, $routingKey);
    }

    /**
     * Unbind from route key.
     *
     * @param string $exchangeName
     * @param string $routingKey
     */
    private function unbind(string $exchangeName, string $routingKey): void
    {
        /** @var AmqpChannel $channel */
        $channel = $this->channelFactory->create();

        $channel->getChannel()->exchange_unbind($this->definition->getName(), $exchangeName, $routingKey);
    }
}
