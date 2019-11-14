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

use FiveLab\Component\Amqp\Queue\Definition\Arguments\MaxLengthBytesArgument;
use PHPUnit\Framework\TestCase;

class MaxLengthBytesArgumentTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessCreate(): void
    {
        $argument = new MaxLengthBytesArgument(111);

        self::assertEquals('x-max-length-bytes', $argument->getName());
        self::assertEquals(111, $argument->getValue());
    }
}
