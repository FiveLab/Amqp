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
     * Make spool connection factory based on dns.
     *
     * @param Dsn $dsn
     *
     * @return self
     */
    public static function fromDsn(Dsn $dsn): self
    {
        $hosts = \explode(',', $dsn->host);
        $hosts = \array_map('trim', $hosts);
        $hosts = \array_filter($hosts);

        $connectionFactoryClass = match ($dsn->driver) {
            Driver::AmqpExt     => AmqpExtConnectionFactory::class,
            Driver::AmqpLib     => AmqpLibConnectionFactory::class,
            Driver::AmqpSockets => AmqpSocketsConnectionFactory::class,
        };

        $connectionFactories = [];

        foreach ($hosts as $host) {
            $connectionDsn = new Dsn($dsn->driver, $host, $dsn->port, $dsn->vhost, $dsn->username, $dsn->password, $dsn->options);

            $connectionFactories[] = new $connectionFactoryClass($connectionDsn);
        }

        return new self(...$connectionFactories);
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

        return $this->connection;
    }
}
