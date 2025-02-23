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

readonly class AmqpChannel implements ChannelInterface
{
    public function __construct(
        private AmqpConnection|SpoolConnection $connection,
        private \AMQPChannel                   $channel
    ) {
    }

    public function getChannel(): \AMQPChannel
    {
        return $this->channel;
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function getPrefetchCount(): int
    {
        return $this->channel->getPrefetchCount();
    }

    public function setPrefetchCount(int $prefetchCount): void
    {
        $this->channel->setPrefetchCount($prefetchCount);
    }

    public function startTransaction(): void
    {
        $this->channel->startTransaction();
    }

    public function commitTransaction(): void
    {
        $this->channel->commitTransaction();
    }

    public function rollbackTransaction(): void
    {
        $this->channel->rollbackTransaction();
    }
}
