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
 * Implement delay message. It adds common headers for next control in handlers.
 */
class DelayMessage extends Message
{
    public const HEADER_PUBLISHER_KEY = 'x-delay-publisher';
    public const HEADER_ROUTING_KEY   = 'x-delay-routing-key';
    public const HEADER_COUNTER       = 'x-delay-counter';

    public function __construct(
        Message            $message,
        string             $publisherKey,
        string|\BackedEnum $routingKey = '',
        int                $counter = 1
    ) {
        if ($routingKey instanceof \BackedEnum) {
            $routingKey = (string) $routingKey->value;
        }

        $headers = $message->headers;
        $headersList = $headers->all();

        $headersList[self::HEADER_PUBLISHER_KEY] = $publisherKey;
        $headersList[self::HEADER_ROUTING_KEY] = $routingKey;
        $headersList[self::HEADER_COUNTER] = $counter;

        $headers = new Headers($headersList);

        parent::__construct($message->payload, $message->options, $headers, $message->identifier);
    }
}
