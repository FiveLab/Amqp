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
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinitions;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinition;
use FiveLab\Component\Amqp\Exchange\Definition\Arguments\AlternateExchangeArgument;
use FiveLab\Component\Amqp\Exchange\Definition\ExchangeDefinition;
use FiveLab\Component\Amqp\Exchange\ExchangeFactoryInterface;
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
    public function shouldSuccessCreateDefaultExchange(): void
    {
        $definition = new ExchangeDefinition('', AMQP_EX_TYPE_DIRECT);
        $factory = $this->createExchangeFactory($definition);
        $exchange = $factory->create();

        self::assertEquals('', $exchange->getName());
    }

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

    /**
     * @test
     */
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
}
