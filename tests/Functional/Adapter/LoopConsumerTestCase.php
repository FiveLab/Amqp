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

use FiveLab\Component\Amqp\Consumer\Loop\LoopConsumer;
use FiveLab\Component\Amqp\Consumer\Loop\LoopConsumerConfiguration;
use FiveLab\Component\Amqp\Consumer\Middleware\MiddlewareCollection;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Queue\Definition\QueueBindingCollection;
use FiveLab\Component\Amqp\Queue\Definition\QueueBindingDefinition;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use FiveLab\Component\Amqp\Tests\Functional\Consumer\Handler\MessageHandlerMock;
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
            new QueueBindingCollection(new QueueBindingDefinition('test.direct', 'test'))
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
        $this->publishMessages(50);

        $consumer = new LoopConsumer(
            $this->queueFactory,
            $this->messageHandler,
            new MiddlewareCollection(),
            new LoopConsumerConfiguration(2)
        );

        $consumer->throwExceptionOnConsumerTimeoutExceed();

        try {
            $consumer->run();
        } catch (ConsumerTimeoutExceedException $e) {
            // Normal flow.
        }

        self::assertCount(50, $this->messageHandler->getReceivedMessages());
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
