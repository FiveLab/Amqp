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
use FiveLab\Component\Amqp\Channel\ChannelInterface;
use FiveLab\Component\Amqp\Connection\ConnectionInterface;
use FiveLab\Component\Amqp\Connection\SpoolConnection;

/**
 * The channel provided via php-amqp extension.
 */
class AmqpChannel implements ChannelInterface
{
    /**
     * @var AmqpConnection|SpoolConnection
     */
    private $connection;

    /**
     * @var \AMQPChannel
     */
    private \AMQPChannel $channel;

    /**
     * Constructor.
     *
     * @param object|AmqpConnection|SpoolConnection $connection
     * @param \AMQPChannel                          $channel
     */
    public function __construct(object $connection, \AMQPChannel $channel)
    {
        if (!$connection instanceof AmqpConnection && !$connection instanceof SpoolConnection) {
            throw new \InvalidArgumentException(\sprintf(
                'Invalid connection. Must be a "%s" or "%s". But "%s" given.',
                AmqpConnection::class,
                SpoolConnection::class,
                \get_class($connection)
            ));
        }

        $this->connection = $connection;
        $this->channel = $channel;
    }

    /**
     * Get original channel
     *
     * @return \AMQPChannel
     */
    public function getChannel(): \AMQPChannel
    {
        return $this->channel;
    }

    /**
     * Get the connection
     *
     * @return ConnectionInterface
     */
    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrefetchCount(): int
    {
        return $this->channel->getPrefetchCount();
    }

    /**
     * {@inheritdoc}
     */
    public function setPrefetchCount(int $prefetchCount): void
    {
        $this->channel->setPrefetchCount($prefetchCount);
    }

    /**
     * {@inheritdoc}
     */
    public function startTransaction(): void
    {
        $this->channel->startTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function commitTransaction(): void
    {
        $this->channel->commitTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function rollbackTransaction(): void
    {
        $this->channel->rollbackTransaction();
    }
}
