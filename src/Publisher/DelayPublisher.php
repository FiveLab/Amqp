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
use FiveLab\Component\Amqp\Message\MessageInterface;

/**
 * Publisher for publish messages to delay system
 */
class DelayPublisher implements DelayPublisherInterface
{
    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var string
     */
    private $landfillRoutingKey;

    /**
     * Constructor.
     *
     * @param PublisherInterface $publisher
     * @param string             $landfillRoutingKey
     */
    public function __construct(PublisherInterface $publisher, string $landfillRoutingKey)
    {
        $this->publisher = $publisher;
        $this->landfillRoutingKey = $landfillRoutingKey;
    }

    /**
     * {@inheritdoc}
     */
    public function publish(MessageInterface $message, string $routingKey = ''): void
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
