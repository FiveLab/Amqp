<?php

declare(strict_types = 1);

namespace FiveLab\Component\Amqp\Adapter\AmqpLib\Exchange;

use FiveLab\Component\Amqp\Adapter\AmqpLib\Channel\AmqpChannel;
use FiveLab\Component\Amqp\Adapter\AmqpLib\Connection\AmqpConnection;
use FiveLab\Component\Amqp\Channel\ChannelFactoryInterface;
use FiveLab\Component\Amqp\Exchange\Definition\ExchangeDefinition;
use FiveLab\Component\Amqp\Exchange\ExchangeFactoryInterface;
use FiveLab\Component\Amqp\Exchange\ExchangeInterface;

/**
 * The factory for create exchanges provided via php-amqplib library.
 */
class AmqpExchangeFactory implements ExchangeFactoryInterface, \SplObserver
{
    /**
     * @var ChannelFactoryInterface
     */
    private $channelFactory;

    /**
     * @var ExchangeDefinition
     */
    private $definition;

    /**
     * @var AmqpExchange
     */
    private $exchange;

    /**
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
                $channel
            ));
        }

        /** @var AmqpConnection $connection */
        $connection = $channel->getConnection();

        $connection->attach($this);

        $exchange = new AmqpExchange($channel, $this->definition);
        $exchange->declare();

        foreach ($this->definition->getBindings() as $binding) {
            $exchange->bind($binding->getExchangeName(), $binding->getRoutingKey());
        }

        foreach ($this->definition->getUnBindings() as $unbinding) {
            $exchange->unbind($unbinding->getExchangeName(), $unbinding->getRoutingKey());
        }

        $this->exchange = $exchange;

        return $this->exchange;
    }

    /**
     * {@inheritdoc}
     */
    public function update(\SplSubject $subject)
    {
        $this->exchange = null;
    }
}
