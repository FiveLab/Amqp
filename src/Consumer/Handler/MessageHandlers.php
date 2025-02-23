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

readonly class MessageHandlers implements MessageHandlerInterface, FlushableMessageHandlerInterface, ThrowableMessageHandlerInterface
{
    /**
     * @var array<MessageHandlerInterface>
     */
    private array $handlers;

    public function __construct(MessageHandlerInterface ...$handlers)
    {
        $this->handlers = $handlers;
    }

    public function supports(ReceivedMessage $message): bool
    {
        try {
            $this->getMessageHandlerForMessage($message);

            return true;
        } catch (MessageHandlerNotSupportedException $e) {
            return false;
        }
    }

    public function handle(ReceivedMessage $message): void
    {
        $handler = $this->getMessageHandlerForMessage($message);

        $handler->handle($message);
    }

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

    public function catchError(ReceivedMessage $message, \Throwable $error): void
    {
        $handler = $this->getMessageHandlerForMessage($message);

        if ($handler instanceof ThrowableMessageHandlerInterface) {
            $handler->catchError($message, $error);
        } else {
            throw $error;
        }
    }

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
