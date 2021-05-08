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

use FiveLab\Component\Amqp\Connection\ConnectionFactoryInterface;
use FiveLab\Component\Amqp\Connection\ConnectionInterface;
use PhpAmqpLib\Connection\AMQPLazyConnection;

/**
 * The factory for create connection provided via php-amqplib library.
 */
class AmqpConnectionFactory implements ConnectionFactoryInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $connectionOptions;

    /**
     * @var AmqpConnection|null
     */
    private ?AmqpConnection $connection = null;

    /**
     * Construct
     *
     * @param array<string, mixed> $connectionOptions
     */
    public function __construct(array $connectionOptions)
    {
        $this->connectionOptions = $connectionOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function create(): ConnectionInterface
    {
        if ($this->connection) {
            return $this->connection;
        }

        $amqpLibConnection = new AMQPLazyConnection(
            $this->connectionOptions['host'],
            $this->connectionOptions['port'],
            $this->connectionOptions['login'] ?? 'guest',
            $this->connectionOptions['password'] ?? 'guest',
            $this->connectionOptions['vhost'] ?? '/',
            false,
            'AMQPLAIN',
            null,
            'en_US',
            5.0,
            $this->connectionOptions['read_timeout'] ?? 0,
            null,
            false,
            $this->connectionOptions['heartbeat'] ?? 0,
            $this->connectionOptions['read_timeout'] ?? 0
        );

        $this->connection = new AmqpConnection($amqpLibConnection, $this->connectionOptions['read_timeout'] ?? 0);

        return $this->connection;
    }
}
