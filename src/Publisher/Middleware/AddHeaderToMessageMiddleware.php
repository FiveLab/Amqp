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

namespace FiveLab\Component\Amqp\Publisher\Middleware;

use FiveLab\Component\Amqp\Message\Headers;
use FiveLab\Component\Amqp\Message\Message;

/**
 * The middleware for add custom header before sending the message.
 */
readonly class AddHeaderToMessageMiddleware implements PublisherMiddlewareInterface
{
    /**
     * Constructor.
     *
     * @param string $name
     * @param string $value
     */
    public function __construct(private string $name, private string $value)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Message $message, callable $next, string $routingKey = ''): void
    {
        $headers = $message->headers->all();
        $headers[$this->name] = $this->value;

        $messageWithHeader = new Message(
            $message->payload,
            $message->options,
            new Headers($headers),
            $message->identifier
        );

        $next($messageWithHeader, $routingKey);
    }
}
