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

use FiveLab\Component\Amqp\Queue\Definition\Arguments\ExpiresArgument;
use PHPUnit\Framework\TestCase;

class ExpiresArgumentTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessCreate(): void
    {
        $argument = new ExpiresArgument(123);

        self::assertEquals('x-expires', $argument->getName());
        self::assertEquals(123, $argument->getValue());
    }
}
