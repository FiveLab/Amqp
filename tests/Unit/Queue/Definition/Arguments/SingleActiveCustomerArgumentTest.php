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

use FiveLab\Component\Amqp\Queue\Definition\Arguments\SingleActiveCustomerArgument;
use PHPUnit\Framework\TestCase;

class SingleActiveCustomerArgumentTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessCreate(): void
    {
        $argument = new SingleActiveCustomerArgument();

        self::assertEquals('x-single-active-consumer', $argument->getName());
        self::assertEquals('1', $argument->getValue());
    }
}
