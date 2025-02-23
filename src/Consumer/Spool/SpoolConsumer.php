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

use FiveLab\Component\Amqp\Channel\ChannelInterface;
use FiveLab\Component\Amqp\Consumer\Event;
use FiveLab\Component\Amqp\Consumer\EventableConsumerInterface;
use FiveLab\Component\Amqp\Consumer\EventableConsumerTrait;
use FiveLab\Component\Amqp\Consumer\Handler\FlushableMessageHandlerInterface;
use FiveLab\Component\Amqp\Consumer\Middleware\ConsumerMiddlewareInterface;
use FiveLab\Component\Amqp\Consumer\Middleware\ConsumerMiddlewares;
use FiveLab\Component\Amqp\Consumer\MiddlewareAwareInterface;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Exception\StopAfterNExecutesException;
use FiveLab\Component\Amqp\Message\MutableReceivedMessages;
use FiveLab\Component\Amqp\Message\ReceivedMessage;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use FiveLab\Component\Amqp\Queue\QueueInterface;

/**
 * The consumer for buffer all received messages by configuration and flush by configuration.
 *
 * @see \FiveLab\Component\Amqp\Consumer\Handler\FlushableMessageHandlerInterface
 */
class SpoolConsumer implements EventableConsumerInterface, MiddlewareAwareInterface
{
    use EventableConsumerTrait;

    private bool $throwConsumerTimeoutExceededException = false;

    public function __construct(
        private readonly QueueFactoryInterface            $queueFactory,
        private readonly FlushableMessageHandlerInterface $messageHandler,
        private readonly ConsumerMiddlewares              $middlewares,
        private readonly SpoolConsumerConfiguration       $configuration
    ) {
    }

    public function throwExceptionOnConsumerTimeoutExceed(): void
    {
        $this->throwConsumerTimeoutExceededException = true;
    }

    public function pushMiddleware(ConsumerMiddlewareInterface $middleware): void
    {
        $this->middlewares->push($middleware);
    }

    public function getQueue(): QueueInterface
    {
        return $this->queueFactory->create();
    }

    public function run(): void
    {
        $executable = $this->middlewares->createExecutable(function (ReceivedMessage $message) {
            $this->messageHandler->handle($message);
        });

        while (true) {
            $channel = $this->queueFactory->create()->getChannel();

            $this->configureBeforeConsume($channel);

            $receivedMessages = new MutableReceivedMessages();
            $endTime = \microtime(true) + $this->configuration->timeout;

            try {
                $countOfProcessedMessages = 0;

                $this->queueFactory->create()->consume(function (ReceivedMessage $message) use ($executable, $receivedMessages, &$endTime, &$countOfProcessedMessages) {
                    try {
                        $executable($message);
                    } catch (ConsumerTimeoutExceedException $error) {
                        // Attention: the executable can't throw consumer timeout exception. But in test we throw
                        // this exception for transfer control to next catch and test success while iteration.
                        // In this case we don't flush message and only add message to buffer.
                        $countOfProcessedMessages++;
                        $receivedMessages->push($message);

                        throw $error;
                    } catch (StopAfterNExecutesException $error) {
                        // We must stop after N executes. In this case we flush all messages and exit from loop.
                        $receivedMessages->push($message);

                        throw $error;
                    } catch (\Throwable $e) {
                        // We catch error on processing messages. We should nack for all received messages.
                        $message->nack($this->configuration->requeueOnError);

                        foreach ($receivedMessages as $receivedMessage) {
                            $receivedMessage->nack($this->configuration->requeueOnError);
                        }

                        $receivedMessages->clear();

                        throw $e;
                    }

                    if ($message->isAnswered()) {
                        throw new \LogicException(\sprintf(
                            'The message handler "%s" is flushable and can\'t directly answering to broker on handle message.',
                            \get_class($this->messageHandler)
                        ));
                    }

                    $receivedMessages->push($message);
                    $countOfProcessedMessages++;

                    if ($countOfProcessedMessages >= $this->configuration->prefetchCount) {
                        // Flush by count messages
                        $this->flushMessages($receivedMessages);
                        $countOfProcessedMessages = 0;
                    }

                    if (\microtime(true) > $endTime) {
                        // We must flush by timeout. In some cases we can use many messages in bucket, and wait to max
                        // process many time.
                        $this->flushMessages($receivedMessages);
                        $endTime = \microtime(true) + $this->configuration->timeout;
                    }
                }, $this->configuration->tagGenerator->generate());
            } catch (ConsumerTimeoutExceedException $e) {
                $this->flushMessages($receivedMessages);

                // Note: we can't cancel consumer, because rabbitmq can send next message to client
                // and client attach to existence consumer. As result we can receive error: orphaned envelope.
                // We full disconnect and try reconnect
                $channel->getConnection()->disconnect();

                $this->triggerEvent(Event::ConsumerTimeout);

                // The application must force throw consumer timeout exception.
                // Can be used manually for force stop consumer or in round robin consumer.
                if ($this->throwConsumerTimeoutExceededException) {
                    throw $e;
                }
            } catch (StopAfterNExecutesException) {
                // We must stop next loop.
                $this->flushMessages($receivedMessages);

                // We must reconnect to broker because client don't return messages to queue on failed.
                $channel->getConnection()->disconnect();

                $this->triggerEvent(Event::StopAfterNExecutes);

                return;
            } catch (\Throwable $e) {
                // We must reconnect to broker because client don't return messages to queue on failed.
                $channel->getConnection()->disconnect();

                throw $e;
            }
        }
    }

    private function flushMessages(MutableReceivedMessages $messages): void
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
                    $message->nack($this->configuration->requeueOnError);
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

    private function configureBeforeConsume(ChannelInterface $channel): void
    {
        $connection = $channel->getConnection();

        $connectionOriginalReadTimeout = $connection->getReadTimeout();
        $spoolReadTimeout = $this->configuration->readTimeout;

        if ($spoolReadTimeout && (0 === (int) $connectionOriginalReadTimeout || $connectionOriginalReadTimeout > $spoolReadTimeout)) {
            // Change the read timeout.
            $connection->setReadTimeout($spoolReadTimeout);
        }

        $originalPrefetchCount = $channel->getPrefetchCount();
        $expectedPrefetchCount = $this->configuration->prefetchCount;

        if ($originalPrefetchCount < $expectedPrefetchCount) {
            $channel->setPrefetchCount($expectedPrefetchCount);
        }
    }
}
