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

use FiveLab\Component\Amqp\Argument\ArgumentDefinition;
use FiveLab\Component\Amqp\Argument\ArgumentDefinitions;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinition;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinitions;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class QueueDefinitionTest extends TestCase
{
    #[Test]
    public function shouldSuccessCreateWithDefaults(): void
    {
        $def = new QueueDefinition('some');

        self::assertEquals('some', $def->name);
        self::assertEquals(new BindingDefinitions(), $def->bindings);
        self::assertEquals(new BindingDefinitions(), $def->unbindings);
        self::assertTrue($def->durable);
        self::assertFalse($def->passive);
        self::assertFalse($def->exclusive);
        self::assertFalse($def->autoDelete);
    }

    #[Test]
    public function shouldSuccessCreateWithBinding(): void
    {
        $bind1 = new BindingDefinition('ex1', 'rout1');
        $bind2 = new BindingDefinition('ex2', 'rout2');

        $def = new QueueDefinition('some', new BindingDefinitions($bind1, $bind2));

        self::assertEquals(new BindingDefinitions($bind1, $bind2), $def->bindings);
    }

    #[Test]
    public function shouldSuccessCreateWithUnBinding(): void
    {
        $bind1 = new BindingDefinition('ex1', 'rout1');
        $bind2 = new BindingDefinition('ex2', 'rout2');

        $def = new QueueDefinition('some', new BindingDefinitions(), new BindingDefinitions($bind1, $bind2));

        self::assertEquals(new BindingDefinitions($bind1, $bind2), $def->unbindings);
    }

    #[Test]
    public function shouldSuccessCreateWithoutDurable(): void
    {
        $def = new QueueDefinition('some', null, null, false);

        self::assertFalse($def->durable);
    }

    #[Test]
    public function shouldSuccessCreateWithPassive(): void
    {
        $def = new QueueDefinition('some', null, null, true, true);

        self::assertTrue($def->passive);
    }

    #[Test]
    public function shouldSuccessCreateWithExclusive(): void
    {
        $def = new QueueDefinition('some', null, null, true, true, true);

        self::assertTrue($def->exclusive);
    }

    #[Test]
    public function shouldSuccessCreateWithAutoDelete(): void
    {
        $def = new QueueDefinition('some', null, null, true, true, true, true);

        self::assertTrue($def->autoDelete);
    }

    #[Test]
    public function shouldSuccessCreateWithArguments(): void
    {
        $def = new QueueDefinition('some', null, null, false, false, false, false, new ArgumentDefinitions(
            new ArgumentDefinition('some', 'foo-bar')
        ));

        self::assertEquals(new ArgumentDefinitions(new ArgumentDefinition('some', 'foo-bar')), $def->arguments);
    }
}
