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
 * The factory for create connection provided via php-amqp extension.
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
     * Constructor.
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

        $this->connection = new AmqpConnection(new \AMQPConnection($this->connectionOptions));

        return $this->connection;
    }
}
