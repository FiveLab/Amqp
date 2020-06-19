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

namespace FiveLab\Component\Amqp\Consumer\Loop;

use FiveLab\Component\Amqp\Consumer\ConsumerInterface;
use FiveLab\Component\Amqp\Consumer\Handler\FlushableMessageHandlerInterface;
use FiveLab\Component\Amqp\Consumer\Handler\ThrowableMessageHandlerInterface;
use FiveLab\Component\Amqp\Consumer\Middleware\ConsumerMiddlewareCollection;
use FiveLab\Component\Amqp\Consumer\Middleware\ConsumerMiddlewareInterface;
use FiveLab\Component\Amqp\Consumer\MiddlewareAwareInterface;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use FiveLab\Component\Amqp\Queue\QueueInterface;

/**
 * Loop consumer.
 */
class LoopConsumer implements ConsumerInterface, MiddlewareAwareInterface
{
    /**
     * @var QueueFactoryInterface
     */
    private $queueFactory;

    /**
     * @var FlushableMessageHandlerInterface
     */
    private $messageHandler;

    /**
     * @var ConsumerMiddlewareCollection
     */
    private $middlewares;

    /**
     * @var LoopConsumerConfiguration
     */
    private $configuration;

    /**
     * Indicate what we should throw exception if consumer timeout exceed.
     *
     * @var bool
     */
    private $throwConsumerTimeoutExceededException = false;

    /**
     * Constructor.
     *
     * @param QueueFactoryInterface            $queueFactory
     * @param FlushableMessageHandlerInterface $messageHandler
     * @param ConsumerMiddlewareCollection     $middlewares
     * @param LoopConsumerConfiguration        $configuration
     */
    public function __construct(QueueFactoryInterface $queueFactory, FlushableMessageHandlerInterface $messageHandler, ConsumerMiddlewareCollection $middlewares, LoopConsumerConfiguration $configuration)
    {
        $this->queueFactory = $queueFactory;
        $this->messageHandler = $messageHandler;
        $this->middlewares = $middlewares;
        $this->configuration = $configuration;
    }

    /**
     * Set flag for force throw consumer timeout exceeded exception.
     */
    public function throwExceptionOnConsumerTimeoutExceed(): void
    {
        $this->throwConsumerTimeoutExceededException = true;
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
    public function getQueue(): QueueInterface
    {
        return $this->queueFactory->create();
    }

    /**
     * {@inheritdoc}
     */
    public function run(): void
    {
        $queue = $this->queueFactory->create();
        $channel = $queue->getChannel();
        $connection = $channel->getConnection();

        $originalReadTimeout = $connection->getReadTimeout();
        $expectedReadTimeout = $this->configuration->getReadTimeout();

        if (!$originalReadTimeout || $originalReadTimeout > $expectedReadTimeout) {
            // Change the read timeout.
            $connection->setReadTimeout($expectedReadTimeout);
        }

        $channel->setPrefetchCount($this->configuration->getPrefetchCount());

        $executable = $this->middlewares->createExecutable(function (ReceivedMessageInterface $message) use ($queue) {
            $this->messageHandler->handle($message);
        });

        while (true) {
            try {
                $this->queueFactory->create()->consume(function (ReceivedMessageInterface $message) use ($executable) {
                    try {
                        $executable($message);
                    } catch (\Throwable $e) {
                        if ($this->messageHandler instanceof ThrowableMessageHandlerInterface) {
                            $this->messageHandler->catchError($message, $e);

                            if (!$message->isAnswered()) {
                                // The error handler can manually answered to broker.
                                $message->ack();
                            }

                            return;
                        } else {
                            $message->nack($this->configuration->isShouldRequeueOnError());

                            throw $e;
                        }
                    }

                    if (!$message->isAnswered()) {
                        $message->ack();
                    }
                });
            } catch (ConsumerTimeoutExceedException $e) {
                // Disconnect, because we can have zombie connection.
                $connection->disconnect();

                // The application must force throw consumer timeout exception.
                // Can be used manually for force stop consumer or in round robin consumer.
                // In other cases it's normal flow.
                if ($this->throwConsumerTimeoutExceededException) {
                    throw $e;
                }
            } catch (\Throwable $e) {
                // Disconnect, because inner system can has buffer for sending to amqp service.
                $connection->disconnect();

                throw $e;
            }
        }
    }
}
