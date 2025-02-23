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

class AmqpQueueFactory implements QueueFactoryInterface, \SplObserver
{
    private ?AmqpQueue $queue = null;

    public function __construct(
        private readonly ChannelFactoryInterface $channelFactory,
        private readonly QueueDefinition         $definition
    ) {
    }

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

        foreach ($this->definition->bindings as $binding) {
            $this->bind($binding->exchangeName, $binding->routingKey);
        }

        foreach ($this->definition->unbindings as $unBinding) {
            $this->unbind($unBinding->exchangeName, $unBinding->routingKey);
        }

        $this->queue = $queue;

        return $this->queue;
    }

    public function update(\SplSubject $subject): void
    {
        $this->queue = null;
    }

    private function bind(string $exchangeName, string $routingKey): void
    {
        /** @var AmqpChannel $channel */
        $channel = $this->channelFactory->create();

        $channel->getChannel()->queue_bind($this->definition->name, $exchangeName, $routingKey);
    }

    private function unbind(string $exchangeName, string $routingKey): void
    {
        /** @var AmqpChannel $channel */
        $channel = $this->channelFactory->create();

        $channel->getChannel()->queue_unbind($this->definition->name, $exchangeName, $routingKey);
    }
}
