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
use FiveLab\Component\Amqp\Consumer\ConsumerInterface;
use FiveLab\Component\Amqp\Consumer\Handler\MessageHandlerInterface;
use FiveLab\Component\Amqp\Consumer\Handler\ThrowableMessageHandlerInterface;
use FiveLab\Component\Amqp\Consumer\Middleware\ConsumerMiddlewares;
use FiveLab\Component\Amqp\Consumer\Middleware\ConsumerMiddlewareInterface;
use FiveLab\Component\Amqp\Consumer\MiddlewareAwareInterface;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Exception\StopAfterNExecutesException;
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
    private QueueFactoryInterface $queueFactory;

    /**
     * @var MessageHandlerInterface
     */
    private MessageHandlerInterface $messageHandler;

    /**
     * @var ConsumerMiddlewares
     */
    private ConsumerMiddlewares $middlewares;

    /**
     * @var LoopConsumerConfiguration
     */
    private LoopConsumerConfiguration $configuration;

    /**
     * Indicate what we should throw exception if consumer timeout exceed.
     *
     * @var bool
     */
    private bool $throwConsumerTimeoutExceededException = false;

    /**
     * Constructor.
     *
     * @param QueueFactoryInterface     $queueFactory
     * @param MessageHandlerInterface   $messageHandler
     * @param ConsumerMiddlewares       $middlewares
     * @param LoopConsumerConfiguration $configuration
     */
    public function __construct(QueueFactoryInterface $queueFactory, MessageHandlerInterface $messageHandler, ConsumerMiddlewares $middlewares, LoopConsumerConfiguration $configuration)
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
        $executable = $this->middlewares->createExecutable(function (ReceivedMessageInterface $message) {
            $this->messageHandler->handle($message);
        });

        while (true) {
            $channel = $this->queueFactory->create()->getChannel();

            $this->configureBeforeConsume($channel);

            try {
                $this->queueFactory->create()->consume(function (ReceivedMessageInterface $message) use ($executable) {
                    try {
                        $executable($message);
                    } catch (ConsumerTimeoutExceedException $error) {
                        // Attention: the executable can't throw consumer timeout exception. But in test we throw
                        // this exception for transfer control to next catch and test success while iteration.
                        $message->ack();

                        throw $error;
                    } catch (StopAfterNExecutesException $error) {
                        // We must stop after N executes. In this case we success process message.
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
                        $message->ack();
                    }
                }, $this->configuration->getTagGenerator()->generate());
            } catch (ConsumerTimeoutExceedException $e) {
                // Note: we can't cancel consumer, because rabbitmq can send next message to client
                // and client attach to existence consumer. As result we can receive error: orphaned envelope.
                // We full disconnect and try reconnect
                $channel->getConnection()->disconnect();

                // The application must force throw consumer timeout exception.
                // Can be used manually for force stop consumer or in round robin consumer.
                // In other cases it's normal flow.
                if ($this->throwConsumerTimeoutExceededException) {
                    throw $e;
                }
            } catch (StopAfterNExecutesException $error) {
                // Disconnect, because inner system can has buffer for sending to amqp service.
                $channel->getConnection()->disconnect();

                return;
            } catch (\Throwable $e) {
                // Disconnect, because inner system can has buffer for sending to amqp service.
                $channel->getConnection()->disconnect();

                throw $e;
            }
        }
    }

    /**
     * Configure channel and connection before consume
     *
     * @param ChannelInterface $channel
     */
    private function configureBeforeConsume(ChannelInterface $channel): void
    {
        $connection = $channel->getConnection();

        $originalReadTimeout = $connection->getReadTimeout();
        $expectedReadTimeout = $this->configuration->getReadTimeout();

        if (!$originalReadTimeout || $originalReadTimeout > $expectedReadTimeout) {
            // Change the read timeout.
            $connection->setReadTimeout($expectedReadTimeout);
        }

        $channel->setPrefetchCount($this->configuration->getPrefetchCount());
    }
}
