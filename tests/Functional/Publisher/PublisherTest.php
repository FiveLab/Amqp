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

namespace FiveLab\Component\Amqp\Tests\Functional\Publisher;

use FiveLab\Component\Amqp\Adapter\Amqp\Channel\AmqpChannelFactory;
use FiveLab\Component\Amqp\Adapter\Amqp\Connection\AmqpConnectionFactory;
use FiveLab\Component\Amqp\Adapter\Amqp\Exchange\AmqpExchangeFactory;
use FiveLab\Component\Amqp\Channel\Definition\ChannelDefinition;
use FiveLab\Component\Amqp\Connection\Driver;
use FiveLab\Component\Amqp\Exchange\Definition\ExchangeDefinition;
use FiveLab\Component\Amqp\Message\Message;
use FiveLab\Component\Amqp\Message\Payload;
use FiveLab\Component\Amqp\Publisher\Middleware\PublisherMiddlewares;
use FiveLab\Component\Amqp\Publisher\Publisher;
use FiveLab\Component\Amqp\Tests\Functional\RabbitMqTestCase;
use PHPUnit\Framework\Attributes\Test;

class PublisherTest extends RabbitMqTestCase
{
    private Publisher $publisher;

    protected function setUp(): void
    {
        parent::setUp();

        $connectionFactory = new AmqpConnectionFactory($this->getRabbitMqDsn(Driver::AmqpExt));

        $channelFactory = new AmqpChannelFactory($connectionFactory, new ChannelDefinition());
        $exchangeFactory = new AmqpExchangeFactory($channelFactory, new ExchangeDefinition('test', 'direct'));

        $exchangeFactory->create();

        $this->publisher = new Publisher($exchangeFactory, new PublisherMiddlewares());

        $this->management->createQueue('test');
        $this->management->queueBind('test', 'test', 'test-routing');
    }

    #[Test]
    public function shouldSuccessPublish(): void
    {
        $this->publisher->publish(new Message(
            new Payload('some', 'text/plain')
        ), 'test-routing');

        $messages = $this->management->queueGetMessages('test', 1);
        self::assertCount(1, $messages);

        $message = $messages[0];

        self::assertEquals('test', $message['exchange']);
        self::assertEquals('test-routing', $message['routing_key']);
        self::assertEquals('some', $message['payload']);
        self::assertEquals([
            'delivery_mode' => 2,
            'content_type'  => 'text/plain',
        ], $message['properties']);
    }
}
