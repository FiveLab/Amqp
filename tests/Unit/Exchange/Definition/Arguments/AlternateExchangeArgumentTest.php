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

namespace FiveLab\Component\Amqp\Tests\Unit\Exchange\Definition\Arguments;

use FiveLab\Component\Amqp\Exchange\Definition\Arguments\AlternateExchangeArgument;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AlternateExchangeArgumentTest extends TestCase
{
    #[Test]
    public function shouldSuccessCreate(): void
    {
        $argument = new AlternateExchangeArgument('foo');

        self::assertEquals('alternate-exchange', $argument->name);
        self::assertEquals('foo', $argument->value);
    }
}
