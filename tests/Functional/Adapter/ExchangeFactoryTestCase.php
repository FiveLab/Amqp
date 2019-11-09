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
use FiveLab\Component\Amqp\Message\Message;
use FiveLab\Component\Amqp\Message\Options;
use FiveLab\Component\Amqp\Message\Payload;
use FiveLab\Component\Amqp\Tests\Functional\RabbitMqTestCase;

abstract class ExchangeFactoryTestCase extends RabbitMqTestCase
{
    /**
     * Create exchange factory for testing
     *
     * @param ExchangeDefinition $definition
     *
     * @return ExchangeFactoryInterface
     */
    abstract protected function createExchangeFactory(ExchangeDefinition $definition): ExchangeFactoryInterface;

    /**
     * @test
     */
    public function shouldSuccessCreateWithDefaults(): void
    {
        $definition = new ExchangeDefinition('some', AMQP_EX_TYPE_DIRECT);

        $factory = $this->createExchangeFactory($definition);
        $factory->create();

        $exchangeInfo = $this->management->exchangeByName('some');

        self::assertFalse($exchangeInfo['auto_delete']);
        self::assertTrue($exchangeInfo['durable']);
        self::assertEquals('direct', $exchangeInfo['type']);
    }

    /**
     * @test
     */
    public function shouldSuccessCreateWithOtherType(): void
    {
        $definition = new ExchangeDefinition('some', AMQP_EX_TYPE_FANOUT);

        $factory = $this->createExchangeFactory($definition);
        $factory->create();

        $exchangeInfo = $this->management->exchangeByName('some');

        self::assertEquals('fanout', $exchangeInfo['type']);
    }

    /**
     * @test
     */
    public function shouldSuccessCreateWithoutDurableFlag(): void
    {
        $definition = new ExchangeDefinition('some', AMQP_EX_TYPE_DIRECT, false);

        $factory = $this->createExchangeFactory($definition);
        $factory->create();

        $exchangeInfo = $this->management->exchangeByName('some');

        self::assertFalse($exchangeInfo['durable']);
    }

    /**
     * @test
     */
    public function shouldSuccessCreatePassive(): void
    {
        $this->management->createExchange(AMQP_EX_TYPE_DIRECT, 'some');

        $definition = new ExchangeDefinition('some', AMQP_EX_TYPE_DIRECT, true, true);

        $factory = $this->createExchangeFactory($definition);
        $factory->create();

        $this->management->exchangeByName('some');

        $this->expectNotToPerformAssertions();
    }

    /**
     * @test
     */
    public function shouldThrowExceptionOnCreatePassiveIfExchangeNotFound(): void
    {
        $this->expectException(\AMQPExchangeException::class);
        $this->expectExceptionMessage(\sprintf(
            'Server channel error: 404, message: NOT_FOUND - no exchange \'some\' in vhost \'%s\'',
            $this->getRabbitMqVhost()
        ));

        $definition = new ExchangeDefinition('some', AMQP_EX_TYPE_DIRECT, true, true);

        $factory = $this->createExchangeFactory($definition);
        $factory->create();
    }

    /**
     * @test
     */
    public function shouldSuccessPublishWithDefaults(): void
    {
        $message = new Message(new Payload('some foo bar'));

        $definition = new ExchangeDefinition('some', AMQP_EX_TYPE_DIRECT);

        $factory = $this->createExchangeFactory($definition);
        $exchange = $factory->create();

        // Create queue for publish message and next retrieve for check.
        $this->management->createQueue('some');
        $this->management->queueBind('some', 'some', 'test');

        $exchange->publish('test', $message);

        $retrieveMessages = $this->management->queueGetMessages('some', 1);

        self::assertCount(1, $retrieveMessages, 'The queue is empty. Messages not published to queue.');
        $retrieveMessage = $retrieveMessages[0];

        self::assertEquals('text/plain', $retrieveMessage['properties']['content_type']);
        self::assertEquals(2, $retrieveMessage['properties']['delivery_mode']);
        self::assertEquals('test', $retrieveMessage['routing_key']);
        self::assertEquals('some foo bar', $retrieveMessage['payload']);
    }

    /**
     * @test
     */
    public function shouldSuccessPublishWithCustomContentType(): void
    {
        $message = new Message(new Payload('{"a":"b"}', 'application/json'));

        $definition = new ExchangeDefinition('some', AMQP_EX_TYPE_DIRECT);

        $factory = $this->createExchangeFactory($definition);
        $exchange = $factory->create();

        // Create queue for publish message and next retrieve for check.
        $this->management->createQueue('some');
        $this->management->queueBind('some', 'some', 'test');

        $exchange->publish('test', $message);

        $retrieveMessages = $this->management->queueGetMessages('some', 1);

        self::assertCount(1, $retrieveMessages, 'The queue is empty. Messages not published to queue.');
        $retrieveMessage = $retrieveMessages[0];

        self::assertEquals('application/json', $retrieveMessage['properties']['content_type']);
        self::assertEquals('{"a":"b"}', $retrieveMessage['payload']);
    }

    /**
     * @test
     */
    public function shouldSuccessPublishWithoutDurableMode(): void
    {
        $message = new Message(new Payload('some foo bar'), new Options(false));

        $definition = new ExchangeDefinition('some', AMQP_EX_TYPE_DIRECT);

        $factory = $this->createExchangeFactory($definition);
        $exchange = $factory->create();

        // Create queue for publish message and next retrieve for check.
        $this->management->createQueue('some');
        $this->management->queueBind('some', 'some', 'test');

        $exchange->publish('test', $message);

        $retrieveMessages = $this->management->queueGetMessages('some', 1);

        self::assertCount(1, $retrieveMessages, 'The queue is empty. Messages not published to queue.');
        $retrieveMessage = $retrieveMessages[0];

        self::assertEquals(1, $retrieveMessage['properties']['delivery_mode']);
    }
}
