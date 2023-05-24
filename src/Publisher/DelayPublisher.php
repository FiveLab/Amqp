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

namespace FiveLab\Component\Amqp\Publisher;

use FiveLab\Component\Amqp\Message\DelayMessage;
use FiveLab\Component\Amqp\Message\Message;

/**
 * Publisher for publish messages to delay system
 */
class DelayPublisher implements DelayPublisherInterface
{
    /**
     * Constructor.
     *
     * @param PublisherInterface $publisher
     * @param string             $landfillRoutingKey
     */
    public function __construct(private PublisherInterface $publisher, private string $landfillRoutingKey)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function publish(Message $message, string|\BackedEnum $routingKey = ''): void
    {
        if (\count(\func_get_args()) > 1) {
            throw new \RuntimeException('The routing key can\'t be specified for delay publisher.');
        }

        if (!$message instanceof DelayMessage) {
            throw new \InvalidArgumentException(\sprintf(
                'Only %s supported.',
                DelayMessage::class
            ));
        }

        $this->publisher->publish($message, $this->landfillRoutingKey);
    }
}
