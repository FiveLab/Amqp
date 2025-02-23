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
use FiveLab\Component\Amqp\Connection\Driver;
use FiveLab\Component\Amqp\Connection\Dsn;

class AmqpConnectionFactory implements ConnectionFactoryInterface
{
    private ?AmqpConnection $connection = null;

    public function __construct(private readonly Dsn $dsn)
    {
        if ($this->dsn->driver !== Driver::AmqpExt) {
            throw new \RuntimeException(\sprintf(
                'Can\'t make %s with different driver "%s".',
                __CLASS__,
                $this->dsn->driver->value
            ));
        }
    }

    public function create(): ConnectionInterface
    {
        if ($this->connection) {
            return $this->connection;
        }

        $options = \array_merge($this->dsn->options, [
            'host'     => $this->dsn->host,
            'port'     => $this->dsn->port,
            'vhost'    => $this->dsn->vhost,
            'login'    => $this->dsn->username,
            'password' => $this->dsn->password,
        ]);

        $this->connection = new AmqpConnection(new \AMQPConnection($options));

        return $this->connection;
    }
}
