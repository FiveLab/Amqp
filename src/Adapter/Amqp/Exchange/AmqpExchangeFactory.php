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

namespace FiveLab\Component\Amqp\Adapter\Amqp\Exchange;

use FiveLab\Component\Amqp\Adapter\Amqp\Channel\AmqpChannel;
use FiveLab\Component\Amqp\Adapter\Amqp\Connection\AmqpConnection;
use FiveLab\Component\Amqp\Channel\ChannelFactoryInterface;
use FiveLab\Component\Amqp\Exchange\Definition\ExchangeDefinition;
use FiveLab\Component\Amqp\Exchange\ExchangeFactoryInterface;
use FiveLab\Component\Amqp\Exchange\ExchangeInterface;

/**
 * The factory for create exchanges provided via php-amqp extension.
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

        $exchange = new \AMQPExchange($channel->getChannel());
        $flags = $this->calculateFlagsForExchange();

        $exchange->setName($this->definition->getName());
        $exchange->setType($this->definition->getType());
        $exchange->setFlags($flags);
        $exchange->setArguments($this->definition->getArguments()->toArray());

        if ('' !== $this->definition->getName()) {
            // We must declare only non-default exchanges.
            $exchange->declareExchange();
        }

        foreach ($this->definition->getBindings() as $binding) {
            $exchange->bind($binding->getExchangeName(), $binding->getRoutingKey());
        }

        foreach ($this->definition->getUnBindings() as $unbinding) {
            $exchange->unbind($unbinding->getExchangeName(), $unbinding->getRoutingKey());
        }

        $this->exchange = new AmqpExchange($channel, $exchange);

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
     * Calculate flags for exchange
     *
     * @return int
     */
    private function calculateFlagsForExchange(): int
    {
        $flags = AMQP_NOPARAM;

        if ($this->definition->isPassive()) {
            $flags |= AMQP_PASSIVE;
        }

        if ($this->definition->isDurable()) {
            $flags |= AMQP_DURABLE;
        }

        return $flags;
    }
}
