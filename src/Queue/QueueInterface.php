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

namespace FiveLab\Component\Amqp\Queue;

use FiveLab\Component\Amqp\Channel\ChannelInterface;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Message\ReceivedMessage;

interface QueueInterface
{
    /**
     * Get the channel
     *
     * @return ChannelInterface
     */
    public function getChannel(): ChannelInterface;

    /**
     * Get the name of queue
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Consume the queue
     *
     * @param \Closure $handler
     * @param string   $tag
     *
     * @throws ConsumerTimeoutExceedException
     */
    public function consume(\Closure $handler, string $tag = ''): void;

    /**
     * Cancel consumer by tag
     *
     * @param string $tag
     */
    public function cancelConsumer(string $tag): void;

    /**
     * Get next message from queue
     *
     * @return ReceivedMessage|null
     */
    public function get(): ?ReceivedMessage;

    /**
     * Purge a queue
     */
    public function purge(): void;

    /**
     * Delete a queue
     */
    public function delete(): void;

    /**
     * Get a count messages
     *
     * @return int
     */
    public function countMessages(): int;
}
