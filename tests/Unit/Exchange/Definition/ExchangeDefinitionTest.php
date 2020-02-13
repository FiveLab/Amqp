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

namespace FiveLab\Component\Amqp\Tests\Unit\Exchange\Definition;

use FiveLab\Component\Amqp\Argument\ArgumentCollection;
use FiveLab\Component\Amqp\Argument\ArgumentDefinition;
use FiveLab\Component\Amqp\Binding\Definition\BindingCollection;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinition;
use FiveLab\Component\Amqp\Exchange\Definition\ExchangeDefinition;
use PHPUnit\Framework\TestCase;

class ExchangeDefinitionTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessCreateWithDefaults(): void
    {
        $def = new ExchangeDefinition('some', AMQP_EX_TYPE_DIRECT);

        self::assertEquals('some', $def->getName());
        self::assertEquals(AMQP_EX_TYPE_DIRECT, $def->getType());
        self::assertTrue($def->isDurable());
        self::assertFalse($def->isPassive());
        self::assertEquals(new BindingCollection(), $def->getBindings());
        self::assertEquals(new BindingCollection(), $def->getUnBindings());
    }

    /**
     * @test
     *
     * @param string $type
     *
     * @dataProvider provideExchangeTypes
     */
    public function shouldSuccessCreateWithCustomType(string $type): void
    {
        $def = new ExchangeDefinition('some', $type);

        self::assertEquals($type, $def->getType());
    }

    /**
     * @test
     */
    public function shouldThrowExceptionOnInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The type "foo" is invalid. Possible types: "direct", "topic", "fanout", "headers".');

        new ExchangeDefinition('some', 'foo');
    }

    /**
     * @test
     */
    public function shouldSuccessCreateWithoutDurable(): void
    {
        $def = new ExchangeDefinition('some', AMQP_EX_TYPE_DIRECT, false);

        self::assertFalse($def->isDurable());
    }

    /**
     * @test
     */
    public function shouldSuccessCreateWithPassive(): void
    {
        $def = new ExchangeDefinition('some', AMQP_EX_TYPE_DIRECT, true, true);

        self::assertTrue($def->isPassive());
    }

    /**
     * @test
     */
    public function shouldSuccessCreateDefaultExchange(): void
    {
        $def = new ExchangeDefinition(
            '',
            AMQP_EX_TYPE_DIRECT,
            true,
            false,
            new ArgumentCollection(),
            new BindingCollection(),
            new BindingCollection()
        );

        self::assertEquals('', $def->getName());
        self::assertTrue($def->isDurable());
        self::assertFalse($def->isPassive());
        self::assertEquals(new ArgumentCollection(), $def->getArguments());
        self::assertEquals(new BindingCollection(), $def->getBindings());
        self::assertEquals(new BindingCollection(), $def->getUnBindings());
    }

    /**
     * @test
     *
     * @param \Throwable              $expectedException
     * @param string                  $type
     * @param bool                    $durable
     * @param bool                    $passive
     * @param ArgumentCollection|null $arguments
     * @param BindingCollection|null  $bindings
     * @param BindingCollection|null  $unbindings
     *
     * @dataProvider provideInvalidParametersForDefaultExchange
     */
    public function shouldFailCreateDefaultExchange(\Throwable $expectedException, string $type, bool $durable, bool $passive, ArgumentCollection $arguments = null, BindingCollection $bindings = null, BindingCollection $unbindings = null): void
    {
        $this->expectException(\get_class($expectedException));
        $this->expectExceptionMessage($expectedException->getMessage());

        new ExchangeDefinition('', $type, $durable, $passive, $arguments, $bindings, $unbindings);
    }

    /**
     * Provide types
     *
     * @return array
     */
    public function provideExchangeTypes(): array
    {
        $exchanges = [
            AMQP_EX_TYPE_DIRECT,
            AMQP_EX_TYPE_FANOUT,
            AMQP_EX_TYPE_HEADERS,
            AMQP_EX_TYPE_TOPIC,
        ];

        return \array_map(static function (string $entry) {
            return [$entry];
        }, $exchanges);
    }

    /**
     * Provide invalid parameters for create default exchange
     *
     * @return array
     */
    public function provideInvalidParametersForDefaultExchange(): array
    {
        return [
            'invalid type' => [
                new \InvalidArgumentException('The default exchange allow only direct type but "fanout" given.'),
                'fanout',
                true,
                false,
            ],

            'non durable' => [
                new \InvalidArgumentException('The default exchange not allow not durable flag.'),
                'direct',
                false,
                false,
            ],

            'passive' => [
                new \InvalidArgumentException('The default exchange not allow passive flag.'),
                'direct',
                true,
                true,
            ],

            'with arguments' => [
                new \InvalidArgumentException('The default exchange not allow arguments.'),
                'direct',
                true,
                false,
                new ArgumentCollection(new ArgumentDefinition('x-some', 'foo')),
            ],

            'with bindings' => [
                new \InvalidArgumentException('The default exchange not allow bindings.'),
                'direct',
                true,
                false,
                null,
                new BindingCollection(new BindingDefinition('some', 'foo')),
            ],

            'with unbindings' => [
                new \InvalidArgumentException('The default exchange not allow un-bindings.'),
                'direct',
                true,
                false,
                null,
                null,
                new BindingCollection(new BindingDefinition('some', 'foo')),
            ],
        ];
    }
}
