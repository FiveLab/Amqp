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

use FiveLab\Component\Amqp\Adapter\Amqp\Connection\AmqpConnectionFactory as AmqpExtConnectionFactory;
use FiveLab\Component\Amqp\Adapter\AmqpLib\Connection\AmqpConnectionFactory as AmqpLibConnectionFactory;
use FiveLab\Component\Amqp\Adapter\AmqpLib\Connection\AmqpSocketsConnectionFactory;

/**
 * The factory for make a SpoolConnection.
 */
class SpoolConnectionFactory implements ConnectionFactoryInterface
{
    /**
     * @var array|ConnectionFactoryInterface[]
     */
    private array $factories;

    /**
     * @var SpoolConnection|null
     */
    private ?SpoolConnection $connection = null;

    /**
     * @var bool
     */
    private bool $shuffleBeforeConnect = false;

    /**
     * Constructor.
     *
     * @param ConnectionFactoryInterface ...$factories
     */
    public function __construct(ConnectionFactoryInterface ...$factories)
    {
        if (!\count($factories)) {
            throw new \InvalidArgumentException('Connection factories must be more than zero.');
        }

        $this->factories = $factories;
    }

    /**
     * Mark for should shuffle connections before connect.
     */
    public function shuffleBeforeConnect(): void
    {
        $this->shuffleBeforeConnect = true;
    }

    /**
     * Make spool connection factory based on dns.
     *
     * @param Dsn $dsn
     *
     * @return self
     */
    public static function fromDsn(Dsn $dsn): self
    {
        $connectionFactoryClass = match ($dsn->driver) {
            Driver::AmqpExt     => AmqpExtConnectionFactory::class,
            Driver::AmqpLib     => AmqpLibConnectionFactory::class,
            Driver::AmqpSockets => AmqpSocketsConnectionFactory::class,
        };

        $connectionFactories = [];

        foreach ($dsn as $entry) {
            $entry = $entry->removeOption('shuffle');

            $connectionFactories[] = new $connectionFactoryClass($entry);
        }

        $factory = new self(...$connectionFactories);

        if (\array_key_exists('shuffle', $dsn->options) && $dsn->options['shuffle']) {
            $factory->shuffleBeforeConnect();
        }

        return $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function create(): ConnectionInterface
    {
        if ($this->connection) {
            return $this->connection;
        }

        $connections = \array_map(static function (ConnectionFactoryInterface $factory) {
            return $factory->create();
        }, $this->factories);

        $this->connection = new SpoolConnection(...$connections);

        if ($this->shuffleBeforeConnect) {
            $this->connection->shuffleBeforeConnect();
        }

        return $this->connection;
    }
}
