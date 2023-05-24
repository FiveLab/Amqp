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
use FiveLab\Component\Amqp\Connection\Driver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BindingDefinitionTest extends TestCase
{
    #[Test]
    public function shouldSuccessCreate(): void
    {
        $def = new BindingDefinition('some', 'test');

        self::assertEquals('some', $def->exchangeName);
        self::assertEquals('test', $def->routingKey);
    }

    #[Test]
    public function shouldSuccessCreateWithEnums(): void
    {
        $def = new BindingDefinition(Driver::AmqpExt, Driver::AmqpLib);

        self::assertEquals('amqp', $def->exchangeName);
        self::assertEquals('amqp-lib', $def->routingKey);
    }
}
