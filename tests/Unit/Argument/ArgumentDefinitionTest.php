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
use PHPUnit\Framework\TestCase;

class ArgumentDefinitionTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessCreate(): void
    {
        $argument = new ArgumentDefinition('some', 'foo');

        self::assertEquals('some', $argument->getName());
        self::assertEquals('foo', $argument->getValue());
    }
}
