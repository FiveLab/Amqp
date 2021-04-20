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

namespace FiveLab\Component\Amqp\Connection;

use FiveLab\Component\Amqp\Exception\ConnectionException;

/**
 * Spool connection for "ext-amqp".
 */
class SpoolConnection implements ConnectionInterface
{
    /**
     * @var array|ConnectionInterface[]
     */
    private array $connections;

    /**
     * @var ConnectionInterface|null
     */
    private ?ConnectionInterface $originConnection = null;

    /**
     * Constructor.
     *
     * @param array|ConnectionInterface ...$connections
     */
    public function __construct(ConnectionInterface ...$connections)
    {
        if (!\count($connections)) {
            throw new \InvalidArgumentException('Connections must be more than zero.');
        }

        $this->connections = $connections;
    }

    /**
     * Proxy call to original connection
     *
     * @param string $methodName
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call(string $methodName, array $arguments)
    {
        return \call_user_func_array([$this->getOriginConnection(), $methodName], $arguments);
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
     * {@inheritdoc}
     */
    public function attach(\SplObserver $observer): void
    {
        $this->getOriginConnection()->attach($observer);
    }

    /**
     * {@inheritdoc}
     */
    public function detach(\SplObserver $observer): void
    {
        $this->getOriginConnection()->detach($observer);
    }

    /**
     * {@inheritdoc}
     */
    public function notify(): void
    {
        $this->getOriginConnection()->notify();;
    }

    /**
     * Get active connection
     *
     * @return ConnectionInterface
     */
    private function getOriginConnection(): ConnectionInterface
    {
        if (!$this->originConnection) {
            throw new \LogicException('Can\'t get origin connection. Please connect previously.');
        }

        return $this->originConnection;
    }
}
