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

use FiveLab\Component\Amqp\Queue\Definition\Arguments\QueueModeArgument;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class QueueModeArgumentTest extends TestCase
{
    #[Test]
    #[TestWith(['lazy'])]
    #[TestWith(['default'])]
    public function shouldSuccessCreate(string $mode): void
    {
        $argument = new QueueModeArgument($mode);

        self::assertEquals('x-queue-mode', $argument->name);
        self::assertEquals($mode, $argument->value);
    }

    #[Test]
    public function shouldThrowExceptionForInvalidMode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid queue mode "some". Possible modes: "default", "lazy".');

        new QueueModeArgument('some');
    }
}
