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

use FiveLab\Component\Amqp\Exchange\Definition\ExchangeDefinition;
use FiveLab\Component\Amqp\Exchange\ExchangeFactoryInterface;
use FiveLab\Component\Amqp\Exchange\ExchangeInterface;
use FiveLab\Component\Amqp\Message\Headers;
use FiveLab\Component\Amqp\Message\Identifier;
use FiveLab\Component\Amqp\Message\Message;
use FiveLab\Component\Amqp\Message\Options;
use FiveLab\Component\Amqp\Message\Payload;
use FiveLab\Component\Amqp\Tests\Functional\RabbitMqTestCase;
use PHPUnit\Framework\Attributes\Test;

abstract class ExchangeTestCase extends RabbitMqTestCase
{
    private ExchangeInterface $exchange;

    abstract protected function createExchangeFactory(ExchangeDefinition $definition): ExchangeFactoryInterface;

    protected function setUp(): void
    {
        parent::setUp();

        $exchangeDefinition = new ExchangeDefinition('test', AMQP_EX_TYPE_DIRECT);
        $this->exchange = $this->createExchangeFactory($exchangeDefinition)->create();

        $this->management->createQueue('test');
        $this->management->queueBind('test', 'test', 'some');
    }

    #[Test]
    public function shouldSuccessPublishViaDefaultExchange(): void
    {
        $this->management->createQueue('default_test');
        $exchangeDefinition = new ExchangeDefinition('', AMQP_EX_TYPE_DIRECT);
        $exchange = $this->createExchangeFactory($exchangeDefinition)->create();

        $exchange->publish(new Message(new Payload('some')), 'default_test');

        $retrieveMessages = $this->management->queueGetMessages('default_test', 1);

        self::assertCount(1, $retrieveMessages, 'The default_test queue is empty. Messages not published to queue via default exchange.');
    }

    #[Test]
    public function shouldSuccessPublishWithDefaults(): void
    {
        $message = new Message(new Payload('some foo bar'));

        $retrieveMessage = $this->publishMessage($message);

        self::assertEquals('text/plain', $retrieveMessage['properties']['content_type']);
        self::assertEquals(2, $retrieveMessage['properties']['delivery_mode']);
        self::assertEquals('some', $retrieveMessage['routing_key']);
        self::assertEquals('some foo bar', $retrieveMessage['payload']);
    }

    #[Test]
    public function shouldSuccessPublishWithCustomContentType(): void
    {
        $message = new Message(new Payload('{"a":"b"}', 'application/json'));

        $retrieveMessage = $this->publishMessage($message);

        self::assertEquals('application/json', $retrieveMessage['properties']['content_type']);
        self::assertEquals('{"a":"b"}', $retrieveMessage['payload']);
    }

    #[Test]
    public function shouldSuccessPublishWithCustomContentEncoding(): void
    {
        $message = new Message(new Payload('foo', 'text/plain', 'gzip'));

        $retrieveMessage = $this->publishMessage($message);

        self::assertEquals('foo', $retrieveMessage['payload']);
        self::assertEquals('gzip', $retrieveMessage['properties']['content_encoding']);
    }

    #[Test]
    public function shouldSuccessPublishWithoutDurableMode(): void
    {
        $message = new Message(new Payload('some foo bar'), new Options(false));

        $retrieveMessage = $this->publishMessage($message);

        self::assertEquals(1, $retrieveMessage['properties']['delivery_mode']);
    }

    #[Test]
    public function shouldSuccessPublishWithExpiration(): void
    {
        $message = new Message(new Payload('some foo bar'), new Options(true, 300000));

        $retrieveMessage = $this->publishMessage($message);

        self::assertEquals(300000, $retrieveMessage['properties']['expiration']);
    }

    #[Test]
    public function shouldSuccessPublishWithoutPriority(): void
    {
        $message = new Message(new Payload('some foo bar'));

        $retrieveMessage = $this->publishMessage($message);

        self::assertArrayNotHasKey('priority', $retrieveMessage['properties']);
    }

    #[Test]
    public function shouldSuccessPublishWithPriority(): void
    {
        $message = new Message(new Payload('some foo bar'), new Options(true, 0, 5));

        $retrieveMessage = $this->publishMessage($message);

        self::assertEquals(5, $retrieveMessage['properties']['priority']);
    }

    #[Test]
    public function shouldSuccessPublishWithHeaders(): void
    {
        $message = new Message(new Payload('some foo bar'), null, new Headers([
            'x-custom-1' => 'foo',
            'x-custom-2' => 'bar',
        ]));

        $retrieveMessage = $this->publishMessage($message);

        self::assertArrayHasKey('headers', $retrieveMessage['properties']);
        self::assertEquals([
            'x-custom-1' => 'foo',
            'x-custom-2' => 'bar',
        ], $retrieveMessage['properties']['headers']);
    }

    #[Test]
    public function shouldSuccessPublishWithIdentifier(): void
    {
        $message = new Message(new Payload('some foo'), null, null, new Identifier('m-id', 'a-id', 'guest'));

        $retrieveMessage = $this->publishMessage($message);

        self::assertArrayHasKey('message_id', $retrieveMessage['properties']);
        self::assertArrayHasKey('app_id', $retrieveMessage['properties']);
        self::assertArrayHasKey('user_id', $retrieveMessage['properties']);

        self::assertEquals('m-id', $retrieveMessage['properties']['message_id']);
        self::assertEquals('a-id', $retrieveMessage['properties']['app_id']);
        self::assertEquals('guest', $retrieveMessage['properties']['user_id']);
    }

    private function publishMessage(Message $message): array
    {
        $this->exchange->publish($message, 'some');

        $retrieveMessages = $this->management->queueGetMessages('test', 1);

        self::assertCount(1, $retrieveMessages, 'The queue is empty. Messages not published to queue.');

        return $retrieveMessages[0];
    }
}
