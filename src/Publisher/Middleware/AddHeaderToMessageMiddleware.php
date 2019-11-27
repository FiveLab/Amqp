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
use FiveLab\Component\Amqp\Message\MessageInterface;

/**
 * The middleware for add custom header before sending the message.
 */
class AddHeaderToMessageMiddleware implements PublisherMiddlewareInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $value;

    /**
     * Constructor.
     *
     * @param string $name
     * @param string $value
     */
    public function __construct(string $name, string $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(string $routingKey, MessageInterface $message, callable $next): void
    {
        $headers = $message->getHeaders()->all();
        $headers[$this->name] = $this->value;

        $messageWithHeader = new Message(
            $message->getPayload(),
            $message->getOptions(),
            new Headers($headers),
            $message->getIdentifier()
        );

        $next($routingKey, $messageWithHeader);
    }
}
