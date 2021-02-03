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
use FiveLab\Component\Amqp\Consumer\Handler\ThrowableMessageHandlerInterface;
use FiveLab\Component\Amqp\Consumer\Middleware\ConsumerMiddlewares;
use FiveLab\Component\Amqp\Consumer\Middleware\ConsumerMiddlewareInterface;
use FiveLab\Component\Amqp\Exception\StopAfterNExecutesException;
use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use FiveLab\Component\Amqp\Queue\QueueInterface;

/**
 * Single consumer.
 */
class SingleConsumer implements ConsumerInterface, MiddlewareAwareInterface
{
    /**
     * @var QueueFactoryInterface
     */
    private $queueFactory;

    /**
     * @var MessageHandlerInterface
     */
    private $messageHandler;

    /**
     * @var ConsumerMiddlewares
     */
    private $middlewares;

    /**
     * @var ConsumerConfiguration
     */
    private $configuration;

    /**
     * Constructor.
     *
     * @param QueueFactoryInterface   $queueFactory
     * @param MessageHandlerInterface $messageHandler
     * @param ConsumerMiddlewares     $middlewares
     * @param ConsumerConfiguration   $configuration
     */
    public function __construct(QueueFactoryInterface $queueFactory, MessageHandlerInterface $messageHandler, ConsumerMiddlewares $middlewares, ConsumerConfiguration $configuration)
    {
        $this->queueFactory = $queueFactory;
        $this->messageHandler = $messageHandler;
        $this->middlewares = $middlewares;
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueue(): QueueInterface
    {
        return $this->queueFactory->create();
    }

    /**
     * {@inheritdoc}
     */
    public function pushMiddleware(ConsumerMiddlewareInterface $middleware): void
    {
        $this->middlewares->push($middleware);
    }

    /**
     * {@inheritdoc}
     */
    public function run(): void
    {
        $queue = $this->queueFactory->create();

        $queue->getChannel()->setPrefetchCount($this->configuration->getPrefetchCount());

        $executable = $this->middlewares->createExecutable(function (ReceivedMessageInterface $message) {
            $this->messageHandler->handle($message);
        });

        try {
            $queue->consume(function (ReceivedMessageInterface $message) use ($executable) {
                try {
                    $executable($message);
                } catch (StopAfterNExecutesException $error) {
                    // We must stop after N executes. In this case we ack message and exit from loop.
                    if (!$message->isAnswered()) {
                        $message->ack();
                    }

                    throw $error;
                } catch (\Throwable $e) {
                    if ($this->messageHandler instanceof ThrowableMessageHandlerInterface) {
                        $this->messageHandler->catchError($message, $e);

                        if (!$message->isAnswered()) {
                            // The error handler can manually answered to broker.
                            $message->ack();
                        }

                        return;
                    }

                    $message->nack($this->configuration->isShouldRequeueOnError());

                    throw $e;
                }

                if (!$message->isAnswered()) {
                    // The message handler can manually answered to broker.
                    $message->ack();
                }
            }, $this->configuration->getTagGenerator()->generate());
        } catch (StopAfterNExecutesException $error) {
            $queue->getChannel()->getConnection()->disconnect();

            // Normal flow. Exit from loop.
            return;
        } catch (\Throwable $error) {
            $queue->getChannel()->getConnection()->disconnect();

            throw $error;
        }
    }
}
