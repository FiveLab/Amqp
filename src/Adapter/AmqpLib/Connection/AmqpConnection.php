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

/**
 * The connection provided via php-amqplib library.
 */
class AmqpConnection implements ConnectionInterface
{
    use SplSubjectTrait;

    /**
     * Constructor.
     *
     * @param AbstractConnection $connection
     * @param float              $readTimeout
     */
    public function __construct(
        private readonly AbstractConnection $connection,
        private float                       $readTimeout
    ) {
    }

    /**
     * Closes connection
     */
    public function __destruct()
    {
        $this->connection->close();
    }

    /**
     * Returns original connection
     * This method establishes the connection
     *
     * @return AbstractConnection
     */
    public function getConnection(): AbstractConnection
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function connect(): void
    {
        if (!$this->connection->isConnected()) {
            $this->connection->reconnect();
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
        $this->connection->close();

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
        $this->readTimeout = $timeout;
    }

    /**
     * {@inheritdoc}
     */
    public function getReadTimeout(): float
    {
        return $this->readTimeout;
    }
}
