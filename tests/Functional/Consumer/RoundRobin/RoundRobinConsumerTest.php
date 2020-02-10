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

namespace FiveLab\Component\Amqp\Tests\Functional\Consumer\RoundRobin;

use FiveLab\Component\Amqp\Adapter\Amqp\Channel\AmqpChannelFactory;
use FiveLab\Component\Amqp\Adapter\Amqp\Connection\AmqpConnectionFactory;
use FiveLab\Component\Amqp\Adapter\Amqp\Queue\AmqpQueueFactory;
use FiveLab\Component\Amqp\Binding\Definition\BindingCollection;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinition;
use FiveLab\Component\Amqp\Channel\Definition\ChannelDefinition;
use FiveLab\Component\Amqp\Consumer\ConsumerConfiguration;
use FiveLab\Component\Amqp\Consumer\Middleware\ConsumerMiddlewareCollection;
use FiveLab\Component\Amqp\Consumer\SingleConsumer;
use FiveLab\Component\Amqp\Consumer\RoundRobin\RoundRobinConsumer;
use FiveLab\Component\Amqp\Consumer\RoundRobin\RoundRobinConsumerConfiguration;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;
use FiveLab\Component\Amqp\Tests\Functional\Consumer\Handler\MessageHandlerMock;
use FiveLab\Component\Amqp\Tests\Functional\RabbitMqTestCase;

class RoundRobinConsumerTest extends RabbitMqTestCase
{
    /**
     * @var MessageHandlerMock
     */
    private $handler1;

    /**
     * @var MessageHandlerMock
     */
    private $handler2;

    /**
     * @var AmqpQueueFactory
     */
    private $queueFactory1;

    /**
     * @var AmqpQueueFactory
     */
    private $queueFactory2;

    /**
     * {@inheritdoc}}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->management->createExchange('direct', 'exchange1');
        $this->management->createQueue('queue1');
        $this->management->queueBind('queue1', 'exchange1', 'foo1');

        $this->management->createExchange('direct', 'exchange2');
        $this->management->createQueue('queue2');
        $this->management->queueBind('queue2', 'exchange2', 'foo2');

        $this->handler1 = new MessageHandlerMock('foo1');
        $this->handler2 = new MessageHandlerMock('foo2');

        $connectionFactory = new AmqpConnectionFactory([
            'host'     => $this->getRabbitMqHost(),
            'port'     => $this->getRabbitMqPort(),
            'vhost'    => $this->getRabbitMqVhost(),
            'login'    => $this->getRabbitMqLogin(),
            'password' => $this->getRabbitMqPassword(),
        ]);

        $channelFactory = new AmqpChannelFactory($connectionFactory, new ChannelDefinition());

        $this->queueFactory1 = new AmqpQueueFactory($channelFactory, new QueueDefinition('queue1', new BindingCollection(
            new BindingDefinition('exchange1', 'foo1')
        )));

        $this->queueFactory2 = new AmqpQueueFactory($channelFactory, new QueueDefinition('queue2', new BindingCollection(
            new BindingDefinition('exchange2', 'foo2')
        )));
    }

    /**
     * @test
     */
    public function shouldSuccessProcessInRoundRobin(): void
    {
        $this->management->publishMessage('exchange1', 'foo1', 'queue1-message-1');
        $this->management->publishMessage('exchange1', 'foo1', 'queue1-message-2');

        $this->management->publishMessage('exchange2', 'foo2', 'queue2-message-1');
        $this->management->publishMessage('exchange2', 'foo2', 'queue2-message-2');

        $consumer1 = new SingleConsumer($this->queueFactory1, $this->handler1, new ConsumerMiddlewareCollection(), new ConsumerConfiguration());
        $consumer2 = new SingleConsumer($this->queueFactory2, $this->handler2, new ConsumerMiddlewareCollection(), new ConsumerConfiguration());

        $configuration = new RoundRobinConsumerConfiguration(1, 1, 5);

        $roundRobin = new RoundRobinConsumer($configuration, $consumer1, $consumer2);

        try {
            $roundRobin->run();
        } catch (ConsumerTimeoutExceedException $e) {
            if ($e->getMessage() !== 'Round robin consumer timeout exceed.') {
                throw $e;
            }
        }

        $messagesFromHandler1 = $this->handler1->getReceivedMessages();

        self::assertCount(2, $messagesFromHandler1);
        self::assertEquals('queue1-message-1', $messagesFromHandler1[0]->getPayload()->getData());
        self::assertEquals('queue1-message-2', $messagesFromHandler1[1]->getPayload()->getData());

        $messagesFromHandler2 = $this->handler2->getReceivedMessages();

        self::assertCount(2, $messagesFromHandler2);
        self::assertEquals('queue2-message-1', $messagesFromHandler2[0]->getPayload()->getData());
        self::assertEquals('queue2-message-2', $messagesFromHandler2[1]->getPayload()->getData());
    }
}
