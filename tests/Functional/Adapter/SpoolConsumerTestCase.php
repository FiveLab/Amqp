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
use FiveLab\Component\Amqp\Consumer\ConsumerStoppedReason;
use FiveLab\Component\Amqp\Consumer\Spool\SpoolConsumer;
use FiveLab\Component\Amqp\Consumer\Spool\SpoolConsumerConfiguration;
use FiveLab\Component\Amqp\Consumer\Strategy\EventableTickHandler;
use FiveLab\Component\Amqp\Consumer\Strategy\LoopConsumeStrategy;
use FiveLab\Component\Amqp\Event\ConsumerStartedEvent;
use FiveLab\Component\Amqp\Event\ConsumerStoppedEvent;
use FiveLab\Component\Amqp\Event\ProcessedMessageEvent;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Listener\StopAfterNExecutesListener;
use FiveLab\Component\Amqp\Message\ReceivedMessage;
use FiveLab\Component\Amqp\Message\ReceivedMessages;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use FiveLab\Component\Amqp\Tests\CatchEventsSubscriber;
use FiveLab\Component\Amqp\Tests\Functional\Consumer\Handler\MessageHandlerMock;
use FiveLab\Component\Amqp\Tests\Functional\RabbitMqTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\EventDispatcher\EventDispatcher;

abstract class SpoolConsumerTestCase extends RabbitMqTestCase
{
    private QueueFactoryInterface $queueFactory;
    private MessageHandlerMock $messageHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->management->createQueue('q-spool');
        $this->management->createExchange('fanout', 'ex-spool');
        $this->management->queueBind('q-spool', 'ex-spool', '');

        $definition = new QueueDefinition(
            'q-spool',
            new BindingDefinitions(new BindingDefinition('ex-spool', ''))
        );

        $this->queueFactory = $this->createQueueFactory($definition);
        $this->messageHandler = new MessageHandlerMock('');
    }

    abstract protected function createQueueFactory(QueueDefinition $definition): QueueFactoryInterface;

    #[Test]
    public function shouldSuccessConsume(): void
    {
        $this->publishMessages(97);

        $consumer = new SpoolConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new SpoolConsumerConfiguration(10, 1)
        );

        $this->runConsumer($consumer);

        self::assertCount(97, $this->messageHandler->getReceivedMessages());
        self::assertCount(97, $this->messageHandler->getFlushedMessages());
        self::assertEquals(10, $this->messageHandler->getCountFlushes());
        self::assertQueueEmpty($this->queueFactory);
    }

    #[Test]
    public function shouldSuccessReturnMessagesToBrokerIfSpoolFailed(): void
    {
        $this->publishMessages(10);
        $processedIterations = 0;

        $this->messageHandler->setHandlerCallback(static function () use (&$processedIterations) {
            if (5 === $processedIterations) {
                throw new \RuntimeException('some message');
            }

            $processedIterations++;
        });

        $consumer = new SpoolConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new SpoolConsumerConfiguration(10, 1, 0, true)
        );

        try {
            $this->runConsumer($consumer);
        } catch (\RuntimeException $e) {
            if ('some message' !== $e->getMessage()) {
                // We must receive other exception.
                throw $e;
            }
        }

        self::assertQueueContainsCountMessages($this->queueFactory, 10);
    }

    #[Test]
    public function shouldNotReturnMessagesToBrokerIfSpoolFailedIfRequeueIsFalse(): void
    {
        $this->publishMessages(10);
        $processedIterations = 0;

        $this->messageHandler->setHandlerCallback(static function () use (&$processedIterations) {
            if (4 === $processedIterations) {
                throw new \RuntimeException('some message');
            }

            $processedIterations++;
        });

        $consumer = new SpoolConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new SpoolConsumerConfiguration(10, 1, 0, false)
        );

        try {
            $this->runConsumer($consumer);
        } catch (\RuntimeException $e) {
            if ('some message' !== $e->getMessage()) {
                // We must receive other exception.
                throw $e;
            }
        }

        self::assertQueueContainsCountMessages($this->queueFactory, 5);
    }

    #[Test]
    public function shouldReturnMessagesToBrokerIfFlushFailed(): void
    {
        $this->publishMessages(10);

        $this->messageHandler->setFlushCallback(static function () {
            throw new \RuntimeException('some message');
        });

        $consumer = new SpoolConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new SpoolConsumerConfiguration(5, 1, 0, true)
        );

        try {
            $this->runConsumer($consumer);
        } catch (\RuntimeException $e) {
            if ('some message' !== $e->getMessage()) {
                // We must receive other exception.
                throw $e;
            }
        }

        self::assertQueueContainsCountMessages($this->queueFactory, 10);
    }

    #[Test]
    public function shouldNotReturnMessagesToBrokerIfFlushFailedAndRequeueIsFalse(): void
    {
        $this->publishMessages(10);

        $this->messageHandler->setFlushCallback(static function () {
            throw new \RuntimeException('some message');
        });

        $consumer = new SpoolConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new SpoolConsumerConfiguration(5, 1, 0, false)
        );

        try {
            $this->runConsumer($consumer);
        } catch (\RuntimeException $e) {
            if ('some message' !== $e->getMessage()) {
                // We must receive other exception.
                throw $e;
            }
        }

        self::assertQueueContainsCountMessages($this->queueFactory, 5);
    }

    #[Test]
    public function shouldReturnMessagesToBrokerOnlyNotAckedMessagesIfFlushFalied(): void
    {
        $this->publishMessages(5);

        $this->messageHandler->setFlushCallback(static function (ReceivedMessages $receivedMessages) {
            foreach ($receivedMessages as $receivedMessage) {
                if ($receivedMessage->payload->data === 'message #4') {
                    throw new \InvalidArgumentException('some foo');
                }

                $receivedMessage->ack();
            }
        });

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('some foo');

        $consumer = new SpoolConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new SpoolConsumerConfiguration(5, 1)
        );

        try {
            $this->runConsumer($consumer);

            self::fail('must throw exception');
        } catch (\InvalidArgumentException $e) {
            $nonProcessMessages = \array_map(static function (ReceivedMessage $receivedMessage) {
                return $receivedMessage->payload->data;
            }, $this->getAllMessagesFromQueue($this->queueFactory));

            self::assertEquals([
                'message #4',
                'message #5',
            ], $nonProcessMessages);

            throw $e;
        }
    }

    #[Test]
    public function shouldThrowExceptionIfMessageHandlerTryAnsweringToBroker(): void
    {
        $this->publishMessages(10);

        $processedIterations = 0;

        $this->messageHandler->setHandlerCallback(static function (ReceivedMessage $message) use (&$processedIterations) {
            if (4 === $processedIterations) {
                $message->ack();
            }

            $processedIterations++;
        });

        $consumer = new SpoolConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new SpoolConsumerConfiguration(10, 1)
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('A flushable message handler can\'t return an acknowledgement to the broker.');

        $this->runConsumer($consumer);
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

        $consumer = new SpoolConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new SpoolConsumerConfiguration(10, 1)
        );

        $this->messageHandler->setHandlerCallback(function (ReceivedMessage $message) use (&$handledMessages, $consumer) {
            $handledMessages++;

            if ($handledMessages >= 3) {
                $this->registerTimeoutListenerForStop($consumer);
            }

            $message->ack();

            throw new ConsumerTimeoutExceedException();
        });

        $this->runConsumer($consumer, true, false);

        $receivedMessages = \array_map(static function (ReceivedMessage $message) {
            return $message->payload->data;
        }, $this->messageHandler->getReceivedMessages());

        self::assertEquals([
            'message #1',
            'message #2',
            'message #3',
        ], $receivedMessages);

        $flushedMessages = \array_map(static function (ReceivedMessage $message) {
            return $message->payload->data;
        }, $this->messageHandler->getFlushedMessages());

        self::assertEquals([
            'message #1',
            'message #2',
            'message #3',
        ], $flushedMessages);

        self::assertQueueEmpty($this->queueFactory);
    }

    #[Test]
    public function shouldSavePrefetchConfigurationAfterConsumerTimeout(): void
    {
        $this->publishMessage('message #1');
        $this->publishMessage('message #2');
        $this->publishMessage('message #3');

        $handledMessages = 0;

        $consumer = new SpoolConsumer($this->queueFactory, $this->messageHandler, new SpoolConsumerConfiguration(10, 1));

        $this->messageHandler->setHandlerCallback(function (ReceivedMessage $message) use (&$handledMessages, $consumer) {
            $channel = $this->queueFactory->create()->getChannel();
            $connection = $channel->getConnection();

            self::assertEquals(10, $channel->getPrefetchCount());
            self::assertEquals(1, $connection->getReadTimeout());

            $handledMessages++;

            if ($handledMessages >= 3) {
                $this->registerTimeoutListenerForStop($consumer);
            }

            $message->ack();

            throw new ConsumerTimeoutExceedException();
        });

        $this->runConsumer($consumer, true, false);

        self::assertCount(3, $this->messageHandler->getFlushedMessages());
    }

    #[Test]
    public function shouldSuccessProcessOnStopAfterNExecutes(): void
    {
        $this->publishMessages(12);

        $consumer = new SpoolConsumer($this->queueFactory, $this->messageHandler, new SpoolConsumerConfiguration(100, 1));
        $consumer->setEventDispatcher($eventDispatcher = new EventDispatcher());
        $eventDispatcher->addListener(AmqpEvents::PROCESSED_MESSAGE, (new StopAfterNExecutesListener(5))->onProcessedMessage(...));

        $this->runConsumer($consumer);

        self::assertCount(5, $this->messageHandler->getFlushedMessages());
        self::assertEquals(1, $this->messageHandler->getCountFlushes());
        self::assertQueueContainsCountMessages($this->queueFactory, 7);
    }

    #[Test]
    #[TestWith([10, 10, 1], 'message amount equal to prefetch count')]
    #[TestWith([5, 15, 3], 'message amount multiple of prefetch count')]
    #[TestWith([10, 7, 1], 'message amount less than prefetch count')]
    #[TestWith([10, 13, 2], 'message amount more than prefetch count')]
    public function shouldFlushWithZeroReadTimeout(int $prefetchCount, int $messageCount, int $expectedFlushCallTimes): void
    {
        $this->publishMessages($messageCount);

        $flushCalledTimes = 0;
        $handleCalledTimes = 0;

        $this->messageHandler->setFlushCallback(static function () use (&$flushCalledTimes) {
            $flushCalledTimes++;
        });

        $this->messageHandler->setHandlerCallback(function () use (&$handleCalledTimes) {
            $handleCalledTimes++;
        });

        $consumer = new SpoolConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new SpoolConsumerConfiguration($prefetchCount, 1, 1, true)
        );

        $this->queueFactory->create()->getChannel()->getConnection()->setReadTimeout(0);

        $this->runConsumer($consumer);

        self::assertEquals(
            $messageCount,
            $handleCalledTimes,
            \sprintf('expected %d handled messages, actually handled %d times', $messageCount, $handleCalledTimes)
        );

        self::assertEquals(
            $expectedFlushCallTimes,
            $flushCalledTimes,
            \sprintf('expected %d "flush" calls, actually called %d times', $expectedFlushCallTimes, $flushCalledTimes)
        );
    }

    #[Test]
    public function shouldSuccessReRunAfterStop(): void
    {
        $this->publishMessages(10);

        $consumer = new SpoolConsumer($this->queueFactory, $this->messageHandler, new SpoolConsumerConfiguration(10, 1, 1, true));
        $consumer->setEventDispatcher($eventDispatcher = new EventDispatcher());

        $eventDispatcher->addListener(AmqpEvents::PROCESSED_MESSAGE, static function (ProcessedMessageEvent $event): void {
            $event->consumer->stop();
        });

        $this->runConsumer($consumer);
        $this->runConsumer($consumer);

        self::assertCount(2, $this->messageHandler->getReceivedMessages());
    }

    #[Test]
    public function shouldSuccessDispatchEvents(): void
    {
        $this->publishMessages(1);

        $eventDispatcher = new EventDispatcher();

        $consumer = new SpoolConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new SpoolConsumerConfiguration(10, 1, 1, true),
            new LoopConsumeStrategy(tickHandler: new EventableTickHandler($eventDispatcher))
        );

        $consumer->setEventDispatcher($eventDispatcher);
        $eventDispatcher->addSubscriber($listener = new CatchEventsSubscriber());

        $this->runConsumer($consumer);

        $events = $listener->getCatchedEvents(null);

        self::assertArrayHasKey(AmqpEvents::CONSUMER_STARTED, $events);
        self::assertEquals([new ConsumerStartedEvent($consumer)], $events[AmqpEvents::CONSUMER_STARTED]);

        self::assertArrayHasKey(AmqpEvents::CONSUMER_TICK, $events);

        self::assertArrayHasKey(AmqpEvents::CONSUMER_STOPPED, $events, 'missed consumer stopped events');
        self::assertEquals([new ConsumerStoppedEvent($consumer, ConsumerStoppedReason::Timeout)], $events[AmqpEvents::CONSUMER_STOPPED]);

        self::assertArrayHasKey(AmqpEvents::RECEIVE_MESSAGE, $events, 'missed receive message events');
        self::assertCount(1, $events[AmqpEvents::RECEIVE_MESSAGE]);

        self::assertArrayHasKey(AmqpEvents::PROCESSED_MESSAGE, $events, 'missed processed message events');
        self::assertCount(1, $events[AmqpEvents::PROCESSED_MESSAGE]);
    }

    private function runConsumer(SpoolConsumer $consumer, bool $changeReadTimeout = true, bool $registerTimeoutListener = true): void
    {
        if (!$consumer->getEventDispatcher()) {
            $consumer->setEventDispatcher(new EventDispatcher());
        }

        if ($registerTimeoutListener) {
            $this->registerTimeoutListenerForStop($consumer);
        }

        if ($changeReadTimeout) {
            $consumer->getQueue()->getChannel()->getConnection()->setReadTimeout(0.1);
        }

        $consumer->run();
    }

    private function registerTimeoutListenerForStop(SpoolConsumer $consumer): void
    {
        $consumer->getEventDispatcher()->addListener(AmqpEvents::CONSUMER_STOPPED, static function (ConsumerStoppedEvent $event): void {
            if ($event->reason === ConsumerStoppedReason::Timeout) {
                $event->consumer->stop(false);
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
        $this->management->publishMessage('ex-spool', '', $payload);
    }
}
