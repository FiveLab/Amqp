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

namespace FiveLab\Component\Amqp\Tests\Unit\Binding\Definition;

use FiveLab\Component\Amqp\Binding\Definition\BindingDefinition;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinitions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BindingDefinitionsTest extends TestCase
{
    #[Test]
    public function shouldSuccessGetIterator(): void
    {
        $bindings = new BindingDefinitions(
            new BindingDefinition('foo', 'bar'),
            new BindingDefinition('bar', 'foo')
        );

        self::assertEquals([
            new BindingDefinition('foo', 'bar'),
            new BindingDefinition('bar', 'foo'),
        ], \iterator_to_array($bindings));
    }

    #[Test]
    public function shouldSuccessGetCount(): void
    {
        $bindings = new BindingDefinitions(
            new BindingDefinition('foo', 'bar'),
            new BindingDefinition('bar', 'foo')
        );

        self::assertCount(2, $bindings);
    }
}
