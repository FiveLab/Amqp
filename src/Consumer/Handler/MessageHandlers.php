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

use FiveLab\Component\Amqp\Exception\MessageHandlerNotSupportedException;
use FiveLab\Component\Amqp\Message\ReceivedMessage;
use FiveLab\Component\Amqp\Message\ReceivedMessages;

/**
 * The chain of message handlers.
 */
class MessageHandlers implements MessageHandlerInterface, FlushableMessageHandlerInterface, ThrowableMessageHandlerInterface
{
    /**
     * @var array|MessageHandlerInterface[]
     */
    private readonly array $handlers;

    /**
     * Constructor.
     *
     * @param MessageHandlerInterface ...$handlers
     */
    public function __construct(MessageHandlerInterface ...$handlers)
    {
        $this->handlers = $handlers;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ReceivedMessage $message): bool
    {
        try {
            $this->getMessageHandlerForMessage($message);

            return true;
        } catch (MessageHandlerNotSupportedException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ReceivedMessage $message): void
    {
        $handler = $this->getMessageHandlerForMessage($message);

        $handler->handle($message);
    }

    /**
     * {@inheritdoc}
     */
    public function flush(ReceivedMessages $receivedMessages): void
    {
        foreach ($this->handlers as $handler) {
            if (!$handler instanceof FlushableMessageHandlerInterface) {
                throw new \RuntimeException(\sprintf(
                    'The message handler "%s" does not support flushable mechanism.',
                    \get_class($handler)
                ));
            }

            $handler->flush($receivedMessages);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function catchError(ReceivedMessage $message, \Throwable $error): void
    {
        $handler = $this->getMessageHandlerForMessage($message);

        if ($handler instanceof ThrowableMessageHandlerInterface) {
            $handler->catchError($message, $error);
        } else {
            throw $error;
        }
    }

    /**
     * Get the message handler for message
     *
     * @param ReceivedMessage $message
     *
     * @return MessageHandlerInterface
     *
     * @throws MessageHandlerNotSupportedException
     */
    private function getMessageHandlerForMessage(ReceivedMessage $message): MessageHandlerInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($message)) {
                return $handler;
            }
        }

        throw new MessageHandlerNotSupportedException('Not found supported message handler.');
    }
}
