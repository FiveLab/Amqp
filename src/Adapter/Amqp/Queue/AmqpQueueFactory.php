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

namespace FiveLab\Component\Amqp\Adapter\Amqp\Queue;

use FiveLab\Component\Amqp\Adapter\Amqp\Channel\AmqpChannel;
use FiveLab\Component\Amqp\Adapter\Amqp\Connection\AmqpConnection;
use FiveLab\Component\Amqp\Channel\ChannelFactoryInterface;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use FiveLab\Component\Amqp\Queue\QueueInterface;

/**
 * The factory for create queues provided via php-amqp extension.
 */
class AmqpQueueFactory implements QueueFactoryInterface, \SplObserver
{
    /**
     * @var ChannelFactoryInterface
     */
    private $channelFactory;

    /**
     * @var QueueDefinition
     */
    private $definition;

    /**
     * @var AmqpQueue
     */
    private $queue;

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

        $queue = new \AMQPQueue($channel->getChannel());

        $flags = $this->calculateFlagsForQueue();

        $queue->setName($this->definition->getName());
        $queue->setFlags($flags);

        $queue->declareQueue();

        foreach ($this->definition->getBindings() as $binding) {
            $queue->bind($binding->getExchangeName(), $binding->getRoutingKey());
        }

        foreach ($this->definition->getUnBindings() as $unBinding) {
            $queue->unbind($unBinding->getExchangeName(), $unBinding->getRoutingKey());
        }

        $this->queue = new AmqpQueue($channel, $queue);

        return $this->queue;
    }

    /**
     * {@inheritdoc}
     */
    public function update(\SplSubject $subject)
    {
        $this->queue = null;
    }

    /**
     * Calculate flags for queue
     *
     * @return int
     */
    private function calculateFlagsForQueue(): int
    {
        $flags = AMQP_NOPARAM;

        if ($this->definition->isDurable()) {
            $flags |= AMQP_DURABLE;
        }

        if ($this->definition->isExclusive()) {
            $flags |= AMQP_EXCLUSIVE;
        }

        if ($this->definition->isPassive()) {
            $flags |= AMQP_PASSIVE;
        }

        if ($this->definition->isAutoDelete()) {
            $flags |= AMQP_AUTODELETE;
        }

        return $flags;
    }
}
