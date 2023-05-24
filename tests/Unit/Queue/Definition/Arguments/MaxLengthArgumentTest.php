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

use FiveLab\Component\Amqp\Queue\Definition\Arguments\MaxLengthArgument;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MaxLengthArgumentTest extends TestCase
{
    #[Test]
    public function shouldSuccessCreate(): void
    {
        $argument = new MaxLengthArgument(123);

        self::assertEquals('x-max-length', $argument->name);
        self::assertEquals(123, $argument->value);
    }
}
