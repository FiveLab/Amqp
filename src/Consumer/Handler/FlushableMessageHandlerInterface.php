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

namespace FiveLab\Component\Amqp\Consumer\Handler;

use FiveLab\Component\Amqp\Message\ReceivedMessageCollection;

/**
 * All message handlers for flushed consumers should implement this interface.
 */
interface FlushableMessageHandlerInterface extends MessageHandlerInterface
{
    /**
     * Flush all received messages.
     *
     * @param ReceivedMessageCollection $receivedMessages
     */
    public function flush(ReceivedMessageCollection $receivedMessages): void;
}
