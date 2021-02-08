<?php

declare(strict_types=1);

namespace FiveLab\Component\Amqp\Adapter\AmqpLib\Channel;

use FiveLab\Component\Amqp\Adapter\AmqpLib\Connection\AmqpConnection;
use FiveLab\Component\Amqp\Channel\ChannelFactoryInterface;
use FiveLab\Component\Amqp\Channel\ChannelInterface;
use FiveLab\Component\Amqp\Channel\Definition\ChannelDefinition;
use FiveLab\Component\Amqp\Connection\ConnectionFactoryInterface;

/**
 * The factory for create channel provided via php-amqplib library.
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
                'The connection "%s" does not support creating php-amqplib channels.',
                \get_class($connection)
            ));
        }

        $connection->attach($this);

        $channel = $connection->getConnection()->channel();

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
