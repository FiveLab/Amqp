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

use FiveLab\Component\Amqp\AmqpEvents;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinition;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinitions;
use FiveLab\Component\Amqp\Consumer\ConsumerConfiguration;
use FiveLab\Component\Amqp\Consumer\Handler\MessageHandlers;
use FiveLab\Component\Amqp\Consumer\SingleConsumer;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Exception\MessageHandlerNotSupportedException;
use FiveLab\Component\Amqp\Exchange\Registry\ExchangeFactoryRegistryInterface;
use FiveLab\Component\Amqp\Listener\StopAfterNExecutesListener;
use FiveLab\Component\Amqp\Message\ReceivedMessage;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use FiveLab\Component\Amqp\Tests\Functional\Consumer\Handler\MessageHandlerMock;
use FiveLab\Component\Amqp\Tests\Functional\Consumer\Handler\ThrowableMessageHandlerMock;
use FiveLab\Component\Amqp\Tests\Functional\RabbitMqTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\EventDispatcher\EventDispatcher;

abstract class SingleConsumerTestCase extends RabbitMqTestCase
{
    private QueueFactoryInterface $queueFactory;
    private ExchangeFactoryRegistryInterface $exchangeRegistry;

    abstract protected function createQueueFactory(QueueDefinition $definition): QueueFactoryInterface;

    protected function setUp(): void
    {
        parent::setUp();

        $this->management->createQueue('some');
        $this->management->createExchange(AMQP_EX_TYPE_DIRECT, 'test.direct');
        $this->management->queueBind('some', 'test.direct', 'test');

        $this->management->publishMessage('test.direct', 'test', 'some payload');

        $queueDefinition = new QueueDefinition(
            'some',
            new BindingDefinitions(new BindingDefinition('test.direct', 'test'))
        );

        $this->queueFactory = $this->createQueueFactory($queueDefinition);
    }

    #[Test]
    public function shouldSuccessConsume(): void
    {
        $handler = new MessageHandlerMock('test');
        $consumer = new SingleConsumer($this->queueFactory, $handler, new ConsumerConfiguration());
        $this->runConsumer($consumer);

        $receivedMessages = $handler->getReceivedMessages();

        self::assertCount(1, $receivedMessages);
        self::assertQueueEmpty($this->queueFactory);
    }

    #[Test]
    public function shouldSuccessConsumeIfMessageHandleAckMessage(): void
    {
        $handler = new MessageHandlerMock('test');

        $handler->setHandlerCallback(function (ReceivedMessage $receivedMessage) {
            $receivedMessage->ack();
        });

        $consumer = new SingleConsumer($this->queueFactory, $handler, new ConsumerConfiguration());
        $this->runConsumer($consumer);

        $receivedMessages = $handler->getReceivedMessages();

        self::assertCount(1, $receivedMessages);
        self::assertQueueEmpty($this->queueFactory);
    }

    #[Test]
    public function shouldSuccessConsumeIfMessageHandlerNackWithoutRequeueMessage(): void
    {
        $handler = new MessageHandlerMock('test');

        $handler->setHandlerCallback(function (ReceivedMessage $receivedMessage) {
            $receivedMessage->nack(false);
        });

        $consumer = new SingleConsumer($this->queueFactory, $handler, new ConsumerConfiguration());
        $this->runConsumer($consumer);

        $receivedMessages = $handler->getReceivedMessages();

        self::assertCount(1, $receivedMessages);
        self::assertQueueEmpty($this->queueFactory);
    }

    #[Test]
    public function shouldSuccessConsumeIfMessageHandlerNackWithRequeueMessage(): void
    {
        $handler = new MessageHandlerMock('test');

        $handler->setHandlerCallback(static function (ReceivedMessage $receivedMessage) {
            $receivedMessage->nack(true);
        });

        $consumer = new SingleConsumer($this->queueFactory, $handler, new ConsumerConfiguration());
        $consumer->setEventDispatcher($eventDispatcher = new EventDispatcher());
        $eventDispatcher->addListener(AmqpEvents::PROCESSED_MESSAGE, (new StopAfterNExecutesListener($eventDispatcher, 2))->onProcessedMessage(...));

        $this->runConsumer($consumer);

        $receivedMessages = $handler->getReceivedMessages();

        self::assertCount(2, $receivedMessages);

        // We should reconnect because the client should flush all nacked messages.
        $this->queueFactory->create()->getChannel()->getConnection()->reconnect();

        $lastMessage = $this->queueFactory->create()->get();

        self::assertNotNull($lastMessage, 'The queue should contain one message.');
    }

    #[Test]
    public function shouldSuccessCatchErrorOnMessageHandler(): void
    {
        $handler = new ThrowableMessageHandlerMock('test');
        $handler->shouldThrowException(new \Exception('some foo bar'));

        $consumer = new SingleConsumer($this->queueFactory, $handler, new ConsumerConfiguration());
        $this->runConsumer($consumer);

        $receivedMessages = $handler->getReceivedMessages();

        self::assertCount(1, $receivedMessages);

        self::assertNotNull($handler->getCatchReceivedMessage());
        self::assertNotNull($handler->getCatchError());

        self::assertEquals(new \Exception('some foo bar'), $handler->getCatchError());
        self::assertEquals($receivedMessages[0], $handler->getCatchReceivedMessage());
    }

    #[Test]
    public function shouldRequeueMessageIfCatchErrorHandleThrowException(): void
    {
        /** @var ReceivedMessage $receivedMessage */
        $receivedMessage = null;

        $handler = new ThrowableMessageHandlerMock('test');
        $handler->shouldThrowException(new \RuntimeException('some'));

        $handler->onCatchError(static function (ReceivedMessage $message, \Throwable $e) use (&$receivedMessage) {
            $receivedMessage = $message;

            throw $e;
        });

        $consumer = new SingleConsumer($this->queueFactory, $handler, new ConsumerConfiguration());

        try {
            $this->runConsumer($consumer);

            self::fail('Must throw exception');
        } catch (\RuntimeException $e) {
            self::assertEquals('some', $e->getMessage());

            $lastMessage = $this->getLastMessageFromQueue($this->queueFactory);

            self::assertEquals($receivedMessage->payload, $lastMessage->payload);
        }
    }

    #[Test]
    public function shouldThrowExceptionIfMessageHandlerNotSupported(): void
    {
        $handler = new MessageHandlerMock('foo-bar');
        $handlers = new MessageHandlers($handler);

        $consumer = new SingleConsumer($this->queueFactory, $handlers, new ConsumerConfiguration());

        $this->expectException(MessageHandlerNotSupportedException::class);
        $this->expectExceptionMessage('Not any message handler supports for message in queue "some" from "test.direct" exchange by "test" routing key.');

        $consumer->run();
    }

    private function runConsumer(SingleConsumer $consumer): void
    {
        $consumer->getQueue()->getChannel()->getConnection()->setReadTimeout(0.2);

        try {
            $consumer->run();
        } catch (ConsumerTimeoutExceedException) {
            // Timeout or max executes. Normal flow.
        }
    }
}
