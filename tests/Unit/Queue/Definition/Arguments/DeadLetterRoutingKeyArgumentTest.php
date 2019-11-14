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

namespace FiveLab\Component\Amqp\Tests\Unit\Queue\Definition\Arguments;

use FiveLab\Component\Amqp\Queue\Definition\Arguments\DeadLetterRoutingKeyArgument;
use PHPUnit\Framework\TestCase;

class DeadLetterRoutingKeyArgumentTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessCreate(): void
    {
        $argument = new DeadLetterRoutingKeyArgument('foo.bar');

        self::assertEquals('x-dead-letter-routing-key', $argument->getName());
        self::assertEquals('foo.bar', $argument->getValue());
    }
}
