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

namespace FiveLab\Component\Amqp\Tests\Functional\Transactional;

use FiveLab\Component\Amqp\Adapter\Amqp\Channel\AmqpChannelFactory;
use FiveLab\Component\Amqp\Adapter\Amqp\Connection\AmqpConnectionFactory;
use FiveLab\Component\Amqp\Adapter\Amqp\Exchange\AmqpExchangeFactory;
use FiveLab\Component\Amqp\Adapter\Amqp\Queue\AmqpQueueFactory;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinition;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinitions;
use FiveLab\Component\Amqp\Channel\Definition\ChannelDefinition;
use FiveLab\Component\Amqp\Connection\Driver;
use FiveLab\Component\Amqp\Exchange\Definition\ExchangeDefinition;
use FiveLab\Component\Amqp\Message\Message;
use FiveLab\Component\Amqp\Message\Payload;
use FiveLab\Component\Amqp\Publisher\Middleware\PublisherMiddlewares;
use FiveLab\Component\Amqp\Publisher\Publisher;
use FiveLab\Component\Amqp\Publisher\SavepointPublisherDecorator;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;
use FiveLab\Component\Amqp\Tests\Functional\RabbitMqTestCase;
use FiveLab\Component\Amqp\Transactional\FlushSavepointPublisherTransactional;
use PHPUnit\Framework\Attributes\Test;

class FlushSavepointPublisherTransactionalTest extends RabbitMqTestCase
{
    /**
     * @var AmqpQueueFactory
     */
    private AmqpQueueFactory $queueFactory;

    /**
     * @var FlushSavepointPublisherTransactional
     */
    private FlushSavepointPublisherTransactional $transactional;

    /**
     * @var SavepointPublisherDecorator
     */
    private SavepointPublisherDecorator $savepointPublisher;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $connectionFactory = new AmqpConnectionFactory($this->getRabbitMqDsn(Driver::AmqpExt));

        $channelFactory = new AmqpChannelFactory($connectionFactory, new ChannelDefinition());
        $exchangeFactory = new AmqpExchangeFactory($channelFactory, new ExchangeDefinition('test', 'direct'));

        $exchangeFactory->create();

        $this->queueFactory = new AmqpQueueFactory($channelFactory, new QueueDefinition(
            'test',
            new BindingDefinitions(
                new BindingDefinition('test', 'foo.bar')
            )
        ));

        $this->queueFactory->create();

        $publisher = new Publisher($exchangeFactory, new PublisherMiddlewares());

        $this->savepointPublisher = new SavepointPublisherDecorator($publisher);
        $this->transactional = new FlushSavepointPublisherTransactional($this->savepointPublisher);
    }

    #[Test]
    public function shouldSuccessOnOneDepth(): void
    {
        $this->transactional->begin();

        $this->savepointPublisher->publish(new Message(new Payload('foo')), 'foo.bar');
        $this->savepointPublisher->publish(new Message(new Payload('bar')), 'foo.bar');

        $this->transactional->commit();

        $messages = $this->getAllMessagesFromQueue($this->queueFactory);

        self::assertCount(2, $messages);
    }

    #[Test]
    public function shouldSuccessOnOneDepthWithRollback(): void
    {
        $this->transactional->begin();

        $this->savepointPublisher->publish(new Message(new Payload('foo')), 'foo.bar');
        $this->savepointPublisher->publish(new Message(new Payload('bar')), 'foo.bar');

        $this->transactional->rollback();

        $messages = $this->getAllMessagesFromQueue($this->queueFactory);

        self::assertCount(0, $messages);
    }

    #[Test]
    public function shouldSuccessOnTwoDepth(): void
    {
        $this->transactional->begin();

        $this->savepointPublisher->publish(new Message(new Payload('foo')), 'foo.bar');

        $this->transactional->begin();

        $this->savepointPublisher->publish(new Message(new Payload('bar')), 'foo.bar');

        $this->transactional->commit();

        $this->savepointPublisher->publish(new Message(new Payload('some')), 'foo.bar');

        $this->transactional->commit();

        $messages = $this->getAllMessagesFromQueue($this->queueFactory);

        self::assertCount(3, $messages);
    }

    #[Test]
    public function shouldSuccessOnThreeDepthWithRollbackOnSecondLevel(): void
    {
        $this->transactional->begin();
        $this->transactional->begin();

        $this->savepointPublisher->publish(new Message(new Payload('foo')), 'foo.bar');

        $this->transactional->begin();

        $this->savepointPublisher->publish(new Message(new Payload('bar')), 'foo.bar');

        $this->transactional->commit();

        $this->transactional->rollback();
        $this->transactional->commit();

        $messages = $this->getAllMessagesFromQueue($this->queueFactory);

        self::assertCount(0, $messages);
    }
}