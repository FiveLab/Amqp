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

/**
 * The factory for make a SpoolConnection via "ext-amqp".
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
