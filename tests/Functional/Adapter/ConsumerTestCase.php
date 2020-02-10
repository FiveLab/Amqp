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
use FiveLab\Component\Amqp\Consumer\ConsumerConfiguration;
use FiveLab\Component\Amqp\Consumer\Middleware\ConsumerMiddlewareCollection;
use FiveLab\Component\Amqp\Consumer\Middleware\ProxyMessageToAnotherExchangeMiddleware;
use FiveLab\Component\Amqp\Consumer\Middleware\StopAfterNExecutesMiddleware;
use FiveLab\Component\Amqp\Consumer\SingleConsumer;
use FiveLab\Component\Amqp\Consumer\Handler\MessageHandlerChain;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Exception\MessageHandlerNotSupportedException;
use FiveLab\Component\Amqp\Exception\StopAfterNExecutesException;
use FiveLab\Component\Amqp\Exchange\Definition\ExchangeDefinition;
use FiveLab\Component\Amqp\Exchange\ExchangeFactoryInterface;
use FiveLab\Component\Amqp\Exchange\Registry\ExchangeFactoryRegistry;
use FiveLab\Component\Amqp\Exchange\Registry\ExchangeFactoryRegistryInterface;
use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use FiveLab\Component\Amqp\Tests\Functional\Consumer\Handler\MessageHandlerMock;
use FiveLab\Component\Amqp\Tests\Functional\Consumer\Handler\ThrowableMessageHandlerMock;
use FiveLab\Component\Amqp\Tests\Functional\Consumer\Middleware\MiddlewareMock;
use FiveLab\Component\Amqp\Tests\Functional\RabbitMqTestCase;

abstract class ConsumerTestCase extends RabbitMqTestCase
{
    /**
     * @var QueueFactoryInterface
     */
    private $queueFactory;

    /**
     * @var QueueFactoryInterface
     */
    private $proxyQueueFactory;

    /**
     * @var ExchangeFactoryRegistryInterface
     */
    private $exchangeRegistry;

    /**
     * Create the queue factory
     *
     * @param QueueDefinition $definition
     *
     * @return QueueFactoryInterface
     */
    abstract protected function createQueueFactory(QueueDefinition $definition): QueueFactoryInterface;

    /**
     * Create the exchange factory
     *
     * @param ExchangeDefinition $definition
     *
     * @return ExchangeFactoryInterface
     */
    abstract protected function createProxyExchangeFactory(ExchangeDefinition $definition): ExchangeFactoryInterface;

    /**
     * {@inheritdoc}
     */
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
            new BindingCollection(new BindingDefinition('test.direct', 'test'))
        );

        $this->queueFactory = $this->createQueueFactory($queueDefinition);

        $proxyQueueDefinition = new QueueDefinition(
            'proxy',
            new BindingCollection(new BindingDefinition('proxy.direct', 'test'))
        );

        $this->proxyQueueFactory = $this->createQueueFactory($proxyQueueDefinition);

        $proxyExchangeDefinition = new ExchangeDefinition('proxy.direct', AMQP_EX_TYPE_DIRECT);
        $proxyExchange = $this->createProxyExchangeFactory($proxyExchangeDefinition);

        $this->exchangeRegistry = new ExchangeFactoryRegistry();
        $this->exchangeRegistry->add('proxy.direct', $proxyExchange);
    }

    /**
     * @test
     */
    public function shouldSuccessConsume(): void
    {
        $handler = new MessageHandlerMock('test');
        $consumer = new SingleConsumer($this->queueFactory, $handler, new ConsumerMiddlewareCollection(), new ConsumerConfiguration());
        $this->runConsumer($consumer);

        $receivedMessages = $handler->getReceivedMessages();

        self::assertCount(1, $receivedMessages);
        self::assertQueueEmpty($this->queueFactory);
    }

    /**
     * @test
     */
    public function shouldSuccessConsumeIfMessageHandleAckMessage(): void
    {
        $handler = new MessageHandlerMock('test');

        $handler->setHandlerCallback(function (ReceivedMessageInterface $receivedMessage) {
            $receivedMessage->ack();
        });

        $consumer = new SingleConsumer($this->queueFactory, $handler, new ConsumerMiddlewareCollection(), new ConsumerConfiguration());
        $this->runConsumer($consumer);

        $receivedMessages = $handler->getReceivedMessages();

        self::assertCount(1, $receivedMessages);
        self::assertQueueEmpty($this->queueFactory);
    }

    /**
     * @test
     */
    public function shouldSuccessConsumeIfMessageHandlerNackWithoutRequeueMessage(): void
    {
        $handler = new MessageHandlerMock('test');

        $handler->setHandlerCallback(function (ReceivedMessageInterface $receivedMessage) {
            $receivedMessage->nack(false);
        });

        $consumer = new SingleConsumer($this->queueFactory, $handler, new ConsumerMiddlewareCollection(), new ConsumerConfiguration());
        $this->runConsumer($consumer);

        $receivedMessages = $handler->getReceivedMessages();

        self::assertCount(1, $receivedMessages);
        self::assertQueueEmpty($this->queueFactory);
    }

    /**
     * @test
     */
    public function shouldSuccessConsumeIfMessageHandlerNackWithRequeueMessage(): void
    {
        $handler = new MessageHandlerMock('test');

        $handler->setHandlerCallback(function (ReceivedMessageInterface $receivedMessage) {
            $receivedMessage->nack(true);
        });

        $consumer = new SingleConsumer($this->queueFactory, $handler, new ConsumerMiddlewareCollection(new StopAfterNExecutesMiddleware(2)), new ConsumerConfiguration());
        $this->runConsumer($consumer);

        $receivedMessages = $handler->getReceivedMessages();

        self::assertCount(2, $receivedMessages);

        // We should reconnect because the client should flush all nacked messages.
        $this->queueFactory->create()->getChannel()->getConnection()->reconnect();

        $lastMessage = $this->queueFactory->create()->get();

        self::assertNotNull($lastMessage, 'The queue should contain one message.');
    }

    /**
     * @test
     */
    public function shouldSuccessCatchErrorOnMessageHandler(): void
    {
        $handler = new ThrowableMessageHandlerMock('test');
        $handler->shouldThrowException(new \Exception('some foo bar'));

        $consumer = new SingleConsumer($this->queueFactory, $handler, new ConsumerMiddlewareCollection(), new ConsumerConfiguration());
        $this->runConsumer($consumer);

        $receivedMessages = $handler->getReceivedMessages();

        self::assertCount(1, $receivedMessages);

        self::assertNotNull($handler->getCatchReceivedMessage());
        self::assertNotNull($handler->getCatchError());

        self::assertEquals(new \Exception('some foo bar'), $handler->getCatchError());
        self::assertEquals($receivedMessages[0], $handler->getCatchReceivedMessage());
    }

    /**
     * @test
     */
    public function shouldSuccessCallToMiddleware(): void
    {
        $handler = new MessageHandlerMock('test');
        $middleware = new MiddlewareMock();

        $consumer = new SingleConsumer($this->queueFactory, $handler, new ConsumerMiddlewareCollection($middleware), new ConsumerConfiguration());
        $this->runConsumer($consumer);

        $receivedMessagesOnHandler = $handler->getReceivedMessages();
        $receivedMessagesOnMiddleware = $handler->getReceivedMessages();

        self::assertCount(1, $receivedMessagesOnHandler);
        self::assertCount(1, $receivedMessagesOnMiddleware);

        self::assertEquals($receivedMessagesOnMiddleware, $receivedMessagesOnHandler);
    }

    /**
     * @test
     */
    public function shouldSuccessProxyMessageToAnotherExchange(): void
    {
        $handler = new MessageHandlerMock('test');
        $middleware = new ProxyMessageToAnotherExchangeMiddleware($this->exchangeRegistry, 'proxy.direct');

        $consumer = new SingleConsumer($this->queueFactory, $handler, new ConsumerMiddlewareCollection($middleware), new ConsumerConfiguration());
        $this->runConsumer($consumer);

        $messages = $this->getAllMessagesFromQueue($this->proxyQueueFactory);

        self::assertCount(1, $messages);

        $message = $messages[0];

        self::assertEquals('some payload', $message->getPayload()->getData());
        self::assertEquals('proxy.direct', $message->getExchangeName());
        self::assertEquals('test', $message->getRoutingKey());
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfMessageHandlerNotSupported(): void
    {
        self::expectException(MessageHandlerNotSupportedException::class);
        self::expectExceptionMessage('Not found supported message handler.');

        $handler = new MessageHandlerMock('foo-bar');
        $chainHandler = new MessageHandlerChain($handler);

        $consumer = new SingleConsumer($this->queueFactory, $chainHandler, new ConsumerMiddlewareCollection(), new ConsumerConfiguration());

        $consumer->run();
    }

    /**
     * Run consumer
     *
     * @param SingleConsumer $consumer
     */
    private function runConsumer(SingleConsumer $consumer): void
    {
        try {
            $consumer->run();
        } catch (ConsumerTimeoutExceedException | StopAfterNExecutesException $e) {
            // Timeout or max executes. Normal flow.
        }
    }
}
