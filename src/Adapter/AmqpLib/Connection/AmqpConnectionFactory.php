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
use FiveLab\Component\Amqp\Connection\Driver;
use FiveLab\Component\Amqp\Connection\Dsn;
use PhpAmqpLib\Connection\AMQPConnectionConfig;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * The factory for create connection provided via php-amqplib library.
 */
class AmqpConnectionFactory implements ConnectionFactoryInterface
{
    /**
     * @var AmqpConnection|null
     */
    private ?AmqpConnection $connection = null;

    /**
     * Constructor.
     *
     * @param Dsn $dsn
     */
    public function __construct(private readonly Dsn $dsn)
    {
        if ($this->dsn->driver !== Driver::AmqpLib) {
            throw new \RuntimeException(\sprintf(
                'Can\'t make %s with different driver "%s".',
                __CLASS__,
                $this->dsn->driver->value
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function create(): ConnectionInterface
    {
        if ($this->connection) {
            return $this->connection;
        }

        $config = new AMQPConnectionConfig();
        $config->setIsLazy(true);

        $options = $this->dsn->options;

        $amqpLibConnection = new AMQPStreamConnection(
            $this->dsn->host,
            $this->dsn->port,
            $this->dsn->username,
            $this->dsn->password,
            $this->dsn->vhost,
            $options['insist'] ?? false,
            $options['login_method'] ?? 'AMQPLAIN',
            $options['login_response'] ?? null,
            $options['locale'] ?? 'en_US',
            $options['connection_timeout'] ?? 5.0,
            $options['read_write_timeout'] ?? $options['read_timeout'] ?? 0,
            null,
            $options['keepalive'] ?? false,
            $options['heartbeat'] ?? 0,
            $options['channel_rpc_timeout'] ?? $options['read_timeout'] ?? 0,
            null,
            $config
        );

        $this->connection = new AmqpConnection($amqpLibConnection, $options['read_timeout'] ?? 0);

        return $this->connection;
    }
}
