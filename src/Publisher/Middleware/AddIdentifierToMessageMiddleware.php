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

use FiveLab\Component\Amqp\Message\Generator\MessageIdGeneratorInterface;
use FiveLab\Component\Amqp\Message\Identifier;
use FiveLab\Component\Amqp\Message\Message;

/**
 * The middleware for add identifier to message.
 */
readonly class AddIdentifierToMessageMiddleware implements PublisherMiddlewareInterface
{
    /**
     * Constructor.
     *
     * @param MessageIdGeneratorInterface|null $messageIdGenerator
     * @param string|null                      $appId
     * @param string|null                      $userId
     */
    public function __construct(
        private ?MessageIdGeneratorInterface $messageIdGenerator = null,
        private ?string                      $appId = null,
        private ?string                      $userId = null
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Message $message, callable $next, string $routingKey = ''): void
    {
        $identifier = $message->identifier;

        $messageId = $identifier->id;
        $appId = $identifier->appId;
        $userId = $identifier->userId;

        if (!$messageId && $this->messageIdGenerator) {
            $messageId = $this->messageIdGenerator->generate();
        }

        if (!$appId && $this->appId) {
            $appId = $this->appId;
        }

        if (!$userId && $this->userId) {
            $userId = $this->userId;
        }

        $messageWithIdentifier = new Message(
            $message->payload,
            $message->options,
            $message->headers,
            new Identifier($messageId, $appId, $userId)
        );

        $next($messageWithIdentifier, $routingKey);
    }
}
