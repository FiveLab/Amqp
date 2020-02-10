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
use FiveLab\Component\Amqp\Message\MessageInterface;

/**
 * The middleware for add identifier to message.
 */
class AddIdentifierToMessageMiddleware implements PublisherMiddlewareInterface
{
    /**
     * @var MessageIdGeneratorInterface
     */
    private $messageIdGenerator;

    /**
     * @var string
     */
    private $appId;

    /**
     * @var string
     */
    private $userId;

    /**
     * Constructor.
     *
     * @param MessageIdGeneratorInterface $messageIdGenerator
     * @param string                      $appId
     * @param string                      $userId
     */
    public function __construct(MessageIdGeneratorInterface $messageIdGenerator = null, string $appId = null, string $userId = null)
    {
        $this->messageIdGenerator = $messageIdGenerator;
        $this->appId = $appId;
        $this->userId = $userId;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(MessageInterface $message, callable $next, string $routingKey = ''): void
    {
        $identifier = $message->getIdentifier();

        $messageId = $identifier->getId();
        $appId = $identifier->getAppId();
        $userId = $identifier->getUserId();

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
            $message->getPayload(),
            $message->getOptions(),
            $message->getHeaders(),
            new Identifier($messageId, $appId, $userId)
        );

        $next($messageWithIdentifier, $routingKey);
    }
}
