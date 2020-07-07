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

namespace FiveLab\Component\Amqp\Consumer\Spool;

use FiveLab\Component\Amqp\Consumer\ConsumerInterface;
use FiveLab\Component\Amqp\Consumer\Handler\FlushableMessageHandlerInterface;
use FiveLab\Component\Amqp\Consumer\Middleware\ConsumerMiddlewareCollection;
use FiveLab\Component\Amqp\Consumer\Middleware\ConsumerMiddlewareInterface;
use FiveLab\Component\Amqp\Consumer\MiddlewareAwareInterface;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Message\MutableReceivedMessageCollection;
use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use FiveLab\Component\Amqp\Queue\QueueInterface;

/**
 * The consumer for buffer all received messages by configuration and flush by configuration.
 *
 * @see \FiveLab\Component\Amqp\Consumer\Handler\FlushableMessageHandlerInterface
 */
class SpoolConsumer implements ConsumerInterface, MiddlewareAwareInterface
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
     * @var SpoolConsumerConfiguration
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
     * @param SpoolConsumerConfiguration       $configuration
     */
    public function __construct(QueueFactoryInterface $queueFactory, FlushableMessageHandlerInterface $messageHandler, ConsumerMiddlewareCollection $middlewares, SpoolConsumerConfiguration $configuration)
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

        $connectionOriginalReadTimeout = $connection->getReadTimeout();
        $spoolReadTimeout = $this->configuration->getReadTimeout();

        if ($spoolReadTimeout && (0 === (int) $connectionOriginalReadTimeout || $connectionOriginalReadTimeout > $spoolReadTimeout)) {
            // Change the read timeout.
            $connection->setReadTimeout($spoolReadTimeout);
        }

        $originalPrefetchCount = $channel->getPrefetchCount();
        $expectedPrefetchCount = $this->configuration->getPrefetchCount();

        if ($originalPrefetchCount < $expectedPrefetchCount) {
            $channel->setPrefetchCount($expectedPrefetchCount);
        }

        $executable = $this->middlewares->createExecutable(function (ReceivedMessageInterface $message) use ($queue) {
            $this->messageHandler->handle($message);
        });

        while (true) {
            $messages = new MutableReceivedMessageCollection();
            $endTime = \microtime(true) + $this->configuration->getTimeout();
            $consumerTag = \sprintf('spool.%s', \md5(\uniqid((string) \random_int(0, PHP_INT_MAX), true)));

            try {
                $countOfProcessedMessages = 0;

                $this->queueFactory->create()->consume(function (ReceivedMessageInterface $message) use ($queue, $executable, $messages, &$endTime, &$countOfProcessedMessages) {
                    try {
                        $executable($message);
                    } catch (\Throwable $e) {
                        // We catch error on processing messages. We should nack for all received messages.
                        $message->nack($this->configuration->isShouldRequeueOnError());

                        foreach ($messages as $message) {
                            $message->nack($this->configuration->isShouldRequeueOnError());
                        }

                        $messages->clear();

                        throw $e;
                    }

                    if ($message->isAnswered()) {
                        throw new \LogicException(\sprintf(
                            'The message handler "%s" is flushable and can\'t directly answering to broker on handle message.',
                            \get_class($this->messageHandler)
                        ));
                    }

                    $messages->push($message);
                    $countOfProcessedMessages++;

                    if ($countOfProcessedMessages >= $this->configuration->getPrefetchCount()) {
                        // Flush by count messages
                        $this->flushMessages($messages);
                        $countOfProcessedMessages = 0;
                    }

                    if (\microtime(true) > $endTime) {
                        // We must flush by timeout. In some cases we can use many messages in bucket, and wait to max
                        // process many time.
                        $this->flushMessages($messages);
                        $endTime = \microtime(true) + $this->configuration->getTimeout();
                    }
                }, $consumerTag);
            } catch (ConsumerTimeoutExceedException $e) {
                $queue->cancelConsumer($consumerTag);

                $this->flushMessages($messages);

                // The application must force throw consumer timeout exception.
                // Can be used manually for force stop consumer or in round robin consumer.
                if ($this->throwConsumerTimeoutExceededException) {
                    throw $e;
                }
            } catch (\Throwable $e) {
                // We must reconnect to broker because client don't return messages to queue on failed.
                $connection->disconnect();

                throw $e;
            }
        }
    }

    /**
     * Flush all messages
     *
     * @param MutableReceivedMessageCollection $messages
     */
    private function flushMessages(MutableReceivedMessageCollection $messages): void
    {
        if (!\count($messages)) {
            // We don't receive any messages. Nothing action.
            return;
        }

        try {
            $this->messageHandler->flush($messages->immutable());
        } catch (\Throwable $e) {
            foreach ($messages as $message) {
                if (!$message->isAnswered()) {
                    $message->nack($this->configuration->isShouldRequeueOnError());
                }
            }

            throw $e;
        }

        foreach ($messages as $message) {
            // The flush mechanism can ack or non-ack some messages.
            if (!$message->isAnswered()) {
                $message->ack();
            }
        }

        $messages->clear();
    }
}
