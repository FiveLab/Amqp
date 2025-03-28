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

namespace FiveLab\Component\Amqp\Tests\Functional\Adapter\Amqp\Channel;

use FiveLab\Component\Amqp\Adapter\Amqp\Channel\AmqpChannel;
use FiveLab\Component\Amqp\Adapter\Amqp\Channel\AmqpChannelFactory;
use FiveLab\Component\Amqp\Adapter\Amqp\Connection\AmqpConnection;
use FiveLab\Component\Amqp\Channel\ChannelInterface;

class StubAmqpChannelFactory extends AmqpChannelFactory
{
    private AmqpConnection $connection;
    private \AMQPChannel $channel;

    public function __construct(AmqpConnection $connection, \AMQPChannel $channel)
    {
        $this->connection = $connection;
        $this->channel = $channel;
    }

    public function create(): ChannelInterface
    {
        return new AmqpChannel($this->connection, $this->channel);
    }
}
