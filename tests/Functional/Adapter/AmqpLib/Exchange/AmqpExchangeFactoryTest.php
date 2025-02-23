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

namespace FiveLab\Component\Amqp\Tests\Functional\Adapter\AmqpLib\Exchange;

use FiveLab\Component\Amqp\Adapter\AmqpLib\Channel\AmqpChannelFactory;
use FiveLab\Component\Amqp\Adapter\AmqpLib\Connection\AmqpConnectionFactory;
use FiveLab\Component\Amqp\Adapter\AmqpLib\Exchange\AmqpExchangeFactory;
use FiveLab\Component\Amqp\Channel\Definition\ChannelDefinition;
use FiveLab\Component\Amqp\Connection\Driver;
use FiveLab\Component\Amqp\Exchange\Definition\ExchangeDefinition;
use FiveLab\Component\Amqp\Exchange\ExchangeFactoryInterface;
use FiveLab\Component\Amqp\Tests\Functional\Adapter\ExchangeFactoryTestCase;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PHPUnit\Framework\Attributes\Test;

class AmqpExchangeFactoryTest extends ExchangeFactoryTestCase
{
    protected function createExchangeFactory(ExchangeDefinition $definition): ExchangeFactoryInterface
    {
        $connectionFactory = new AmqpConnectionFactory($this->getRabbitMqDsn(Driver::AmqpLib));

        $channelFactory = new AmqpChannelFactory($connectionFactory, new ChannelDefinition());

        return new AmqpExchangeFactory($channelFactory, $definition);
    }

    #[Test]
    public function shouldThrowExceptionOnCreatePassiveIfExchangeNotFound(): void
    {
        $this->expectException(AMQPProtocolChannelException::class);

        $this->expectExceptionMessage(\sprintf(
            'NOT_FOUND - no exchange \'some\' in vhost \'%s\'',
            $this->getRabbitMqVhost()
        ));

        $definition = new ExchangeDefinition('some', AMQP_EX_TYPE_DIRECT, true, true);

        $factory = $this->createExchangeFactory($definition);
        $factory->create();
    }
}
