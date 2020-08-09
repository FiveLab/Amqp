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

use FiveLab\Component\Amqp\Binding\Definition\BindingDefinitions;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinition;
use FiveLab\Component\Amqp\Consumer\Middleware\ConsumerMiddlewares;
use FiveLab\Component\Amqp\Consumer\Middleware\StopAfterNExecutesMiddleware;
use FiveLab\Component\Amqp\Consumer\Spool\SpoolConsumer;
use FiveLab\Component\Amqp\Consumer\Spool\SpoolConsumerConfiguration;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;
use FiveLab\Component\Amqp\Message\ReceivedMessages;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use FiveLab\Component\Amqp\Tests\Functional\Consumer\Handler\MessageHandlerMock;
use FiveLab\Component\Amqp\Tests\Functional\RabbitMqTestCase;

abstract class SpoolConsumerTestCase extends RabbitMqTestCase
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
     * {@inheritdoc}
     */
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
        $this->publishMessages(97);

        $consumer = new SpoolConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new ConsumerMiddlewares(),
            new SpoolConsumerConfiguration(10, 1)
        );

        $consumer->throwExceptionOnConsumerTimeoutExceed();

        try {
            $consumer->run();
        } catch (ConsumerTimeoutExceedException $e) {
            // Normal flow.
        }

        self::assertCount(97, $this->messageHandler->getReceivedMessages());
        self::assertCount(97, $this->messageHandler->getFlushedMessages());
        self::assertEquals(10, $this->messageHandler->getCountFlushes());
        self::assertQueueEmpty($this->queueFactory);
    }

    /**
     * @test
     */
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
            new ConsumerMiddlewares(),
            new SpoolConsumerConfiguration(10, 1, 0, true)
        );

        $consumer->throwExceptionOnConsumerTimeoutExceed();

        try {
            $consumer->run();
        } catch (\RuntimeException $e) {
            if ('some message' !== $e->getMessage()) {
                // We must receive other exception.
                throw $e;
            }
        }

        self::assertQueueContainsCountMessages($this->queueFactory, 10);
    }

    /**
     * @test
     */
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
            new ConsumerMiddlewares(),
            new SpoolConsumerConfiguration(10, 1, 0, false)
        );

        $consumer->throwExceptionOnConsumerTimeoutExceed();

        try {
            $consumer->run();
        } catch (\RuntimeException $e) {
            if ('some message' !== $e->getMessage()) {
                // We must receive other exception.
                throw $e;
            }
        }

        self::assertQueueContainsCountMessages($this->queueFactory, 5);
    }

    /**
     * @test
     */
    public function shouldReturnMessagesToBrokerIfFlushFailed(): void
    {
        $this->publishMessages(10);

        $this->messageHandler->setFlushCallback(static function () {
            throw new \RuntimeException('some message');
        });

        $consumer = new SpoolConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new ConsumerMiddlewares(),
            new SpoolConsumerConfiguration(5, 1, 0, true)
        );

        $consumer->throwExceptionOnConsumerTimeoutExceed();

        try {
            $consumer->run();
        } catch (\RuntimeException $e) {
            if ('some message' !== $e->getMessage()) {
                // We must receive other exception.
                throw $e;
            }
        }

        self::assertQueueContainsCountMessages($this->queueFactory, 10);
    }

    /**
     * @test
     */
    public function shouldNotReturnMessagesToBrokerIfFlushFailedAndRequeueIsFalse(): void
    {
        $this->publishMessages(10);

        $this->messageHandler->setFlushCallback(static function () {
            throw new \RuntimeException('some message');
        });

        $consumer = new SpoolConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new ConsumerMiddlewares(),
            new SpoolConsumerConfiguration(5, 1, 0, false)
        );

        $consumer->throwExceptionOnConsumerTimeoutExceed();

        try {
            $consumer->run();
        } catch (\RuntimeException $e) {
            if ('some message' !== $e->getMessage()) {
                // We must receive other exception.
                throw $e;
            }
        }

        self::assertQueueContainsCountMessages($this->queueFactory, 5);
    }

    /**
     * @test
     */
    public function shouldReturnMessagesToBrokerOnlyNotAckedMessagesIfFlushFalied(): void
    {
        $this->publishMessages(5);

        $this->messageHandler->setFlushCallback(static function (ReceivedMessages $receivedMessages) {
            foreach ($receivedMessages as $receivedMessage) {
                if ($receivedMessage->getPayload()->getData() === 'message #4') {
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
            new ConsumerMiddlewares(),
            new SpoolConsumerConfiguration(5, 1)
        );

        $consumer->throwExceptionOnConsumerTimeoutExceed();

        try {
            $consumer->run();

            self::fail('must throw exception');
        } catch (\InvalidArgumentException $e) {
            $nonProcessMessages = \array_map(static function (ReceivedMessageInterface $receivedMessage) {
                return $receivedMessage->getPayload()->getData();
            }, $this->getAllMessagesFromQueue($this->queueFactory));

            self::assertEquals([
                'message #4',
                'message #5',
            ], $nonProcessMessages);

            throw $e;
        }
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfMessageHandlerTryAnsweringToBroker(): void
    {
        $this->publishMessages(10);

        $processedIterations = 0;

        $this->messageHandler->setHandlerCallback(static function (ReceivedMessageInterface $message) use (&$processedIterations) {
            if (4 === $processedIterations) {
                $message->ack();
            }

            $processedIterations++;
        });

        $consumer = new SpoolConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new ConsumerMiddlewares(),
            new SpoolConsumerConfiguration(10, 1)
        );

        $consumer->throwExceptionOnConsumerTimeoutExceed();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The message handler "FiveLab\Component\Amqp\Tests\Functional\Consumer\Handler\MessageHandlerMock" is flushable and can\'t directly answering to broker on handle message.');

        try {
            $consumer->run();
        } catch (ConsumerTimeoutExceedException $e) {
            // Nothing action.
            self::fail('The consumer should throw exception if message handler trying to answering to broker.');
        }
    }

    /**
     * @test
     *
     * @see https://github.com/php-amqp/php-amqp/issues/327
     */
    public function shouldNotThrowOrphanedEnvelope(): void
    {
        $this->publishMessage('message #1');
        $this->publishMessage('message #2');
        $this->publishMessage('message #3');

        $handledMessages = 0;

        $consumer = new SpoolConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new ConsumerMiddlewares(),
            new SpoolConsumerConfiguration(10, 1)
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

            self::fail('Must throw exception');
        } catch (ConsumerTimeoutExceedException $error) {
            $receivedMessages = \array_map(static function (ReceivedMessageInterface $message) {
                return $message->getPayload()->getData();
            }, $this->messageHandler->getReceivedMessages());

            self::assertEquals([
                'message #1',
                'message #2',
                'message #3',
            ], $receivedMessages);

            $flushedMessages = \array_map(static function (ReceivedMessageInterface $message) {
                return $message->getPayload()->getData();
            }, $this->messageHandler->getFlushedMessages());

            self::assertEquals([
                'message #1',
                'message #2',
                'message #3',
            ], $flushedMessages);

            self::assertQueueEmpty($this->queueFactory);
        }
    }

    /**
     * @test
     */
    public function shouldSavePrefetchConfigurationAfterConsumerTimeout(): void
    {
        $this->publishMessage('message #1');
        $this->publishMessage('message #2');
        $this->publishMessage('message #3');

        $handledMessages = 0;

        $consumer = new SpoolConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new ConsumerMiddlewares(),
            new SpoolConsumerConfiguration(10, 1)
        );

        $this->messageHandler->setHandlerCallback(function () use (&$handledMessages, $consumer) {
            $channel = $this->queueFactory->create()->getChannel();
            $connection = $channel->getConnection();

            self::assertEquals(10, $channel->getPrefetchCount());
            self::assertEquals(1, $connection->getReadTimeout());

            $handledMessages++;

            if ($handledMessages >= 3) {
                $consumer->throwExceptionOnConsumerTimeoutExceed();
            }

            throw new ConsumerTimeoutExceedException();
        });

        try {
            $consumer->run();

            self::fail('Must throw exception');
        } catch (ConsumerTimeoutExceedException $error) {
            self::assertCount(3, $this->messageHandler->getFlushedMessages());
        }
    }

    /**
     * @test
     */
    public function shouldSuccessProcessOnStopAfterNExecutes(): void
    {
        $this->publishMessages(12);

        $consumer = new SpoolConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new ConsumerMiddlewares(new StopAfterNExecutesMiddleware(5)),
            new SpoolConsumerConfiguration(100, 1)
        );

        $consumer->throwExceptionOnConsumerTimeoutExceed();

        $consumer->run();

        self::assertCount(5, $this->messageHandler->getFlushedMessages());
        self::assertEquals(1, $this->messageHandler->getCountFlushes());
        self::assertQueueContainsCountMessages($this->queueFactory, 7);
    }

    /**
     * @test
     *
     * @dataProvider providePrefetchAndMessageCount
     *
     * @param int $prefetchCount
     * @param int $messageCount
     * @param int $expectedFlushCallTimes
     *
     * @throws \Throwable
     */
    public function shouldFlushWithZeroReadTimeout(int $prefetchCount, int $messageCount, int $expectedFlushCallTimes): void
    {
        $this->publishMessages($messageCount);

        $flushCalledTimes = 0;
        $this->messageHandler->setFlushCallback(static function () use (&$flushCalledTimes) {
            $flushCalledTimes++;
        });

        $handleCalledTimes = 0;
        $this->messageHandler->setHandlerCallback(function () use (&$handleCalledTimes) {
            $handleCalledTimes++;
        });

        $consumer = new SpoolConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new ConsumerMiddlewares(),
            new SpoolConsumerConfiguration($prefetchCount, 1, 1, true)
        );

        $consumer->throwExceptionOnConsumerTimeoutExceed();

        $this->queueFactory->create()->getChannel()->getConnection()->setReadTimeout(0);

        try {
            $consumer->run();
        } catch (ConsumerTimeoutExceedException $e) {
            // Normal expected flow.
        }

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

    /**
     * @return array
     */
    public function providePrefetchAndMessageCount(): array
    {
        return [
            'message amount equal to prefetch count'    => [10, 10, 1],
            'message amount multiple of prefetch count' => [5, 15, 3],
            'message amount less than prefetch count'   => [10, 7, 1],
            'message amount more than prefetch count'   => [10, 13, 2],
        ];
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
        $this->management->publishMessage('ex-spool', '', $payload);
    }
}
