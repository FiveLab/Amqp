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

namespace FiveLab\Component\Amqp\Adapter\AmqpLib\Connection;

use FiveLab\Component\Amqp\Connection\ConnectionInterface;
use FiveLab\Component\Amqp\SplSubjectTrait;
use PhpAmqpLib\Connection\AbstractConnection;

class AmqpConnection implements ConnectionInterface
{
    use SplSubjectTrait;

    public function __construct(
        private readonly AbstractConnection $connection,
        private float                       $readTimeout
    ) {
    }

    public function __destruct()
    {
        $this->connection->close();
    }

    public function getConnection(): AbstractConnection
    {
        return $this->connection;
    }

    public function connect(): void
    {
        if (!$this->connection->isConnected()) {
            $this->connection->reconnect();
        }
    }

    public function isConnected(): bool
    {
        return $this->connection->isConnected();
    }

    public function disconnect(): void
    {
        $this->connection->close();

        $this->notify();
    }

    public function reconnect(): void
    {
        $this->disconnect();
        $this->connect();
    }

    public function setReadTimeout(float $timeout): void
    {
        $this->readTimeout = $timeout;
    }

    public function getReadTimeout(): float
    {
        return $this->readTimeout;
    }
}
