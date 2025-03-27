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

namespace FiveLab\Component\Amqp\Consumer;

use FiveLab\Component\Amqp\Consumer\Handler\MessageHandlerInterface;
use FiveLab\Component\Amqp\Consumer\Handler\MessageHandlers;
use FiveLab\Component\Amqp\Consumer\Middleware\ConsumerMiddlewareInterface;
use FiveLab\Component\Amqp\Consumer\Middleware\ConsumerMiddlewares;
use FiveLab\Component\Amqp\Exception\StopAfterNExecutesException;
use FiveLab\Component\Amqp\Message\ReceivedMessage;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use FiveLab\Component\Amqp\Queue\QueueInterface;

class SingleConsumer implements EventableConsumerInterface, MiddlewareAwareInterface
{
    use EventableConsumerTrait;

    private readonly MessageHandlers $messageHandler;

    public function __construct(
        private readonly QueueFactoryInterface $queueFactory,
        MessageHandlerInterface                $messageHandler,
        private readonly ConsumerMiddlewares   $middlewares,
        private readonly ConsumerConfiguration $configuration
    ) {
        $this->messageHandler = $messageHandler instanceof MessageHandlers ? $messageHandler : new MessageHandlers($messageHandler);
    }

    public function getQueue(): QueueInterface
    {
        return $this->queueFactory->create();
    }

    public function pushMiddleware(ConsumerMiddlewareInterface $middleware): void
    {
        $this->middlewares->push($middleware);
    }

    public function run(): void
    {
        $queue = $this->queueFactory->create();

        $queue->getChannel()->setPrefetchCount($this->configuration->prefetchCount);

        $executable = $this->middlewares->createExecutable(function (ReceivedMessage $message) {
            $this->messageHandler->handle($message);
        });

        try {
            $queue->consume(function (ReceivedMessage $message) use ($executable) {
                try {
                    $executable($message);
                } catch (StopAfterNExecutesException $error) {
                    // We must stop after N executes. In this case we ack message and exit from loop.
                    if (!$message->isAnswered()) {
                        $message->ack();
                    }

                    throw $error;
                } catch (\Throwable $e) {
                    $message->nack($this->configuration->requeueOnError);

                    throw $e;
                }

                if (!$message->isAnswered()) {
                    // The message handler can manually answer to broker.
                    $message->ack();
                }
            }, $this->configuration->tagGenerator->generate());
        } catch (StopAfterNExecutesException) {
            $queue->getChannel()->getConnection()->disconnect();

            $this->triggerEvent(Event::StopAfterNExecutes);

            // Normal flow. Exit from loop.
            return;
        } catch (\Throwable $error) {
            $queue->getChannel()->getConnection()->disconnect();

            throw $error;
        }
    }
}
