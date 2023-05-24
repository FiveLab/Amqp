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

namespace FiveLab\Component\Amqp\Tests\Functional\Adapter;

use FiveLab\Component\Amqp\Binding\Definition\BindingDefinition;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinitions;
use FiveLab\Component\Amqp\Consumer\Loop\LoopConsumer;
use FiveLab\Component\Amqp\Consumer\Loop\LoopConsumerConfiguration;
use FiveLab\Component\Amqp\Consumer\Middleware\ConsumerMiddlewares;
use FiveLab\Component\Amqp\Consumer\Middleware\StopAfterNExecutesMiddleware;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Message\ReceivedMessage;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use FiveLab\Component\Amqp\Tests\Functional\Consumer\Handler\MessageHandlerMock;
use FiveLab\Component\Amqp\Tests\Functional\Consumer\Handler\ThrowableMessageHandlerMock;
use FiveLab\Component\Amqp\Tests\Functional\RabbitMqTestCase;
use PHPUnit\Framework\Attributes\Test;

abstract class LoopConsumerTestCase extends RabbitMqTestCase
{
    /**
     * @var QueueFactoryInterface
     */
    private QueueFactoryInterface $queueFactory;

    /**
     * @var MessageHandlerMock
     */
    private MessageHandlerMock $messageHandler;

    /**
     * @var ThrowableMessageHandlerMock
     */
    private ThrowableMessageHandlerMock $throwableMessageHandler;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->management->createQueue('q-loop');
        $this->management->createExchange('fanout', 'ex-loop');
        $this->management->queueBind('q-loop', 'ex-loop', '');

        $definition = new QueueDefinition(
            'q-loop',
            new BindingDefinitions(new BindingDefinition('ex-loop', ''))
        );

        $this->queueFactory = $this->createQueueFactory($definition);
        $this->messageHandler = new MessageHandlerMock('');
        $this->throwableMessageHandler = new ThrowableMessageHandlerMock('');
    }

    /**
     * Create the queue factory
     *
     * @param QueueDefinition $definition
     *
     * @return QueueFactoryInterface
     */
    abstract protected function createQueueFactory(QueueDefinition $definition): QueueFactoryInterface;

    #[Test]
    public function shouldSuccessConsume(): void
    {
        $this->publishMessages(50);

        $consumer = new LoopConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new ConsumerMiddlewares(),
            new LoopConsumerConfiguration(1)
        );

        $this->runConsumer($consumer);

        self::assertCount(50, $this->messageHandler->getReceivedMessages());
        self::assertQueueEmpty($this->queueFactory);
    }

    #[Test]
    public function shouldSuccessAutomaticallyAckMessageOnCatchError(): void
    {
        $this->publishMessages(1);

        $consumer = new LoopConsumer(
            $this->queueFactory,
            $this->throwableMessageHandler,
            new ConsumerMiddlewares(),
            new LoopConsumerConfiguration(1)
        );

        $this->throwableMessageHandler->shouldThrowException(new \RuntimeException('some'));

        $this->runConsumer($consumer);

        self::assertQueueEmpty($this->queueFactory);
    }

    #[Test]
    public function shouldSuccessManuallyAckMessageOnCatchError(): void
    {
        $this->publishMessages(1);

        $consumer = new LoopConsumer(
            $this->queueFactory,
            $this->throwableMessageHandler,
            new ConsumerMiddlewares(),
            new LoopConsumerConfiguration(1)
        );

        $this->throwableMessageHandler->shouldThrowException(new \RuntimeException('some'));
        $this->throwableMessageHandler->onCatchError(static function (ReceivedMessage $message) {
            $message->ack();
        });

        $this->runConsumer($consumer);

        self::assertQueueEmpty($this->queueFactory);
    }

    #[Test]
    public function shouldSuccessManuallyNackMessageOnCatchError(): void
    {
        $this->publishMessages(1);

        $consumer = new LoopConsumer(
            $this->queueFactory,
            $this->throwableMessageHandler,
            new ConsumerMiddlewares(),
            new LoopConsumerConfiguration(1)
        );

        $this->throwableMessageHandler->shouldThrowException(new \RuntimeException('some'));
        $this->throwableMessageHandler->onCatchError(static function (ReceivedMessage $message) {
            $message->nack(false);
        });

        $this->runConsumer($consumer);

        self::assertQueueEmpty($this->queueFactory);
    }

    #[Test]
    public function shouldSuccessRequeueMessageIfCatchErrorHandlerThrowException(): void
    {
        $this->publishMessage('some');

        $consumer = new LoopConsumer(
            $this->queueFactory,
            $this->throwableMessageHandler,
            new ConsumerMiddlewares(),
            new LoopConsumerConfiguration(1)
        );

        $this->throwableMessageHandler->shouldThrowException(new \RuntimeException('some'));
        $this->throwableMessageHandler->onCatchError(static function (ReceivedMessage $message, \Throwable $error) {
            throw $error;
        });

        try {
            $this->runConsumer($consumer);

            self::fail('should throw exception');
        } catch (\RuntimeException $e) {
            self::assertEquals('some', $e->getMessage(), 'exception message nor equals');

            $message = $this->getLastMessageFromQueue($this->queueFactory);
            self::assertEquals('some', $message->payload->data);
        }
    }

    /**
     * @see https://github.com/php-amqp/php-amqp/issues/327
     */
    #[Test]
    public function shouldNotThrowOrphanedEnvelope(): void
    {
        $this->publishMessage('message #1');
        $this->publishMessage('message #2');
        $this->publishMessage('message #3');

        $handledMessages = 0;

        $consumer = new LoopConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new ConsumerMiddlewares(),
            new LoopConsumerConfiguration(1)
        );

        $this->messageHandler->setHandlerCallback(static function () use (&$handledMessages, $consumer) {
            $handledMessages++;

            if ($handledMessages >= 3) {
                $consumer->throwExceptionOnConsumerTimeoutExceed();
            }

            throw new ConsumerTimeoutExceedException();
        });

        try {
            $consumer->run();

            self::fail('must throw exception');
        } catch (ConsumerTimeoutExceedException) {
            $receivedMessages = \array_map(static function (ReceivedMessage $receivedMessage) {
                return $receivedMessage->payload->data;
            }, $this->messageHandler->getReceivedMessages());

            self::assertEquals([
                'message #1',
                'message #2',
                'message #3',
            ], $receivedMessages);

            self::assertQueueEmpty($this->queueFactory);
        }
    }

    #[Test]
    public function shouldSaveConnectionConfigurationAfterConsumerTimeout(): void
    {
        $this->publishMessages(3);

        $handledMessages = 0;

        $consumer = new LoopConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new ConsumerMiddlewares(),
            new LoopConsumerConfiguration(1, true, 5)
        );

        $this->messageHandler->setHandlerCallback(function () use (&$handledMessages, $consumer) {
            $channel = $this->queueFactory->create()->getChannel();
            $connection = $channel->getConnection();

            self::assertEquals(5, $channel->getPrefetchCount());
            self::assertEquals(1, $connection->getReadTimeout());

            $handledMessages++;

            if ($handledMessages >= 3) {
                $consumer->throwExceptionOnConsumerTimeoutExceed();
            }

            throw new ConsumerTimeoutExceedException();
        });

        try {
            $consumer->run();

            self::fail('must throw exception');
        } catch (ConsumerTimeoutExceedException) {
            self::assertCount(3, $this->messageHandler->getReceivedMessages());
            self::assertQueueEmpty($this->queueFactory);
        }
    }

    #[Test]
    public function shouldSuccessProcessOnStopAfterNExecutes(): void
    {
        $this->publishMessages(12);

        $consumer = new LoopConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new ConsumerMiddlewares(new StopAfterNExecutesMiddleware(5)),
            new LoopConsumerConfiguration(1)
        );

        $consumer->throwExceptionOnConsumerTimeoutExceed();

        $consumer->run();

        self::assertCount(5, $this->messageHandler->getReceivedMessages());
        self::assertQueueContainsCountMessages($this->queueFactory, 7);
    }

    /**
     * Run consumer
     *
     * @param LoopConsumer $consumer
     */
    private function runConsumer(LoopConsumer $consumer): void
    {
        $consumer->throwExceptionOnConsumerTimeoutExceed();
        $consumer->getQueue()->getChannel()->getConnection()->setReadTimeout(1);

        try {
            $consumer->run();
        } catch (ConsumerTimeoutExceedException) {
            // Normal flow
        }
    }

    /**
     * Publish more messages
     *
     * @param int $messages
     */
    private function publishMessages(int $messages): void
    {
        for ($i = 1; $i <= $messages; $i++) {
            $this->publishMessage('message #'.$i);
        }
    }

    /**
     * Publish message to broker
     *
     * @param string $payload
     */
    private function publishMessage(string $payload = 'some payload'): void
    {
        $this->management->publishMessage('ex-loop', '', $payload);
    }
}
