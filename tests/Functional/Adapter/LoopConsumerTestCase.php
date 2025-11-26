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
use FiveLab\Component\Amqp\Consumer\ConsumerStoppedReason;
use FiveLab\Component\Amqp\Consumer\Loop\LoopConsumer;
use FiveLab\Component\Amqp\Consumer\Loop\LoopConsumerConfiguration;
use FiveLab\Component\Amqp\Event\ConsumerStoppedEvent;
use FiveLab\Component\Amqp\Event\ProcessedMessageEvent;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Listener\StopAfterNExecutesListener;
use FiveLab\Component\Amqp\Message\ReceivedMessage;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use FiveLab\Component\Amqp\Tests\Functional\Consumer\Handler\MessageHandlerMock;
use FiveLab\Component\Amqp\Tests\Functional\Consumer\Handler\ThrowableMessageHandlerMock;
use FiveLab\Component\Amqp\Tests\Functional\RabbitMqTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\EventDispatcher\EventDispatcher;

abstract class LoopConsumerTestCase extends RabbitMqTestCase
{
    private QueueFactoryInterface $queueFactory;
    private MessageHandlerMock $messageHandler;
    private ThrowableMessageHandlerMock $throwableMessageHandler;

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

    abstract protected function createQueueFactory(QueueDefinition $definition): QueueFactoryInterface;

    #[Test]
    public function shouldSuccessConsume(): void
    {
        $this->publishMessages(50);

        $consumer = new LoopConsumer(
            $this->queueFactory,
            $this->messageHandler,
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

        $consumer = new LoopConsumer($this->queueFactory, $this->throwableMessageHandler, new LoopConsumerConfiguration(1));

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

        $consumer = new LoopConsumer($this->queueFactory, $this->throwableMessageHandler, new LoopConsumerConfiguration(1));

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
            new LoopConsumerConfiguration(1)
        );

        $this->throwableMessageHandler->shouldThrowException(new \RuntimeException('some'));
        $this->throwableMessageHandler->onCatchError(static fn(ReceivedMessage $message, \Throwable $error) => throw $error);

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
            new LoopConsumerConfiguration(1)
        );

        $this->messageHandler->setHandlerCallback(function (ReceivedMessage $message) use (&$handledMessages, $consumer) {
            $handledMessages++;

            if ($handledMessages >= 3) {
                $this->registerTimeoutListenerForStop($consumer);
            }

            $message->ack();

            throw new ConsumerTimeoutExceedException();
        });

        $this->runConsumer($consumer, false, false);

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

    #[Test]
    public function shouldSaveConnectionConfigurationAfterConsumerTimeout(): void
    {
        $this->publishMessages(3);

        $handledMessages = 0;

        $consumer = new LoopConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new LoopConsumerConfiguration(1, true, 5)
        );

        $this->messageHandler->setHandlerCallback(function (ReceivedMessage $message) use (&$handledMessages, $consumer) {
            $channel = $this->queueFactory->create()->getChannel();
            $connection = $channel->getConnection();

            self::assertEquals(5, $channel->getPrefetchCount());
            self::assertEquals(1, $connection->getReadTimeout());

            $handledMessages++;

            if ($handledMessages >= 3) {
                $this->registerTimeoutListenerForStop($consumer);
            }

            $message->ack();

            throw new ConsumerTimeoutExceedException();
        });

        $this->runConsumer($consumer, false, false);

        self::assertCount(3, $this->messageHandler->getReceivedMessages());
        self::assertQueueEmpty($this->queueFactory);
    }

    #[Test]
    public function shouldSuccessProcessOnStopAfterNExecutes(): void
    {
        $this->publishMessages(12);

        $consumer = new LoopConsumer($this->queueFactory, $this->messageHandler, new LoopConsumerConfiguration(1));
        $consumer->setEventDispatcher($eventDispatcher = new EventDispatcher());
        $eventDispatcher->addListener(ProcessedMessageEvent::class, (new StopAfterNExecutesListener($eventDispatcher, 5))->onProcessedMessage(...));

        $this->runConsumer($consumer);

        self::assertCount(5, $this->messageHandler->getReceivedMessages());
        self::assertQueueContainsCountMessages($this->queueFactory, 7);
    }

    private function runConsumer(LoopConsumer $consumer, bool $changeReadTimeout = true, bool $registerTimeoutListener = true): void
    {
        if (!$consumer->getEventDispatcher()) {
            $consumer->setEventDispatcher(new EventDispatcher());
        }

        if ($registerTimeoutListener) {
            $this->registerTimeoutListenerForStop($consumer);
        }

        if ($changeReadTimeout) {
            $consumer->getQueue()->getChannel()->getConnection()->setReadTimeout(0.2);
        }

        $consumer->run();
    }

    private function registerTimeoutListenerForStop(LoopConsumer $consumer): void
    {
        $consumer->getEventDispatcher()->addListener(ConsumerStoppedEvent::class, static function (ConsumerStoppedEvent $event): void {
            if ($event->reason === ConsumerStoppedReason::Timeout) {
                $event->consumer->stop();
            }
        });
    }

    private function publishMessages(int $messages): void
    {
        for ($i = 1; $i <= $messages; $i++) {
            $this->publishMessage('message #'.$i);
        }
    }

    private function publishMessage(string $payload = 'some payload'): void
    {
        $this->management->publishMessage('ex-loop', '', $payload);
    }
}
