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

class AmqpConnection implements ConnectionInterface
{
    use SplSubjectTrait;

    public function __construct(private readonly \AMQPConnection $connection)
    {
    }

    public function getConnection(): \AMQPConnection
    {
        return $this->connection;
    }

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

    public function isConnected(): bool
    {
        return $this->connection->isConnected();
    }

    public function disconnect(): void
    {
        $this->connection->disconnect();

        $this->notify();
    }

    public function reconnect(): void
    {
        $this->disconnect();
        $this->connect();
    }

    public function setReadTimeout(float $timeout): void
    {
        $this->connection->setReadTimeout($timeout);
    }

    public function getReadTimeout(): float
    {
        return $this->connection->getReadTimeout();
    }
}
