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

namespace FiveLab\Component\Amqp\Adapter\Amqp\Channel;

use FiveLab\Component\Amqp\Adapter\Amqp\Connection\AmqpConnection;
use FiveLab\Component\Amqp\Channel\ChannelFactoryInterface;
use FiveLab\Component\Amqp\Channel\ChannelInterface;
use FiveLab\Component\Amqp\Channel\Definition\ChannelDefinition;
use FiveLab\Component\Amqp\Connection\ConnectionFactoryInterface;
use FiveLab\Component\Amqp\Connection\SpoolConnection;

class AmqpChannelFactory implements ChannelFactoryInterface, \SplObserver
{
    private ?AmqpChannel $channel = null;

    public function __construct(
        private readonly ConnectionFactoryInterface $connectionFactory,
        private readonly ChannelDefinition          $definition // @phpstan-ignore-line
    ) {
    }

    public function create(): ChannelInterface
    {
        if ($this->channel) {
            return $this->channel;
        }

        $connection = $this->connectionFactory->create();

        if (!$connection instanceof AmqpConnection && !$connection instanceof SpoolConnection) {
            throw new \InvalidArgumentException(\sprintf(
                'The connection "%s" does not supported for create channel.',
                \get_class($connection)
            ));
        }

        $connection->attach($this);

        if (!$connection->isConnected()) {
            $connection->connect();
        }

        /** @var AmqpConnection $connection */
        $channel = new \AMQPChannel($connection->getConnection());

        $this->channel = new AmqpChannel($connection, $channel);

        return $this->channel;
    }

    public function update(\SplSubject $subject): void
    {
        $this->channel = null;
    }
}
