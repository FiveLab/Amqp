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

abstract class ExchangeTestCase extends RabbitMqTestCase
{
    /**
     * @var ExchangeInterface
     */
    private $exchange;

    /**
     * Create exchange factory for testing
     *
     * @param ExchangeDefinition $definition
     *
     * @return ExchangeFactoryInterface
     */
    abstract protected function createExchangeFactory(ExchangeDefinition $definition): ExchangeFactoryInterface;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $exchangeDefinition = new ExchangeDefinition('test', AMQP_EX_TYPE_DIRECT);
        $this->exchange = $this->createExchangeFactory($exchangeDefinition)->create();

        $this->management->createQueue('test');
        $this->management->queueBind('test', 'test', 'some');
    }

    /**
     * @test
     */
    public function shouldSuccessPublishWithDefaults(): void
    {
        $message = new Message(new Payload('some foo bar'));

        $retrieveMessage = $this->publishMessage($message);

        self::assertEquals('text/plain', $retrieveMessage['properties']['content_type']);
        self::assertEquals(2, $retrieveMessage['properties']['delivery_mode']);
        self::assertEquals('some', $retrieveMessage['routing_key']);
        self::assertEquals('some foo bar', $retrieveMessage['payload']);
    }

    /**
     * @test
     */
    public function shouldSuccessPublishWithCustomContentType(): void
    {
        $message = new Message(new Payload('{"a":"b"}', 'application/json'));

        $retrieveMessage = $this->publishMessage($message);

        self::assertEquals('application/json', $retrieveMessage['properties']['content_type']);
        self::assertEquals('{"a":"b"}', $retrieveMessage['payload']);
    }

    /**
     * @test
     */
    public function shouldSuccessPublishWithoutDurableMode(): void
    {
        $message = new Message(new Payload('some foo bar'), new Options(false));

        $retrieveMessage = $this->publishMessage($message);

        self::assertEquals(1, $retrieveMessage['properties']['delivery_mode']);
    }

    /**
     * @test
     */
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

    /**
     * @test
     *
     * @group foo
     */
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

    /**
     * Publish message
     *
     * @param Message $message
     *
     * @return array
     */
    private function publishMessage(Message $message): array
    {
        $this->exchange->publish('some', $message);

        $retrieveMessages = $this->management->queueGetMessages('test', 1);

        self::assertCount(1, $retrieveMessages, 'The queue is empty. Messages not published to queue.');

        return $retrieveMessages[0];
    }
}
