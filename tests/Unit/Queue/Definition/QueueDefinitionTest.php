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

namespace FiveLab\Component\Amqp\Tests\Unit\Queue\Definition;

use FiveLab\Component\Amqp\Queue\Definition\QueueBindingDefinition;
use FiveLab\Component\Amqp\Queue\Definition\QueueBindingCollection;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;
use PHPUnit\Framework\TestCase;

class QueueDefinitionTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessCreateWithDefaults(): void
    {
        $def = new QueueDefinition('some', []);

        self::assertEquals('some', $def->getName());
        self::assertEquals(new QueueBindingCollection(), $def->getBindings());
        self::assertTrue($def->isDurable());
        self::assertFalse($def->isPassive());
        self::assertFalse($def->isExclusive());
        self::assertFalse($def->isAutoDelete());
    }

    /**
     * @test
     */
    public function shouldSuccessCreateWithBinding(): void
    {
        $bind1 = new QueueBindingDefinition('ex1', 'rout1');
        $bind2 = new QueueBindingDefinition('ex2', 'rout2');

        $def = new QueueDefinition('some', [$bind1, $bind2]);

        self::assertEquals(new QueueBindingCollection($bind1, $bind2), $def->getBindings());
    }

    /**
     * @test
     */
    public function shouldSuccessCreateWithUnBinding(): void
    {
        $bind1 = new QueueBindingDefinition('ex1', 'rout1');
        $bind2 = new QueueBindingDefinition('ex2', 'rout2');

        $def = new QueueDefinition('some', [], [$bind1, $bind2]);

        self::assertEquals(new QueueBindingCollection($bind1, $bind2), $def->getUnBindings());
    }

    /**
     * @test
     */
    public function shouldSuccessCreateWithoutDurable(): void
    {
        $def = new QueueDefinition('some', [], [], false);

        self::assertFalse($def->isDurable());
    }

    /**
     * @test
     */
    public function shouldSuccessCreateWithPassive(): void
    {
        $def = new QueueDefinition('some', [], [], true, true);

        self::assertTrue($def->isPassive());
    }

    /**
     * @test
     */
    public function shouldSuccessCreateWithExclusive(): void
    {
        $def = new QueueDefinition('some', [], [], true, true, true);

        self::assertTrue($def->isExclusive());
    }

    /**
     * @test
     */
    public function shouldSuccessCreateWithAutoDelete(): void
    {
        $def = new QueueDefinition('some', [], [], true, true, true, true);

        self::assertTrue($def->isAutoDelete());
    }
}
