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
     * @var AmqpQueue|null
     */
    private ?AmqpQueue $queue = null;

    /**
     * Constructor.
     *
     * @param ChannelFactoryInterface $channelFactory
     * @param QueueDefinition         $definition
     */
    public function __construct(
        private readonly ChannelFactoryInterface $channelFactory,
        private readonly QueueDefinition         $definition
    ) {
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

        $queue->setName($this->definition->name);
        $queue->setFlags($flags);
        $queue->setArguments($this->definition->arguments->toArray());

        $queue->declareQueue();

        foreach ($this->definition->bindings as $binding) {
            $queue->bind($binding->exchangeName, $binding->routingKey);
        }

        foreach ($this->definition->unbindings as $unBinding) {
            $queue->unbind($unBinding->exchangeName, $unBinding->routingKey);
        }

        $this->queue = new AmqpQueue($channel, $queue);

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
     * Calculate flags for queue
     *
     * @return int
     */
    private function calculateFlagsForQueue(): int
    {
        $flags = AMQP_NOPARAM;

        if ($this->definition->durable) {
            $flags |= AMQP_DURABLE;
        }

        if ($this->definition->exclusive) {
            $flags |= AMQP_EXCLUSIVE;
        }

        if ($this->definition->passive) {
            $flags |= AMQP_PASSIVE;
        }

        if ($this->definition->autoDelete) {
            $flags |= AMQP_AUTODELETE;
        }

        return $flags;
    }
}
