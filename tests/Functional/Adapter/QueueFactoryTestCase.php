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

use FiveLab\Component\Amqp\Argument\ArgumentDefinitions;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinitions;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinition;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Message\Payload;
use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;
use FiveLab\Component\Amqp\Queue\Definition\Arguments\QueueMasterLocatorArgument;
use FiveLab\Component\Amqp\Queue\Definition\Arguments\QueueModeArgument;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use FiveLab\Component\Amqp\Tests\Functional\RabbitMqTestCase;

abstract class QueueFactoryTestCase extends RabbitMqTestCase
{
    /**
     * Create queue factory for testing
     *
     * @param QueueDefinition $definition
     *
     * @return QueueFactoryInterface
     */
    abstract protected function createQueueFactory(QueueDefinition $definition): QueueFactoryInterface;

    /**
     * @test
     */
    public function shouldSuccessCreateWithDefaults(): void
    {
        $definition = new QueueDefinition('some');

        $factory = $this->createQueueFactory($definition);
        $factory->create();

        $queueInfo = $this->management->queueByName('some');

        self::assertTrue($queueInfo['durable']);
        self::assertFalse($queueInfo['exclusive']);
    }

    /**
     * @test
     */
    public function shouldSuccessCreateWithoutDurable(): void
    {
        $definition = new QueueDefinition(
            'some',
            null,
            null,
            false
        );

        $factory = $this->createQueueFactory($definition);
        $factory->create();

        $queueInfo = $this->management->queueByName('some');

        self::assertFalse($queueInfo['durable']);
    }

    /**
     * @test
     */
    public function shouldSuccessCreateWithPassiveFlag(): void
    {
        $this->management->createQueue('foo');

        $definition = new QueueDefinition(
            'foo',
            null,
            null,
            true,
            true
        );

        $factory = $this->createQueueFactory($definition);
        $factory->create();

        $this->management->queueByName('foo');

        $this->expectNotToPerformAssertions();
    }

    /**
     * Note: test must override this method for correct set throw error.
     *
     * @test
     */
    public function shouldThrowExceptionWithCreatePassiveQueueAndQueueWasNotFound(): void
    {
        $definition = new QueueDefinition(
            'foo',
            null,
            null,
            true,
            true
        );

        $factory = $this->createQueueFactory($definition);
        $factory->create();
    }

    /**
     * @test
     */
    public function shouldSuccessCreateExclusiveQueue(): void
    {
        $queueName = 'test_queue_exclusive_'.\uniqid();

        $definition = new QueueDefinition(
            $queueName,
            null,
            null,
            true,
            false,
            true
        );

        $factory = $this->createQueueFactory($definition);
        $factory->create();

        $queueInfo = $this->management->queueByName($queueName);

        self::assertTrue($queueInfo['exclusive']);
    }

    /**
     * @test
     */
    public function shouldSuccessCreateAutoDeleteQueue(): void
    {
        $definition = new QueueDefinition(
            'test_queue_auto_delete',
            null,
            null,
            false,
            false,
            false,
            true
        );

        $factory = $this->createQueueFactory($definition);
        $factory->create();

        $queueInfo = $this->management->queueByName('test_queue_auto_delete');

        self::assertTrue($queueInfo['auto_delete']);
    }

    /**
     * @test
     */
    public function shouldSuccessCreateWithBindings(): void
    {
        $this->management->createExchange(AMQP_EX_TYPE_DIRECT, 'test.direct1');
        $this->management->createExchange(AMQP_EX_TYPE_DIRECT, 'test.direct2');

        $definition = new QueueDefinition(
            'some',
            new BindingDefinitions(
                new BindingDefinition('test.direct1', 'key1'),
                new BindingDefinition('test.direct2', 'key1'),
                new BindingDefinition('test.direct2', 'key2')
            )
        );

        $factory = $this->createQueueFactory($definition);
        $factory->create();

        $bindings = $this->management->queueBindings('some');

        self::assertQueueBindingExists($bindings, 'test.direct1', 'key1');
        self::assertQueueBindingExists($bindings, 'test.direct2', 'key1');
        self::assertQueueBindingExists($bindings, 'test.direct2', 'key2');
    }

    /**
     * @test
     */
    public function shouldSuccessCreateWithoutBindings(): void
    {
        $this->management->createExchange(AMQP_EX_TYPE_DIRECT, 'test.direct1');
        $this->management->createExchange(AMQP_EX_TYPE_DIRECT, 'test.direct2');

        $this->management->createQueue('some');

        $this->management->queueBind('some', 'test.direct1', 'key1');
        $this->management->queueBind('some', 'test.direct2', 'key1');
        $this->management->queueBind('some', 'test.direct2', 'key2');

        $definition = new QueueDefinition(
            'some',
            null,
            new BindingDefinitions(
                new BindingDefinition('test.direct1', 'key1'),
                new BindingDefinition('test.direct2', 'key1'),
                new BindingDefinition('test.direct2', 'key2')
            )
        );

        $factory = $this->createQueueFactory($definition);
        $factory->create();

        $bindings = $this->management->queueBindings('some');

        self::assertCount(1, $bindings);
    }

    /**
     * @test
     */
    public function shouldSuccessCreateWithArguments(): void
    {
        $definition = new QueueDefinition(
            'some',
            null,
            null,
            false,
            false,
            false,
            false,
            new ArgumentDefinitions(
                new QueueModeArgument('default'),
                new QueueMasterLocatorArgument('random')
            )
        );

        $factory = $this->createQueueFactory($definition);
        $factory->create();

        $queueInfo = $this->management->queueByName('some');
        $arguments = $queueInfo['arguments'];

        self::assertEquals([
            'x-queue-master-locator' => 'random',
            'x-queue-mode'           => 'default',
        ], $arguments);
    }

    /**
     * @test
     */
    public function shouldSuccessConsumeMessage(): void
    {
        $this->management->createExchange(AMQP_EX_TYPE_DIRECT, 'test.direct');
        $this->management->createQueue('some');
        $this->management->queueBind('some', 'test.direct', 'test');

        $this->management->publishMessage('test.direct', 'test', 'some foo bar');

        $definition = new QueueDefinition(
            'some',
            new BindingDefinitions(
                new BindingDefinition('test.direct', 'test')
            )
        );

        $factory = $this->createQueueFactory($definition);
        $queue = $factory->create();

        $consumed = false;

        try {
            $queue->consume(static function (ReceivedMessageInterface $receivedMessage) use (&$consumed) {
                $consumed = true;

                self::assertEquals(new Payload('some foo bar'), $receivedMessage->getPayload());
                self::assertFalse($receivedMessage->getOptions()->isPersistent());
                self::assertNotNull($receivedMessage->getDeliveryTag());
                self::assertEquals('test', $receivedMessage->getRoutingKey());
            });
        } catch (ConsumerTimeoutExceedException $e) {
            // Timeout. Normal flow.
        }

        self::assertTrue($consumed, 'The queue not receive message.');
    }

    /**
     * @test
     */
    public function shouldSuccessGetCountMessages(): void
    {
        $this->management->createExchange(AMQP_EX_TYPE_DIRECT, 'test.direct');
        $this->management->createQueue('some');
        $this->management->queueBind('some', 'test.direct', 'test');

        for ($i = 0; $i < 12; $i++) {
            $this->management->publishMessage('test.direct', 'test', 'some foo bar '.$i);
        }

        $definition = new QueueDefinition(
            'some',
            new BindingDefinitions(
                new BindingDefinition('test.direct', 'test')
            )
        );

        $factory = $this->createQueueFactory($definition);
        $queue = $factory->create();

        $countMessages = $queue->countMessages();

        self::assertEquals(12, $countMessages);
    }
}
