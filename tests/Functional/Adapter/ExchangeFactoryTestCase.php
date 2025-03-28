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

use FiveLab\Component\Amqp\Argument\ArgumentDefinitions;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinition;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinitions;
use FiveLab\Component\Amqp\Exchange\Definition\Arguments\AlternateExchangeArgument;
use FiveLab\Component\Amqp\Exchange\Definition\ExchangeDefinition;
use FiveLab\Component\Amqp\Exchange\ExchangeFactoryInterface;
use FiveLab\Component\Amqp\Tests\Functional\RabbitMqTestCase;
use PHPUnit\Framework\Attributes\Test;

abstract class ExchangeFactoryTestCase extends RabbitMqTestCase
{
    abstract protected function createExchangeFactory(ExchangeDefinition $definition): ExchangeFactoryInterface;

    #[Test]
    public function shouldSuccessCreateDefaultExchange(): void
    {
        $definition = new ExchangeDefinition('', AMQP_EX_TYPE_DIRECT);
        $factory = $this->createExchangeFactory($definition);
        $exchange = $factory->create();

        self::assertEquals('', $exchange->getName());
    }

    #[Test]
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

    #[Test]
    public function shouldSuccessCreateWithOtherType(): void
    {
        $definition = new ExchangeDefinition('some', AMQP_EX_TYPE_FANOUT);

        $factory = $this->createExchangeFactory($definition);
        $factory->create();

        $exchangeInfo = $this->management->exchangeByName('some');

        self::assertEquals('fanout', $exchangeInfo['type']);
    }

    #[Test]
    public function shouldSuccessCreateWithoutDurableFlag(): void
    {
        $definition = new ExchangeDefinition('some', AMQP_EX_TYPE_DIRECT, false);

        $factory = $this->createExchangeFactory($definition);
        $factory->create();

        $exchangeInfo = $this->management->exchangeByName('some');

        self::assertFalse($exchangeInfo['durable']);
    }

    #[Test]
    public function shouldSuccessCreatePassive(): void
    {
        $this->management->createExchange(AMQP_EX_TYPE_DIRECT, 'some');

        $definition = new ExchangeDefinition('some', AMQP_EX_TYPE_DIRECT, true, true);

        $factory = $this->createExchangeFactory($definition);
        $factory->create();

        $this->management->exchangeByName('some');

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function shouldSuccessCreateWithArguments(): void
    {
        $definition = new ExchangeDefinition(
            'some',
            AMQP_EX_TYPE_DIRECT,
            true,
            false,
            new ArgumentDefinitions(
                new AlternateExchangeArgument('foo-bar')
            )
        );

        $factory = $this->createExchangeFactory($definition);
        $factory->create();

        $exchangeInfo = $this->management->exchangeByName('some');
        $arguments = $exchangeInfo['arguments'];

        self::assertEquals([
            'alternate-exchange' => 'foo-bar',
        ], $arguments);
    }

    #[Test]
    public function shouldSuccessCreateWithBindings(): void
    {
        $this->management->createExchange('direct', 'foo.bar');

        $definition = new ExchangeDefinition(
            'some',
            AMQP_EX_TYPE_DIRECT,
            true,
            false,
            null,
            new BindingDefinitions(new BindingDefinition('foo.bar', 'some'))
        );

        $factory = $this->createExchangeFactory($definition);
        $factory->create();

        $bindings = $this->management->exchangeBindings('some');

        self::assertEquals([
            [
                'source'           => 'foo.bar',
                'vhost'            => $this->getRabbitMqVhost(),
                'destination'      => 'some',
                'destination_type' => 'exchange',
                'routing_key'      => 'some',
                'arguments'        => [],
                'properties_key'   => 'some',
            ],
        ], $bindings);
    }

    #[Test]
    public function shouldSuccessDelete(): void
    {
        $this->management->createExchange('direct', 'some');

        $definition = new ExchangeDefinition('some', AMQP_EX_TYPE_DIRECT);

        $factory = $this->createExchangeFactory($definition);
        $exchange = $factory->create();

        $this->management->exchangeByName('some');
        $this->addToAssertionCount(1); // because we success get exchange by name

        $exchange->delete();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The exchange with name "some" was not found.');

        $this->management->exchangeByName('some');
    }

    #[Test]
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
}
