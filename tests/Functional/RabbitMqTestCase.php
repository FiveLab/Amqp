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

namespace FiveLab\Component\Amqp\Tests\Functional;

use FiveLab\Component\Amqp\Adapter\Amqp\Queue\AmqpQueue;
use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use PHPUnit\Framework\TestCase;

abstract class RabbitMqTestCase extends TestCase
{
    /**
     * @var AmqpManagement
     */
    protected $management;

    /**
     * @var AmqpClearer
     */
    protected $clearer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        if (!$this->getRabbitMqHost()) {
            self::markTestSkipped('The RABBITMQ_HOST env variable was not found.');
        }

        $this->management = new AmqpManagement(
            $this->getRabbitMqHost(),
            $this->getRabbitMqManagementPort(),
            $this->getRabbitMqLogin(),
            $this->getRabbitMqPassword(),
            $this->getRabbitMqVhost()
        );

        $this->clearer = new AmqpClearer($this->management);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $this->clearer->clear();

        $this->management = null;
        $this->clearer = null;
    }

    /**
     * Get the AMQP host
     *
     * @return string|null
     */
    protected function getRabbitMqHost(): ?string
    {
        return \getenv('RABBITMQ_HOST') ?: null;
    }

    /**
     * Get rabbitmq port
     *
     * @return int
     */
    protected function getRabbitMqPort(): int
    {
        return \getenv('RABBITMQ_PORT') ? (int) \getenv('RABBITMQ_PORT') : 5672;
    }

    /**
     * Get management port
     *
     * @return int
     */
    protected function getRabbitMqManagementPort(): int
    {
        return \getenv('RABBITMQ_MANAGEMENT_PORT') ? (int) \getenv('RABBITMQ_MANAGEMENT_PORT') : 15672;
    }

    /**
     * Get rabbitmq vhost
     *
     * @return string
     */
    protected function getRabbitMqVhost(): string
    {
        return \getenv('RABBITMQ_VHOST') ?: '/';
    }

    /**
     * Get rabbitmq login
     *
     * @return string
     */
    protected function getRabbitMqLogin(): string
    {
        return \getenv('RABBITMQ_LOGIN') ?: 'guest';
    }

    /**
     * Get password for connect to rabbitmq
     *
     * @return string
     */
    protected function getRabbitMqPassword(): string
    {
        return \getenv('RABBITMQ_PASSWORD') ?: 'guest';
    }

    /**
     * Assert what the queue is empty
     *
     * @param QueueFactoryInterface $queueFactory
     */
    protected static function assertQueueEmpty(QueueFactoryInterface $queueFactory): void
    {
        $queue = $queueFactory->create();
        $lastMessage = $queue->get();

        self::assertNull($lastMessage, \sprintf('The queue %s is not empty.', $queue->getName()));
    }

    /**
     * Asset what the queue contain N messages
     *
     * @param QueueFactoryInterface $queueFactory
     * @param int                   $countMessages
     */
    protected static function assertQueueContainsCountMessages(QueueFactoryInterface $queueFactory, int $countMessages): void
    {
        $queue = $queueFactory->create();

        $messages = [];

        while ($message = $queue->get()) {
            $messages[] = $message;
        }

        self::assertCount($countMessages, $messages, \sprintf(
            'The queue %s contain %d messages, but expected %d messages.',
            $queue->getName(),
            \count($messages),
            $countMessages
        ));
    }

    /**
     * Get all messages from queue
     *
     * @param QueueFactoryInterface $queueFactory
     *
     * @return array|ReceivedMessageInterface[]
     */
    protected function getAllMessagesFromQueue(QueueFactoryInterface $queueFactory): array
    {
        $queue = $queueFactory->create();

        $messages = [];

        while ($message = $queue->get()) {
            $messages[] = $message;
        }

        return $messages;
    }

    /**
     * Get last message from queue
     *
     * @param QueueFactoryInterface $queueFactory
     *
     * @return ReceivedMessageInterface
     */
    public function getLastMessageFromQueue(QueueFactoryInterface $queueFactory): ReceivedMessageInterface
    {
        $queue = $queueFactory->create();

        $lastMessage = $queue->get();

        if (!$lastMessage) {
            throw new \RuntimeException(\sprintf(
                'The queue "%s" is empty.',
                $queue->getName()
            ));
        }

        return $lastMessage;
    }

    /**
     * Assert what the queue has bindings
     *
     * @param array  $bindingsInfo
     * @param string $expectedExchangeName
     * @param string $expectedRoutingKey
     */
    protected static function assertQueueBindingExists(array $bindingsInfo, string $expectedExchangeName, string $expectedRoutingKey): void
    {
        $entry = null;

        foreach ($bindingsInfo as $bindingInfo) {
            if ($bindingInfo['destination_type'] !== 'queue') {
                continue;
            }

            if ($expectedExchangeName === $bindingInfo['source'] && $expectedRoutingKey === $bindingInfo['routing_key']) {
                $entry = $bindingInfo;
                break;
            }
        }

        self::assertNotEmpty($entry, \sprintf(
            'Cannot find binding from exchange "%s" by routing key "%s".',
            $expectedExchangeName,
            $expectedRoutingKey
        ));
    }
}
