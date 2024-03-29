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

namespace FiveLab\Component\Amqp\Adapter\Amqp\Connection;

use FiveLab\Component\Amqp\Connection\ConnectionInterface;
use FiveLab\Component\Amqp\Exception\BadCredentialsException;
use FiveLab\Component\Amqp\Exception\ConnectionException;
use FiveLab\Component\Amqp\SplSubjectTrait;

/**
 * The connection provided via php-amqp extension.
 */
class AmqpConnection implements ConnectionInterface
{
    use SplSubjectTrait;

    /**
     * Constructor.
     *
     * @param \AMQPConnection $connection
     */
    public function __construct(private readonly \AMQPConnection $connection)
    {
    }

    /**
     * Get original connection
     *
     * @return \AMQPConnection
     */
    public function getConnection(): \AMQPConnection
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function connect(): void
    {
        try {
            $this->connection->connect();
        } catch (\AMQPConnectionException $e) {
            if (\str_contains($e->getMessage(), 'ACCESS_REFUSED')) {
                throw new BadCredentialsException('Bad credentials for connect to RabbitMQ.', 0, $e);
            }

            throw new ConnectionException('Cannot connect to RabbitMQ.', 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected(): bool
    {
        return $this->connection->isConnected();
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect(): void
    {
        $this->connection->disconnect();

        $this->notify();
    }

    /**
     * {@inheritdoc}
     */
    public function reconnect(): void
    {
        $this->disconnect();
        $this->connect();
    }

    /**
     * {@inheritdoc}
     */
    public function setReadTimeout(float $timeout): void
    {
        // @phpstan-ignore-next-line
        $this->connection->setReadTimeout($timeout);
    }

    /**
     * {@inheritdoc}
     */
    public function getReadTimeout(): float
    {
        return $this->connection->getReadTimeout();
    }
}
