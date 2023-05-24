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
use FiveLab\Component\Amqp\Connection\Driver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ArgumentDefinitionTest extends TestCase
{
    #[Test]
    public function shouldSuccessCreate(): void
    {
        $argument = new ArgumentDefinition('some', 'foo');

        self::assertEquals('some', $argument->name);
        self::assertEquals('foo', $argument->value);
    }

    #[Test]
    public function shouldSuccessCreateWithEnums(): void
    {
        $argument = new ArgumentDefinition(Driver::AmqpExt, Driver::AmqpLib);

        self::assertEquals('amqp', $argument->name);
        self::assertEquals(Driver::AmqpLib, $argument->value);
    }
}
