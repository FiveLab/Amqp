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

namespace FiveLab\Component\Amqp\Tests\Unit\Argument;

use FiveLab\Component\Amqp\Argument\ArgumentDefinition;
use FiveLab\Component\Amqp\Argument\ArgumentDefinitions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ArgumentDefinitionsTest extends TestCase
{
    #[Test]
    public function shouldSuccessGetIterator(): void
    {
        $argument1 = new ArgumentDefinition('foo', 'bar');
        $argument2 = new ArgumentDefinition('bar', 'foo');

        $arguments = new ArgumentDefinitions($argument1, $argument2);

        self::assertEquals([$argument1, $argument2], \iterator_to_array($arguments));
    }

    #[Test]
    public function shouldSuccessGetCount(): void
    {
        $arguments = new ArgumentDefinitions(
            new ArgumentDefinition('foo', 'bar'),
            new ArgumentDefinition('bar', 'foo')
        );

        self::assertCount(2, $arguments);
    }

    #[Test]
    public function shouldSuccessConvertToArray(): void
    {
        $arguments = new ArgumentDefinitions(
            new ArgumentDefinition('foo', 'bar'),
            new ArgumentDefinition('bar', 'foo')
        );

        self::assertEquals([
            'foo' => 'bar',
            'bar' => 'foo',
        ], $arguments->toArray());
    }
}
