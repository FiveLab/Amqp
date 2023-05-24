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

namespace FiveLab\Component\Amqp\Tests\Unit\Message\Generator;

use FiveLab\Component\Amqp\Message\Generator\RamseyUuidMessageIdGenerator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RamseyUuidMessageIdGeneratorTest extends TestCase
{
    #[Test]
    public function shouldSuccessGenerate(): void
    {
        $generator = new RamseyUuidMessageIdGenerator();

        self::assertNotEmpty($generator->generate());
    }
}
