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

use FiveLab\Component\Amqp\Queue\Definition\Arguments\DeadLetterExchangeArgument;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DeadLetterExchangeArgumentTest extends TestCase
{
    #[Test]
    public function shouldSuccessCreate(): void
    {
        $argument = new DeadLetterExchangeArgument('some');

        self::assertEquals('x-dead-letter-exchange', $argument->name);
        self::assertEquals('some', $argument->value);
    }
}
