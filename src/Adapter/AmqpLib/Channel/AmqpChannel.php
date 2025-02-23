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

namespace FiveLab\Component\Amqp\Adapter\AmqpLib\Channel;

use FiveLab\Component\Amqp\Adapter\AmqpLib\Connection\AmqpConnection;
use FiveLab\Component\Amqp\Channel\ChannelInterface;
use FiveLab\Component\Amqp\Connection\ConnectionInterface;
use FiveLab\Component\Amqp\Connection\SpoolConnection;
use PhpAmqpLib\Channel\AMQPChannel as AmqpLibChannel;

class AmqpChannel implements ChannelInterface
{
    private int $prefetchCount = 3;

    public function __construct(
        private readonly AmqpConnection|SpoolConnection $connection,
        private readonly AmqpLibChannel                 $channel
    ) {
    }

    public function __destruct()
    {
        $this->channel->close();
    }

    public function getChannel(): AmqpLibChannel
    {
        return $this->channel;
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function getPrefetchCount(): int
    {
        return $this->prefetchCount;
    }

    public function setPrefetchCount(int $prefetchCount): void
    {
        $this->prefetchCount = $prefetchCount;
        $this->channel->basic_qos(0, $prefetchCount, false);
    }

    public function startTransaction(): void
    {
        $this->channel->tx_select();
    }

    public function commitTransaction(): void
    {
        $this->channel->tx_commit();
    }

    public function rollbackTransaction(): void
    {
        $this->channel->tx_rollback();
    }
}
