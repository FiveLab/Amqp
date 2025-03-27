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
        foreach ($this->handlers as $handler) {
            if ($handler->supports($message)) {
                return true;
            }
        }

        return false;
    }

    public function handle(ReceivedMessage $message): void
    {
        $supported = false;

        foreach ($this->handlers as $handler) {
            if ($handler->supports($message)) {
                $supported = true;

                try {
                    $handler->handle($message);
                } catch (\Throwable $error) {
                    if ($handler instanceof ThrowableMessageHandlerInterface) {
                        $handler->catchError($message, $error);

                        if (!$message->isAnswered()) {
                            // The error handler can manually answer to broker.
                            $message->ack();
                        }

                        continue;
                    }

                    throw $error;
                }
            }
        }

        if (!$supported) {
            throw new MessageHandlerNotSupportedException(\sprintf(
                'Not any message handler supports for message in queue "%s" from "%s" exchange by "%s" routing key.',
                $message->queueName,
                $message->exchangeName,
                $message->routingKey
            ));
        }
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
        // Nothing to do, because we catch error inside handling.
    }
}
