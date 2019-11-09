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

use FiveLab\Component\Amqp\Exception\ConnectionException;

/**
 * Spool connection for "ext-amqp".
 */
class SpoolAmqpConnection extends AmqpConnection
{
    /**
     * @var array|AmqpConnection[]
     */
    private $connections;

    /**
     * @var AmqpConnection
     */
    private $originConnection;

    /**
     * Constructor.
     *
     * @param array|AmqpConnection ...$connections
     */
    public function __construct(AmqpConnection ...$connections)
    {
        if (!\count($connections)) {
            throw new \InvalidArgumentException('Connections must be more than zero.');
        }

        $this->connections = $connections;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection(): \AMQPConnection
    {
        return $this->getOriginConnection()->getConnection();
    }

    /**
     * {@inheritdoc}
     */
    public function connect(): void
    {
        $firstException = null;

        foreach ($this->connections as $connection) {
            try {
                $connection->connect();

                $this->originConnection = $connection;

                return;
            } catch (ConnectionException $e) {
                $firstException = $firstException ?: $e;
            }
        }

        throw $firstException;
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected(): bool
    {
        if (!$this->originConnection) {
            return false;
        }

        return $this->getOriginConnection()->isConnected();
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect(): void
    {
        $this->getOriginConnection()->disconnect();
        $this->notify();

        $this->originConnection = null;
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
        $this->getOriginConnection()->setReadTimeout($timeout);
    }

    /**
     * {@inheritdoc}
     */
    public function getReadTimeout(): float
    {
        return $this->getOriginConnection()->getReadTimeout();
    }

    /**
     * Get active connection
     *
     * @return AmqpConnection
     */
    private function getOriginConnection(): AmqpConnection
    {
        if (!$this->originConnection) {
            throw new \LogicException('Can\'t get origin AMQP connection. Please connect previously.');
        }

        return $this->originConnection;
    }
}
