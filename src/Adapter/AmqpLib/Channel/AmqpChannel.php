<?php

declare(strict_types=1);

namespace FiveLab\Component\Amqp\Adapter\AmqpLib\Channel;

use FiveLab\Component\Amqp\Adapter\AmqpLib\Connection\AmqpConnection;
use FiveLab\Component\Amqp\Channel\ChannelInterface;
use FiveLab\Component\Amqp\Connection\ConnectionInterface;
use PhpAmqpLib\Channel\AMQPChannel as AmqpLibChannel;

/**
 * The channel provided via php-amqplib library.
 */
class AmqpChannel implements ChannelInterface
{
    /**
     * @var AmqpConnection
     */
    private $connection;

    /**
     * @var AmqpLibChannel
     */
    private $channel;

    /**
     * @var int
     */
    private $prefetchCount = 1;

    /**
     * @param AmqpConnection $connection
     * @param AmqpLibChannel $channel
     */
    public function __construct(AmqpConnection $connection, AmqpLibChannel $channel)
    {
        $this->connection = $connection;
        $this->channel = $channel;
    }

    /**
     * closes channel
     */
    public function __destruct()
    {
        $this->channel->close();
    }

    /**
     * Returns original channel
     *
     * @return AmqpLibChannel
     */
    public function getChannel(): AmqpLibChannel
    {
        return $this->channel;
    }

    /**
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
        return $this->prefetchCount;
    }

    /**
     * {@inheritdoc}
     */
    public function setPrefetchCount(int $prefetchCount): void
    {
        $this->prefetchCount = $prefetchCount;
        $this->channel->basic_qos(0, $prefetchCount, false);
    }

    /**
     * {@inheritdoc}
     */
    public function startTransaction(): void
    {
        $this->channel->tx_select();
    }

    /**
     * {@inheritdoc}
     */
    public function commitTransaction(): void
    {
        $this->channel->tx_commit();
    }

    /**
     * {@inheritdoc}
     */
    public function rollbackTransaction(): void
    {
        $this->channel->tx_rollback();
    }
}
