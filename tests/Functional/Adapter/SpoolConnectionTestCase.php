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

use FiveLab\Component\Amqp\Channel\Definition\ChannelDefinition;
use FiveLab\Component\Amqp\Connection\ConnectionFactoryInterface;
use FiveLab\Component\Amqp\Connection\SpoolConnectionFactory;
use FiveLab\Component\Amqp\Exchange\Definition\ExchangeDefinition;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;
use FiveLab\Component\Amqp\Tests\Functional\RabbitMqTestCase;

abstract class SpoolConnectionTestCase extends RabbitMqTestCase
{
    protected const CHANNEL_CLASS  = 'ChannelClass';
    protected const EXCHANGE_CLASS = 'ExchangeClass';
    protected const QUEUE_CLASS    = 'QueueClass';

    /**
     * Create connection factory
     *
     * @return ConnectionFactoryInterface
     */
    abstract protected function createConnectionFactory(): ConnectionFactoryInterface;

    /**
     * Get classes for make factories
     *
     * @return array
     */
    abstract protected function getClasses(): array;

    /**
     * @test
     */
    public function shouldSuccessConnect(): void
    {
        $factory = $this->makeSpoolConnectionFactory();
        $factory->create()->connect();

        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function shouldSuccessGetChannel(): void
    {
        $connectionFactory = $this->makeSpoolConnectionFactory();

        $factoryClass = $this->getClasses()[self::CHANNEL_CLASS];
        $channelFactory = new $factoryClass($connectionFactory, new ChannelDefinition());

        $channelFactory->create();

        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function shouldSuccessCreateExchange(): void
    {
        $connectionFactory = $this->makeSpoolConnectionFactory();

        $channelFactoryClass = $this->getClasses()[self::CHANNEL_CLASS];
        $exchangeFactoryClass = $this->getClasses()[self::EXCHANGE_CLASS];

        $channelFactory = new $channelFactoryClass($connectionFactory, new ChannelDefinition());

        $exchangeDef = new ExchangeDefinition(\uniqid('', true), 'direct');
        $exchangeFactory = new $exchangeFactoryClass($channelFactory, $exchangeDef);

        $exchangeFactory->create();

        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function shouldSuccessCreateQueue(): void
    {
        $connectionFactory = $this->makeSpoolConnectionFactory();

        $channelFactoryClass = $this->getClasses()[self::CHANNEL_CLASS];
        $queueFactoryClass = $this->getClasses()[self::QUEUE_CLASS];

        $channelFactory = new $channelFactoryClass($connectionFactory, new ChannelDefinition());

        $queueDef = new QueueDefinition(\uniqid('', true));
        $queueFactory = new $queueFactoryClass($channelFactory, $queueDef);

        $queueFactory->create();

        $this->addToAssertionCount(1);
    }

    /**
     * Make spool connection factory
     *
     * @return SpoolConnectionFactory
     */
    private function makeSpoolConnectionFactory(): SpoolConnectionFactory
    {
        return new SpoolConnectionFactory($this->createConnectionFactory());
    }
}
