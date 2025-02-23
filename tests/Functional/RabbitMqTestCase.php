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

use FiveLab\Component\Amqp\Connection\Driver;
use FiveLab\Component\Amqp\Connection\Dsn;
use FiveLab\Component\Amqp\Message\ReceivedMessage;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use PHPUnit\Framework\TestCase;

abstract class RabbitMqTestCase extends TestCase
{
    protected ?AmqpManagement $management = null;
    protected ?AmqpClearer $clearer = null;

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

    protected function tearDown(): void
    {
        $this->clearer->clear();

        $this->management = null;
        $this->clearer = null;
    }

    protected function getRabbitMqDsn(Driver $driver, ?array $options = null): Dsn
    {
        return new Dsn(
            $driver,
            $this->getRabbitMqHost(),
            $this->getRabbitMqPort(),
            $this->getRabbitMqVhost(),
            $this->getRabbitMqLogin(),
            $this->getRabbitMqPassword(),
            $options ?: ['read_timeout' => 2]
        );
    }

    protected function getRabbitMqHost(): ?string
    {
        return \getenv('RABBITMQ_HOST') ?: null;
    }

    protected function getRabbitMqPort(): int
    {
        return \getenv('RABBITMQ_PORT') ? (int) \getenv('RABBITMQ_PORT') : 5672;
    }

    protected function getRabbitMqManagementPort(): int
    {
        return \getenv('RABBITMQ_MANAGEMENT_PORT') ? (int) \getenv('RABBITMQ_MANAGEMENT_PORT') : 15672;
    }

    protected function getRabbitMqVhost(): string
    {
        return \getenv('RABBITMQ_VHOST') ?: '/';
    }

    protected function getRabbitMqLogin(): string
    {
        return \getenv('RABBITMQ_LOGIN') ?: 'guest';
    }

    protected function getRabbitMqPassword(): string
    {
        return \getenv('RABBITMQ_PASSWORD') ?: 'guest';
    }

    protected static function assertQueueEmpty(QueueFactoryInterface $queueFactory): void
    {
        $queue = $queueFactory->create();
        $lastMessage = $queue->get();

        self::assertNull($lastMessage, \sprintf('The queue %s is not empty.', $queue->getName()));
    }

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

    protected function getAllMessagesFromQueue(QueueFactoryInterface $queueFactory): array
    {
        $queue = $queueFactory->create();

        $messages = [];

        while ($message = $queue->get()) {
            $messages[] = $message;
        }

        return $messages;
    }

    public function getLastMessageFromQueue(QueueFactoryInterface $queueFactory): ReceivedMessage
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
