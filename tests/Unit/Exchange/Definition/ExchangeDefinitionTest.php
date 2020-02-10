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

use FiveLab\Component\Amqp\Binding\Definition\BindingCollection;
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

        return \array_map(function (string $entry) {
            return [$entry];
        }, $exchanges);
    }
}
