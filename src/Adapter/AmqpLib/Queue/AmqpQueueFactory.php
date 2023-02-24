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

namespace FiveLab\Component\Amqp\Adapter\AmqpLib\Queue;

use FiveLab\Component\Amqp\Adapter\AmqpLib\Channel\AmqpChannel;
use FiveLab\Component\Amqp\Adapter\AmqpLib\Connection\AmqpConnection;
use FiveLab\Component\Amqp\Channel\ChannelFactoryInterface;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use FiveLab\Component\Amqp\Queue\QueueInterface;

/**
 * The factory for create queues provided via php-amqplib library.
 */
class AmqpQueueFactory implements QueueFactoryInterface, \SplObserver
{
    /**
     * @var ChannelFactoryInterface
     */
    private ChannelFactoryInterface $channelFactory;

    /**
     * @var QueueDefinition
     */
    private QueueDefinition $definition;

    /**
     * @var AmqpQueue|null
     */
    private ?AmqpQueue $queue = null;

    /**
     * Constructor.
     *
     * @param ChannelFactoryInterface $channelFactory
     * @param QueueDefinition         $definition
     */
    public function __construct(ChannelFactoryInterface $channelFactory, QueueDefinition $definition)
    {
        $this->channelFactory = $channelFactory;
        $this->definition = $definition;
    }

    /**
     * {@inheritdoc}
     */
    public function create(): QueueInterface
    {
        if ($this->queue) {
            return $this->queue;
        }

        $channel = $this->channelFactory->create();

        if (!$channel instanceof AmqpChannel) {
            throw new \InvalidArgumentException(\sprintf(
                'The channel "%s" does not support for create queue.',
                \get_class($channel)
            ));
        }

        /** @var AmqpConnection $connection */
        $connection = $channel->getConnection();
        $connection->attach($this);

        $queue = new AmqpQueue($channel, $this->definition);
        $queue->declare();

        foreach ($this->definition->getBindings() as $binding) {
            $this->bind($binding->getExchangeName(), $binding->getRoutingKey());
        }

        foreach ($this->definition->getUnBindings() as $unBinding) {
            $this->unbind($unBinding->getExchangeName(), $unBinding->getRoutingKey());
        }

        $this->queue = $queue;

        return $this->queue;
    }

    /**
     * {@inheritdoc}
     */
    public function update(\SplSubject $subject): void
    {
        $this->queue = null;
    }

    /**
     * Bind queue to route
     *
     * @param string $exchangeName
     * @param string $routingKey
     */
    private function bind(string $exchangeName, string $routingKey): void
    {
        /** @var AmqpChannel $channel */
        $channel = $this->channelFactory->create();

        $channel->getChannel()->queue_bind($this->definition->getName(), $exchangeName, $routingKey);
    }

    /**
     * Unbind queue from route
     *
     * @param string $exchangeName
     * @param string $routingKey
     */
    private function unbind(string $exchangeName, string $routingKey): void
    {
        /** @var AmqpChannel $channel */
        $channel = $this->channelFactory->create();

        $channel->getChannel()->queue_unbind($this->definition->getName(), $exchangeName, $routingKey);
    }
}
