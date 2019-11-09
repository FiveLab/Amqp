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

use FiveLab\Component\Amqp\Consumer\Middleware\MiddlewareCollection;
use FiveLab\Component\Amqp\Consumer\Spool\SpoolConsumer;
use FiveLab\Component\Amqp\Consumer\Spool\SpoolConsumerConfiguration;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;
use FiveLab\Component\Amqp\Queue\Definition\QueueBindingDefinition;
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

        $this->management->createQueue('some');
        $this->management->createExchange(AMQP_EX_TYPE_DIRECT, 'test.direct');
        $this->management->queueBind('some', 'test.direct', 'test');

        $definition = new QueueDefinition(
            'some',
            [
                new QueueBindingDefinition('test.direct', 'test'),
            ]
        );

        $this->queueFactory = $this->createQueueFactory($definition);
        $this->messageHandler = new MessageHandlerMock('test');
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
    public function shouldSuccessConsume()
    {
        $this->publishMessages(97);

        $consumer = new SpoolConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new MiddlewareCollection(),
            new SpoolConsumerConfiguration(10, 2)
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
    }

    /**
     * @test
     */
    public function shouldSuccessReturnMessagesToBrokerIfSpoolFailed(): void
    {
        $this->publishMessages(10);
        $processedIterations = 0;

        $this->messageHandler->setHandlerCallback(function () use (&$processedIterations) {
            if (5 === $processedIterations) {
                throw new \RuntimeException('some message');
            }

            $processedIterations++;
        });

        $consumer = new SpoolConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new MiddlewareCollection(),
            new SpoolConsumerConfiguration(10, 2, 0, true)
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

        // Check what the messages returned to broker.
        $messages = $this->getAllMessagesFromQueue($this->queueFactory);

        self::assertCount(10, $messages);
    }

    /**
     * @test
     */
    public function shouldNotReturnMessagesToBrokerIfSpoolFailedIfRequeueIsFalse(): void
    {
        $this->publishMessages(10);
        $processedIterations = 0;

        $this->messageHandler->setHandlerCallback(function () use (&$processedIterations) {
            if (4 === $processedIterations) {
                throw new \RuntimeException('some message');
            }

            $processedIterations++;
        });

        $consumer = new SpoolConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new MiddlewareCollection(),
            new SpoolConsumerConfiguration(10, 2, 0, false)
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

        // Check what the messages not returned to broker.
        $messages = $this->getAllMessagesFromQueue($this->queueFactory);

        self::assertCount(5, $messages);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfMessageHandlerTryAnsweringToBroker(): void
    {
        $this->publishMessages(10);

        $processedIterations = 0;

        $this->messageHandler->setHandlerCallback(function (ReceivedMessageInterface $message) use (&$processedIterations) {
            if (4 === $processedIterations) {
                $message->ack();
            }

            $processedIterations++;
        });

        $consumer = new SpoolConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new MiddlewareCollection(),
            new SpoolConsumerConfiguration(10, 2)
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
     */
    public function shouldReturnMessagesToBrokerIfFlushFailed(): void
    {
        $this->publishMessages(10);

        $this->messageHandler->setFlushCallback(function () {
            throw new \RuntimeException('some message');
        });

        $consumer = new SpoolConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new MiddlewareCollection(),
            new SpoolConsumerConfiguration(5, 2, 0, true)
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

        // Check what the messages returned to broker.
        $messages = $this->getAllMessagesFromQueue($this->queueFactory);

        self::assertCount(10, $messages);
    }

    /**
     * @test
     */
    public function shouldNotReturnMessagesToBrokerIfFlushFailedAndRequeueIsFalse(): void
    {
        $this->publishMessages(10);

        $this->messageHandler->setFlushCallback(function () {
            throw new \RuntimeException('some message');
        });

        $consumer = new SpoolConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new MiddlewareCollection(),
            new SpoolConsumerConfiguration(5, 2, 0, false)
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

        // Check what the messages returned to broker.
        $messages = $this->getAllMessagesFromQueue($this->queueFactory);

        self::assertCount(5, $messages);
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
