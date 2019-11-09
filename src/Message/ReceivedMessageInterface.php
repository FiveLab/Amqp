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

namespace FiveLab\Component\Amqp\Message;

/**
 * All received messages should implement this interface.
 */
interface ReceivedMessageInterface extends MessageInterface
{
    /**
     * Get delivery tag
     *
     * @return int
     */
    public function getDeliveryTag(): int;

    /**
     * Get routing key
     *
     * @return string
     */
    public function getRoutingKey(): string;

    /**
     * Get exchange name
     *
     * @return string
     */
    public function getExchangeName(): string;

    /**
     * Was answer to broker?
     *
     * @return bool
     */
    public function isAnswered(): bool;

    /**
     * Acknowledge the received message
     */
    public function ack(): void;

    /**
     * Not acknowledge the received message.
     *
     * @param bool $requeue If system should requeue this message?
     */
    public function nack(bool $requeue = true): void;
}
