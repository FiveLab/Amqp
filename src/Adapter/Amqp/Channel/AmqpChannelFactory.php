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

namespace FiveLab\Component\Amqp\Adapter\Amqp\Channel;

use FiveLab\Component\Amqp\Adapter\Amqp\Connection\AmqpConnection;
use FiveLab\Component\Amqp\Channel\ChannelFactoryInterface;
use FiveLab\Component\Amqp\Channel\ChannelInterface;
use FiveLab\Component\Amqp\Channel\Definition\ChannelDefinition;
use FiveLab\Component\Amqp\Connection\ConnectionFactoryInterface;

/**
 * The factory for create channel provided via php-amqp extension.
 */
class AmqpChannelFactory implements ChannelFactoryInterface, \SplObserver
{
    /**
     * @var ConnectionFactoryInterface
     */
    private $connectionFactory;

    /**
     * @var ChannelDefinition
     */
    private $definition;

    /**
     * @var AmqpChannel
     */
    private $channel;

    /**
     * Constructor.
     *
     * @param ConnectionFactoryInterface $connectionFactory
     * @param ChannelDefinition          $definition
     */
    public function __construct(ConnectionFactoryInterface $connectionFactory, ChannelDefinition $definition)
    {
        $this->connectionFactory = $connectionFactory;
        $this->definition = $definition;
    }

    /**
     * {@inheritdoc}
     */
    public function create(): ChannelInterface
    {
        if ($this->channel) {
            return $this->channel;
        }

        $connection = $this->connectionFactory->create();

        if (!$connection instanceof AmqpConnection) {
            throw new \InvalidArgumentException(\sprintf(
                'The connection "%s" does not supported for create channel.',
                \get_class($connection)
            ));
        }

        $connection->attach($this);

        if (!$connection->isConnected()) {
            $connection->connect();
        }

        $channel = new \AMQPChannel($connection->getConnection());

        $this->channel = new AmqpChannel($connection, $channel);

        return $this->channel;
    }

    /**
     * {@inheritdoc}
     */
    public function update(\SplSubject $subject)
    {
        $this->channel = null;
    }
}
