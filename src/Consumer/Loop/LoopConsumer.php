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

use FiveLab\Component\Amqp\Channel\ChannelInterface;
use FiveLab\Component\Amqp\Consumer\Event;
use FiveLab\Component\Amqp\Consumer\EventableConsumerInterface;
use FiveLab\Component\Amqp\Consumer\EventableConsumerTrait;
use FiveLab\Component\Amqp\Consumer\Handler\MessageHandlerInterface;
use FiveLab\Component\Amqp\Consumer\Handler\MessageHandlers;
use FiveLab\Component\Amqp\Consumer\Middleware\ConsumerMiddlewareInterface;
use FiveLab\Component\Amqp\Consumer\Middleware\ConsumerMiddlewares;
use FiveLab\Component\Amqp\Consumer\MiddlewareAwareInterface;
use FiveLab\Component\Amqp\Consumer\Strategy\DefaultConsumeStrategy;
use FiveLab\Component\Amqp\Consumer\Strategy\ConsumeStrategyInterface;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Exception\StopConsumingException;
use FiveLab\Component\Amqp\Message\ReceivedMessage;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use FiveLab\Component\Amqp\Queue\QueueInterface;

class LoopConsumer implements EventableConsumerInterface, MiddlewareAwareInterface
{
    use EventableConsumerTrait;

    private readonly MessageHandlers $messageHandler;
    private readonly ConsumeStrategyInterface $strategy;
    private bool $throwConsumerTimeoutExceededException = false;
    private bool $stopConsuming = false;

    public function __construct(
        private readonly QueueFactoryInterface     $queueFactory,
        MessageHandlerInterface                    $messageHandler,
        private readonly ConsumerMiddlewares       $middlewares,
        private readonly LoopConsumerConfiguration $configuration,
        ?ConsumeStrategyInterface                  $strategy = null
    ) {
        $this->messageHandler = $messageHandler instanceof MessageHandlers ? $messageHandler : new MessageHandlers($messageHandler);
        $this->strategy = $strategy ?: new DefaultConsumeStrategy();
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

    public function stop(): void
    {
        $this->stopConsuming = true;

        $this->strategy->stopConsume();
    }

    public function run(): void
    {
        $executable = $this->middlewares->createExecutable(function (ReceivedMessage $message) {
            $this->messageHandler->handle($message);
        });

        $this->stopConsuming = false;

        while (!$this->stopConsuming) {
            $channel = $this->queueFactory->create()->getChannel();

            $this->configureBeforeConsume($channel);

            try {
                $queue = $this->queueFactory->create();

                $this->strategy->consume($queue, function (ReceivedMessage $message) use ($executable) {
                    try {
                        $executable($message);
                    } catch (ConsumerTimeoutExceedException $error) {
                        // Attention: the executable can't throw consumer timeout exception. But in test we throw
                        // this exception for transfer control to next catch and test success while iteration.
                        $message->ack();

                        throw $error;
                    } catch (StopConsumingException $error) {
                        // We must stop after N executes. In this case we success process message.
                        if (!$message->isAnswered()) {
                            $message->ack();
                        }

                        throw $error;
                    } catch (\Throwable $e) {
                        $message->nack($this->configuration->requeueOnError);

                        throw $e;
                    }

                    if (!$message->isAnswered()) {
                        $message->ack();
                    }
                }, $this->configuration->tagGenerator->generate());
            } catch (ConsumerTimeoutExceedException $e) {
                // Note: we can't cancel consumer, because rabbitmq can send next message to client
                // and client attach to existence consumer. As result we can receive error: orphaned envelope.
                // We full disconnect and try reconnect
                $channel->getConnection()->disconnect();

                $this->triggerEvent(Event::ConsumerTimeout);

                // The application must force throw consumer timeout exception.
                // Can be used manually for force stop consumer or in round robin consumer.
                // In other cases it's normal flow.
                if ($this->throwConsumerTimeoutExceededException) {
                    throw $e;
                }
            } catch (StopConsumingException) {
                // Disconnect, because inner system can has buffer for sending to amqp service.
                $channel->getConnection()->disconnect();

                $this->triggerEvent(Event::StopConsuming);

                return;
            } catch (\Throwable $e) {
                // Disconnect, because inner system can has buffer for sending to amqp service.
                $channel->getConnection()->disconnect();

                throw $e;
            }
        }
    }

    private function configureBeforeConsume(ChannelInterface $channel): void
    {
        $connection = $channel->getConnection();

        $originalReadTimeout = $connection->getReadTimeout();
        $expectedReadTimeout = $this->configuration->readTimeout;

        if (!$originalReadTimeout || $originalReadTimeout > $expectedReadTimeout) {
            // Change the read timeout.
            $connection->setReadTimeout($expectedReadTimeout);
        }

        $channel->setPrefetchCount($this->configuration->prefetchCount);
    }
}
