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

use FiveLab\Component\Amqp\Connection\ConnectionFactoryInterface;
use FiveLab\Component\Amqp\Connection\ConnectionInterface;

/**
 * The factory for make a SpoolConnection via "ext-amqp".
 */
class SpoolAmqpConnectionFactory implements ConnectionFactoryInterface
{
    /**
     * @var AmqpConnectionFactory
     */
    private $factories;

    /**
     * @var SpoolAmqpConnection
     */
    private $connection;

    /**
     * Constructor.
     *
     * @param AmqpConnectionFactory ...$factories
     */
    public function __construct(AmqpConnectionFactory ...$factories)
    {
        if (!\count($factories)) {
            throw new \InvalidArgumentException('Connection factories must be more than zero.');
        }

        $this->factories = $factories;
    }

    /**
     * {@inheritdoc}
     */
    public function create(): ConnectionInterface
    {
        if ($this->connection) {
            return $this->connection;
        }

        $connections = \array_map(function (AmqpConnectionFactory $factory) {
            return $factory->create();
        }, $this->factories);

        $this->connection = new SpoolAmqpConnection(...$connections);

        return $this->connection;
    }
}
