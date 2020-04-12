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

use FiveLab\Component\Amqp\Binding\Definition\BindingCollection;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinition;
use FiveLab\Component\Amqp\Consumer\Loop\LoopConsumer;
use FiveLab\Component\Amqp\Consumer\Loop\LoopConsumerConfiguration;
use FiveLab\Component\Amqp\Consumer\Middleware\ConsumerMiddlewareCollection;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Message\MessageInterface;
use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use FiveLab\Component\Amqp\Tests\Functional\Consumer\Handler\MessageHandlerMock;
use FiveLab\Component\Amqp\Tests\Functional\Consumer\Handler\ThrowableMessageHandlerMock;
use FiveLab\Component\Amqp\Tests\Functional\RabbitMqTestCase;

abstract class LoopConsumerTestCase extends RabbitMqTestCase
{
    /**
     * @var QueueFactoryInterface
     */
    private $queueFactory;

    /**
     * @var MessageHandlerMock
     */
    private $messageHandler;

    /**
     * @var ThrowableMessageHandlerMock
     */
    private $throwableMessageHandler;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->management->createQueue('some');
        $this->management->createExchange(AMQP_EX_TYPE_DIRECT, 'test.direct');
        $this->management->queueBind('some', 'test.direct', 'test');

        $definition = new QueueDefinition(
            'some',
            new BindingCollection(new BindingDefinition('test.direct', 'test'))
        );

        $this->queueFactory = $this->createQueueFactory($definition);
        $this->messageHandler = new MessageHandlerMock('test');
        $this->throwableMessageHandler = new ThrowableMessageHandlerMock('test');
    }

    /**
     * Create the queue factory
     *
     * @param QueueDefinition $definition
     *
     * @return QueueFactoryInterface
     */
    abstract protected function createQueueFactory(QueueDefinition $definition): QueueFactoryInterface;

    /**
     * @test
     */
    public function shouldSuccessConsume(): void
    {
        $this->publishMessages(50);

        $consumer = new LoopConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new ConsumerMiddlewareCollection(),
            new LoopConsumerConfiguration(2)
        );

        $this->runConsumer($consumer);

        self::assertCount(50, $this->messageHandler->getReceivedMessages());
    }

    /**
     * @test
     */
    public function shouldSuccessAutomaticallyAckMessageOnCatchError(): void
    {
        $this->publishMessages(1);

        $consumer = new LoopConsumer(
            $this->queueFactory,
            $this->throwableMessageHandler,
            new ConsumerMiddlewareCollection(),
            new LoopConsumerConfiguration(2)
        );

        $this->throwableMessageHandler->shouldThrowException(new \RuntimeException('some'));

        $this->runConsumer($consumer);

        $messages = $this->getAllMessagesFromQueue($this->queueFactory);
        self::assertCount(0, $messages);
    }

    /**
     * @test
     */
    public function shouldSuccessManuallyAckMessageOnCatchError(): void
    {
        $this->publishMessages(1);

        $consumer = new LoopConsumer(
            $this->queueFactory,
            $this->throwableMessageHandler,
            new ConsumerMiddlewareCollection(),
            new LoopConsumerConfiguration(2)
        );

        $this->throwableMessageHandler->shouldThrowException(new \RuntimeException('some'));
        $this->throwableMessageHandler->onCatchError(static function (ReceivedMessageInterface $message) {
            $message->ack();
        });

        $this->runConsumer($consumer);

        $messages = $this->getAllMessagesFromQueue($this->queueFactory);
        self::assertCount(0, $messages);
    }

    /**
     * @test
     */
    public function shouldSuccessManuallyNackMessageOnCatchError(): void
    {
        $this->publishMessages(1);

        $consumer = new LoopConsumer(
            $this->queueFactory,
            $this->throwableMessageHandler,
            new ConsumerMiddlewareCollection(),
            new LoopConsumerConfiguration(2)
        );

        $this->throwableMessageHandler->shouldThrowException(new \RuntimeException('some'));
        $this->throwableMessageHandler->onCatchError(static function (ReceivedMessageInterface $message) {
            $message->nack(false);
        });

        $this->runConsumer($consumer);

        $messages = $this->getAllMessagesFromQueue($this->queueFactory);
        self::assertCount(0, $messages);
    }

    /**
     * @test
     */
    public function shouldSuccessRequeueMessageIfCatchErrorHandlerThrowException(): void
    {
        $this->publishMessage('some');

        $consumer = new LoopConsumer(
            $this->queueFactory,
            $this->throwableMessageHandler,
            new ConsumerMiddlewareCollection(),
            new LoopConsumerConfiguration(2)
        );

        $this->throwableMessageHandler->shouldThrowException(new \RuntimeException('some'));
        $this->throwableMessageHandler->onCatchError(static function (ReceivedMessageInterface $message, \Throwable $error) {
            throw $error;
        });

        try {
            $this->runConsumer($consumer);

            self::fail('should throw exception');
        } catch (\RuntimeException $e) {
            self::assertEquals('some', $e->getMessage(), 'exception message nor equals');

            $message = $this->getLastMessageFromQueue($this->queueFactory);
            self::assertEquals('some', $message->getPayload()->getData());
        }
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
        } catch (ConsumerTimeoutExceedException $e) {
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
            $this->publishMessage('some payload '.$i);
        }
    }

    /**
     * Publish message to broker
     *
     * @param string $payload
     */
    private function publishMessage(string $payload = 'some payload'): void
    {
        $this->management->publishMessage('test.direct', 'test', $payload);
    }
}
