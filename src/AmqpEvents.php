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

namespace FiveLab\Component\Amqp;

final readonly class AmqpEvents
{
    /**
     * Call to this event after receive message.
     *
     * @see \FiveLab\Component\Amqp\Event\ReceiveMessageEvent
     */
    public const RECEIVE_MESSAGE   = 'amqp.receive_message';

    /**
     * Call to this event after success processed message.
     *
     * @see \FiveLab\Component\Amqp\Event\ProcessedMessageEvent
     */
    public const PROCESSED_MESSAGE = 'amqp.processed_message';

    /**
     * Call to this event on stop consumer.
     *
     * @see \FiveLab\Component\Amqp\Event\ConsumerStoppedEvent
     */
    public const CONSUMER_STOPPED  = 'amqp.consumer_stopped';

    /**
     * Call to this event on tick consumer.
     *
     * @see \FiveLab\Component\Amqp\Event\ConsumerTickEvent
     */
    public const CONSUMER_TICK     = 'amqp.consumer_tick';

    private function __construct()
    {
    }
}
