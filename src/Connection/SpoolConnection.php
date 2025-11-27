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
use FiveLab\Component\Amqp\SplSubjectTrait;

class SpoolConnection implements ConnectionInterface
{
    use SplSubjectTrait;

    /**
     * @var array<ConnectionInterface>
     */
    private readonly array $connections;

    private ?ConnectionInterface $originConnection = null;
    private bool $shuffleBeforeConnect = false;

    public function __construct(ConnectionInterface ...$connections)
    {
        if (!\count($connections)) {
            throw new \InvalidArgumentException('Connections must be more than zero.');
        }

        $this->connections = $connections;
    }

    public function shuffleBeforeConnect(): void
    {
        $this->shuffleBeforeConnect = true;
    }

    /**
     * Call method in original connection
     *
     * @param string       $methodName
     * @param array<mixed> $arguments
     *
     * @return mixed
     */
    public function __call(string $methodName, array $arguments): mixed
    {
        // @phpstan-ignore-next-line
        return \call_user_func_array([$this->getOriginConnection(), $methodName], $arguments);
    }

    public function connect(): void
    {
        $connections = $this->connections;

        if ($this->shuffleBeforeConnect) {
            \shuffle($connections);
        }

        $firstException = null;

        foreach ($connections as $connection) {
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

    public function isConnected(): bool
    {
        if (!$this->originConnection) {
            return false;
        }

        return $this->getOriginConnection()->isConnected();
    }

    public function disconnect(): void
    {
        if (!$this->originConnection) {
            // Already disconnected.
            return;
        }

        $this->getOriginConnection()->disconnect();
        $this->notify();

        $this->originConnection = null;
    }

    public function reconnect(): void
    {
        $this->disconnect();
        $this->connect();
    }

    public function setReadTimeout(float $timeout): void
    {
        $this->getOriginConnection()->setReadTimeout($timeout);
    }

    public function getReadTimeout(): float
    {
        return $this->getOriginConnection()->getReadTimeout();
    }

    private function getOriginConnection(): ConnectionInterface
    {
        if (!$this->originConnection) {
            throw new \LogicException('Can\'t get origin connection. Please connect previously.');
        }

        return $this->originConnection;
    }
}
