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

use FiveLab\Component\Amqp\Argument\ArgumentDefinition;
use FiveLab\Component\Amqp\Argument\ArgumentDefinitions;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinition;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinitions;
use FiveLab\Component\Amqp\Exchange\Definition\ExchangeDefinition;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExchangeDefinitionTest extends TestCase
{
    #[Test]
    public function shouldSuccessCreateWithDefaults(): void
    {
        $def = new ExchangeDefinition('some', AMQP_EX_TYPE_DIRECT);

        self::assertEquals('some', $def->name);
        self::assertEquals(AMQP_EX_TYPE_DIRECT, $def->type);
        self::assertTrue($def->durable);
        self::assertFalse($def->passive);
        self::assertEquals(new BindingDefinitions(), $def->bindings);
        self::assertEquals(new BindingDefinitions(), $def->unbindings);
    }

    #[Test]
    #[DataProvider('provideExchangeTypes')]
    public function shouldSuccessCreateWithCustomType(string $type): void
    {
        $def = new ExchangeDefinition('some', $type);

        self::assertEquals($type, $def->type);
    }

    #[Test]
    public function shouldThrowExceptionOnInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The type "foo" is invalid. Possible types: "direct", "topic", "fanout", "headers".');

        new ExchangeDefinition('some', 'foo');
    }

    #[Test]
    public function shouldSuccessCreateWithoutDurable(): void
    {
        $def = new ExchangeDefinition('some', AMQP_EX_TYPE_DIRECT, false);

        self::assertFalse($def->durable);
    }

    #[Test]
    public function shouldSuccessCreateWithPassive(): void
    {
        $def = new ExchangeDefinition('some', AMQP_EX_TYPE_DIRECT, true, true);

        self::assertTrue($def->passive);
    }

    #[Test]
    public function shouldSuccessCreateDefaultExchange(): void
    {
        $def = new ExchangeDefinition(
            '',
            AMQP_EX_TYPE_DIRECT,
            true,
            false,
            new ArgumentDefinitions(),
            new BindingDefinitions(),
            new BindingDefinitions()
        );

        self::assertEquals('', $def->name);
        self::assertTrue($def->durable);
        self::assertFalse($def->passive);
        self::assertEquals(new ArgumentDefinitions(), $def->arguments);
        self::assertEquals(new BindingDefinitions(), $def->bindings);
        self::assertEquals(new BindingDefinitions(), $def->unbindings);
    }

    #[Test]
    #[DataProvider('provideInvalidParametersForDefaultExchange')]
    public function shouldFailCreateDefaultExchange(\Throwable $expectedException, string $type, bool $durable, bool $passive, ?ArgumentDefinitions $arguments = null, ?BindingDefinitions $bindings = null, ?BindingDefinitions $unbindings = null): void
    {
        $this->expectException(\get_class($expectedException));
        $this->expectExceptionMessage($expectedException->getMessage());

        new ExchangeDefinition('', $type, $durable, $passive, $arguments, $bindings, $unbindings);
    }

    public static function provideExchangeTypes(): array
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

    public static function provideInvalidParametersForDefaultExchange(): array
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
                new ArgumentDefinitions(new ArgumentDefinition('x-some', 'foo')),
            ],

            'with bindings' => [
                new \InvalidArgumentException('The default exchange not allow bindings.'),
                'direct',
                true,
                false,
                null,
                new BindingDefinitions(new BindingDefinition('some', 'foo')),
            ],

            'with unbindings' => [
                new \InvalidArgumentException('The default exchange not allow un-bindings.'),
                'direct',
                true,
                false,
                null,
                null,
                new BindingDefinitions(new BindingDefinition('some', 'foo')),
            ],
        ];
    }
}
