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
use FiveLab\Component\Amqp\Consumer\ConsumerConfiguration;
use FiveLab\Component\Amqp\Consumer\Handler\MessageHandlers;
use FiveLab\Component\Amqp\Consumer\Middleware\ConsumerMiddlewares;
use FiveLab\Component\Amqp\Consumer\Middleware\ProxyMessageToAnotherExchangeMiddleware;
use FiveLab\Component\Amqp\Consumer\Middleware\StopAfterNExecutesMiddleware;
use FiveLab\Component\Amqp\Consumer\SingleConsumer;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Exception\MessageHandlerNotSupportedException;
use FiveLab\Component\Amqp\Exchange\Definition\ExchangeDefinition;
use FiveLab\Component\Amqp\Exchange\ExchangeFactoryInterface;
use FiveLab\Component\Amqp\Exchange\Registry\ExchangeFactoryRegistry;
use FiveLab\Component\Amqp\Exchange\Registry\ExchangeFactoryRegistryInterface;
use FiveLab\Component\Amqp\Message\ReceivedMessage;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use FiveLab\Component\Amqp\Tests\Functional\Consumer\Handler\MessageHandlerMock;
use FiveLab\Component\Amqp\Tests\Functional\Consumer\Handler\ThrowableMessageHandlerMock;
use FiveLab\Component\Amqp\Tests\Functional\Consumer\Middleware\MiddlewareMock;
use FiveLab\Component\Amqp\Tests\Functional\RabbitMqTestCase;
use PHPUnit\Framework\Attributes\Test;

abstract class SingleConsumerTestCase extends RabbitMqTestCase
{
    private QueueFactoryInterface $queueFactory;
    private QueueFactoryInterface $proxyQueueFactory;
    private ExchangeFactoryRegistryInterface $exchangeRegistry;

    abstract protected function createQueueFactory(QueueDefinition $definition): QueueFactoryInterface;

    abstract protected function createProxyExchangeFactory(ExchangeDefinition $definition): ExchangeFactoryInterface;

    protected function setUp(): void
    {
        parent::setUp();

        $this->management->createQueue('some');
        $this->management->createExchange(AMQP_EX_TYPE_DIRECT, 'test.direct');
        $this->management->queueBind('some', 'test.direct', 'test');

        $this->management->createQueue('proxy');
        $this->management->createExchange(AMQP_EX_TYPE_DIRECT, 'proxy.direct');
        $this->management->queueBind('proxy', 'proxy.direct', 'test');

        $this->management->publishMessage('test.direct', 'test', 'some payload');

        $queueDefinition = new QueueDefinition(
            'some',
            new BindingDefinitions(new BindingDefinition('test.direct', 'test'))
        );

        $this->queueFactory = $this->createQueueFactory($queueDefinition);

        $proxyQueueDefinition = new QueueDefinition(
            'proxy',
            new BindingDefinitions(new BindingDefinition('proxy.direct', 'test'))
        );

        $this->proxyQueueFactory = $this->createQueueFactory($proxyQueueDefinition);

        $proxyExchangeDefinition = new ExchangeDefinition('proxy.direct', AMQP_EX_TYPE_DIRECT);
        $proxyExchange = $this->createProxyExchangeFactory($proxyExchangeDefinition);

        $this->exchangeRegistry = new ExchangeFactoryRegistry();
        $this->exchangeRegistry->add('proxy.direct', $proxyExchange);
    }

    #[Test]
    public function shouldSuccessConsume(): void
    {
        $handler = new MessageHandlerMock('test');
        $consumer = new SingleConsumer($this->queueFactory, $handler, new ConsumerMiddlewares(), new ConsumerConfiguration());
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

        $consumer = new SingleConsumer($this->queueFactory, $handler, new ConsumerMiddlewares(), new ConsumerConfiguration());
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

        $consumer = new SingleConsumer($this->queueFactory, $handler, new ConsumerMiddlewares(), new ConsumerConfiguration());
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

        $consumer = new SingleConsumer($this->queueFactory, $handler, new ConsumerMiddlewares(new StopAfterNExecutesMiddleware(2)), new ConsumerConfiguration());
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

        $consumer = new SingleConsumer($this->queueFactory, $handler, new ConsumerMiddlewares(), new ConsumerConfiguration());
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

        $consumer = new SingleConsumer($this->queueFactory, $handler, new ConsumerMiddlewares(), new ConsumerConfiguration());

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
    public function shouldSuccessCallToMiddleware(): void
    {
        $handler = new MessageHandlerMock('test');
        $middleware = new MiddlewareMock();

        $consumer = new SingleConsumer($this->queueFactory, $handler, new ConsumerMiddlewares($middleware), new ConsumerConfiguration());
        $this->runConsumer($consumer);

        $receivedMessagesOnHandler = $handler->getReceivedMessages();
        $receivedMessagesOnMiddleware = $handler->getReceivedMessages();

        self::assertCount(1, $receivedMessagesOnHandler);
        self::assertCount(1, $receivedMessagesOnMiddleware);

        self::assertEquals($receivedMessagesOnMiddleware, $receivedMessagesOnHandler);
    }

    #[Test]
    public function shouldSuccessProxyMessageToAnotherExchange(): void
    {
        $handler = new MessageHandlerMock('test');
        $middleware = new ProxyMessageToAnotherExchangeMiddleware($this->exchangeRegistry, 'proxy.direct');

        $consumer = new SingleConsumer($this->queueFactory, $handler, new ConsumerMiddlewares($middleware), new ConsumerConfiguration());
        $this->runConsumer($consumer);

        $messages = $this->getAllMessagesFromQueue($this->proxyQueueFactory);

        self::assertCount(1, $messages);

        $message = $messages[0];

        self::assertEquals('some payload', $message->payload->data);
        self::assertEquals('proxy.direct', $message->exchangeName);
        self::assertEquals('test', $message->routingKey);
    }

    #[Test]
    public function shouldThrowExceptionIfMessageHandlerNotSupported(): void
    {
        $this->expectException(MessageHandlerNotSupportedException::class);
        $this->expectExceptionMessage('Not any message handler supports for message in queue "some" from "test.direct" exchange by "test" routing key.');

        $handler = new MessageHandlerMock('foo-bar');
        $handlers = new MessageHandlers($handler);

        $consumer = new SingleConsumer($this->queueFactory, $handlers, new ConsumerMiddlewares(), new ConsumerConfiguration());

        $consumer->run();
    }

    private function runConsumer(SingleConsumer $consumer): void
    {
        $consumer->getQueue()->getChannel()->getConnection()->setReadTimeout(1);

        try {
            $consumer->run();
        } catch (ConsumerTimeoutExceedException) {
            // Timeout or max executes. Normal flow.
        }
    }
}
