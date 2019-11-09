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

namespace FiveLab\Component\Amqp\Exchange;

use FiveLab\Component\Amqp\Channel\ChannelInterface;
use FiveLab\Component\Amqp\Message\MessageInterface;

/**
 * All exchanges should implement this interface.
 */
interface ExchangeInterface
{
    /**
     * Get the channel
     *
     * @return ChannelInterface
     */
    public function getChannel(): ChannelInterface;

    /**
     * Get the name of exchange
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Publish message to RabbitMQ
     *
     * @param string           $routingKey
     * @param MessageInterface $message
     */
    public function publish(string $routingKey, MessageInterface $message): void;
}
